<?php

namespace App\Jobs;

use App\DTO\CreateGenerationData;
use App\Exceptions\ProviderException;
use App\Models\Generation;
use App\Services\Ai\AiProviderManager;
use App\Services\GenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Единственная точка вызова AI-провайдера. Ретраи ограничены:
 * при окончательном провале кредиты возвращаются в GenerationService::fail().
 */
class ProcessGeneration implements ShouldQueue
{
    use Queueable;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $generationId)
    {
        $this->tries = (int) config('ai.retry.tries', 3);
        $this->backoff = (array) config('ai.retry.backoff', [10, 60, 180]);
        $this->onQueue('generations');
    }

    public function handle(AiProviderManager $providers, GenerationService $generations): void
    {
        $generation = Generation::query()->find($this->generationId);

        if ($generation === null) {
            Log::warning('Генерация не найдена в очереди', ['id' => $this->generationId]);

            return;
        }

        // Задачу могли отменить, пока она ждала воркер.
        // Processing допустим — это повторная попытка после retry.
        if ($generation->status->isFinal()) {
            return;
        }

        $generation = $generations->markProcessing($generation);
        $startedAt = microtime(true);

        try {
            $result = $providers->driver($generation->provider)->generate($this->toData($generation));
        } catch (ProviderException $e) {
            if ($e->retryable && $this->attempts() < $this->tries) {
                throw $e;
            }

            $generations->fail($generation, $e->getMessage());

            return;
        } catch (Throwable $e) {
            Log::error('Неожиданная ошибка AI-провайдера', [
                'generation' => $generation->uuid,
                'error' => $e->getMessage(),
            ]);

            $generations->fail($generation, 'Внутренняя ошибка генерации');

            return;
        }

        $generations->complete($generation, $result, (int) round((microtime(true) - $startedAt) * 1000));
    }

    /**
     * Вызывается, когда все попытки исчерпаны на уровне очереди.
     */
    public function failed(?Throwable $exception): void
    {
        $generation = Generation::query()->find($this->generationId);

        if ($generation === null || $generation->status->isFinal()) {
            return;
        }

        app(GenerationService::class)->fail(
            $generation,
            $exception?->getMessage() ?? 'Генерация не удалась после всех попыток',
        );
    }

    private function toData(Generation $generation): CreateGenerationData
    {
        return new CreateGenerationData(
            projectId: $generation->project_id,
            type: $generation->type,
            prompt: $generation->prompt,
            negativePrompt: $generation->negative_prompt,
            params: $generation->params ?? [],
            idempotencyKey: $generation->idempotency_key,
        );
    }
}
