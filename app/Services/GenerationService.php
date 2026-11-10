<?php

namespace App\Services;

use App\DTO\CreateGenerationData;
use App\DTO\ProviderResult;
use App\Enums\GenerationStatus;
use App\Exceptions\ConcurrencyLimitException;
use App\Exceptions\InsufficientCreditsException;
use App\Jobs\ProcessGeneration;
use App\Models\Generation;
use App\Models\Project;
use App\Models\User;
use App\Repositories\GenerationRepository;
use App\Repositories\UsageLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class GenerationService
{
    public function __construct(
        private readonly GenerationRepository $generations,
        private readonly UsageLogRepository $usageLogs,
        private readonly AssetService $assets,
    ) {}

    /**
     * Создаёт генерацию, списывает кредиты и ставит задачу в очередь.
     * Повторный вызов с тем же idempotency_key возвращает существующую запись.
     */
    public function create(User $user, Project $project, CreateGenerationData $data): Generation
    {
        if ($data->idempotencyKey !== null) {
            $existing = $this->generations->findByIdempotencyKey($user, $data->idempotencyKey);
            if ($existing !== null) {
                return $existing;
            }
        }

        $cost = $data->type->creditCost();
        $this->assertCanGenerate($user, $cost);

        $generation = DB::transaction(function () use ($user, $project, $data, $cost): Generation {
            $user->decrement('credits_balance', $cost);
            $user->forceFill(['last_generation_at' => now()])->save();

            return $this->generations->create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'type' => $data->type,
                'status' => GenerationStatus::Queued,
                'prompt' => $data->prompt,
                'negative_prompt' => $data->negativePrompt,
                'params' => $data->params,
                'provider' => config('ai.default'),
                'credits_used' => $cost,
                'idempotency_key' => $data->idempotencyKey,
                'queued_at' => now(),
            ]);
        });

        $this->forgetStats($user);

        ProcessGeneration::dispatch($generation->id);

        return $generation;
    }

    /**
     * @param array{status?: string|null, type?: string|null, project_id?: int|null, search?: string|null} $filters
     * @return LengthAwarePaginator<int, Generation>
     */
    public function paginate(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->generations->paginateForUser($user, $filters, $perPage);
    }

    public function find(User $user, string $uuid): ?Generation
    {
        return $this->generations->findForUser($user, $uuid);
    }

    /**
     * Отмена допустима только до старта обработки провайдером.
     */
    public function cancel(Generation $generation): Generation
    {
        if ($generation->status !== GenerationStatus::Queued) {
            return $generation;
        }

        return DB::transaction(function () use ($generation): Generation {
            $updated = $this->generations->update($generation, [
                'status' => GenerationStatus::Cancelled,
                'finished_at' => now(),
                'error_message' => 'Отменено пользователем',
            ]);

            $this->refund($updated);

            return $updated;
        });
    }

    public function markProcessing(Generation $generation): Generation
    {
        return $this->generations->update($generation, [
            'status' => GenerationStatus::Processing,
            'started_at' => now(),
        ]);
    }

    public function complete(Generation $generation, ProviderResult $result, int $latencyMs): Generation
    {
        $assets = $this->assets->storeProviderResult($generation, $result);

        $completed = DB::transaction(function () use ($generation, $result, $latencyMs, $assets): Generation {
            $updated = $this->generations->update($generation, [
                'status' => GenerationStatus::Completed,
                'provider' => $result->provider,
                'model' => $result->model,
                'result' => [
                    'asset_ids' => array_map(fn ($asset): int => $asset->id, $assets),
                    'count' => count($assets),
                    'meta' => $result->raw,
                ],
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
                'latency_ms' => $latencyMs,
                'finished_at' => now(),
                'error_message' => null,
            ]);

            $this->usageLogs->create([
                'user_id' => $updated->user_id,
                'project_id' => $updated->project_id,
                'generation_id' => $updated->id,
                'type' => $updated->type->value,
                'provider' => $result->provider,
                'credits' => $updated->credits_used,
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
                'cost_micros' => $result->costMicros,
                'occurred_at' => now(),
            ]);

            return $updated;
        });

        $this->forgetStats($generation->user);

        return $completed;
    }

    public function fail(Generation $generation, string $message): Generation
    {
        $failed = DB::transaction(function () use ($generation, $message): Generation {
            $updated = $this->generations->update($generation, [
                'status' => GenerationStatus::Failed,
                'error_message' => Str::limit($message, 900),
                'finished_at' => now(),
            ]);

            $this->refund($updated);

            return $updated;
        });

        $this->forgetStats($generation->user);

        return $failed;
    }

    /**
     * Возврат кредитов при отмене или окончательной ошибке.
     */
    private function refund(Generation $generation): void
    {
        $generation->user()->increment('credits_balance', $generation->credits_used);

        Log::info('Кредиты возвращены за генерацию', [
            'generation' => $generation->uuid,
            'credits' => $generation->credits_used,
        ]);
    }

    private function assertCanGenerate(User $user, int $cost): void
    {
        $running = $this->generations->countRunning($user);
        $limit = $user->plan->concurrentGenerations();

        if ($running >= $limit) {
            throw new ConcurrencyLimitException($limit);
        }

        if (!$user->hasCreditsFor($cost)) {
            throw new InsufficientCreditsException($cost, $user->credits_balance);
        }
    }

    /**
     * Агрегированная статистика кешируется, список последних генераций — нет:
     * он меняется на каждом запросе и должен быть актуальным.
     *
     * @return array{status_counts: array<string, int>, type_counts: array<string, int>, daily: list<array{date: string, total: int, failed: int}>, recent: Collection<int, Generation>}
     */
    public function stats(User $user): array
    {
        /** @var array{status_counts: array<string, int>, type_counts: array<string, int>, daily: list<array{date: string, total: int, failed: int}>} $aggregates */
        $aggregates = Cache::remember(
            "generations:stats:{$user->id}",
            now()->addMinutes(2),
            fn (): array => [
                'status_counts' => $this->generations->statusCounts($user),
                'type_counts' => $this->generations->typeCounts($user),
                'daily' => $this->generations->dailySeries($user),
            ],
        );

        return [...$aggregates, 'recent' => $this->generations->recentForUser($user)];
    }

    public function forgetStats(User $user): void
    {
        Cache::forget("generations:stats:{$user->id}");
        Cache::forget("projects:stats:{$user->id}");
    }
}
