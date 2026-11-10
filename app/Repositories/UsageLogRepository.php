<?php

namespace App\Repositories;

use App\DTO\UsageSummary;
use App\Enums\GenerationStatus;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UsageLogRepository
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): UsageLog
    {
        return UsageLog::query()->create($attributes);
    }

    /**
     * Агрегированная статистика за период.
     *
     * Счётчики генераций берутся из generations (там есть и неуспешные),
     * а кредиты и токены — из usage_logs (пишутся только по факту списания).
     * Так «всего» всегда равно completed + failed + cancelled и не расходится.
     */
    public function summaryForUser(User $user, \DateTimeInterface $from): UsageSummary
    {
        $row = UsageLog::query()
            ->where('user_id', $user->id)
            ->where('occurred_at', '>=', $from)
            ->toBase()
            ->selectRaw('COALESCE(SUM(credits), 0) as credits')
            ->selectRaw('COALESCE(SUM(input_tokens), 0) as input_tokens')
            ->selectRaw('COALESCE(SUM(output_tokens), 0) as output_tokens')
            ->first();

        $byType = UsageLog::query()
            ->where('user_id', $user->id)
            ->where('occurred_at', '>=', $from)
            ->toBase()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        $statusCounts = $user->generations()
            ->where('created_at', '>=', $from)
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        $avgLatency = (int) round((float) $user->generations()
            ->where('created_at', '>=', $from)
            ->whereNotNull('latency_ms')
            ->avg('latency_ms'));

        return new UsageSummary(
            generations: array_sum($statusCounts),
            completed: $statusCounts[GenerationStatus::Completed->value] ?? 0,
            failed: $statusCounts[GenerationStatus::Failed->value] ?? 0,
            creditsSpent: (int) ($row->credits ?? 0),
            inputTokens: (int) ($row->input_tokens ?? 0),
            outputTokens: (int) ($row->output_tokens ?? 0),
            avgLatencyMs: $avgLatency,
            byType: $byType,
        );
    }

    /**
     * Расход кредитов по дням.
     *
     * @return list<array{date: string, credits: int}>
     */
    public function dailyCredits(User $user, int $days = 14): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = UsageLog::query()
            ->where('user_id', $user->id)
            ->where('occurred_at', '>=', $from)
            ->toBase()
            ->selectRaw('DATE(occurred_at) as day, COALESCE(SUM(credits), 0) as credits')
            ->groupBy(DB::raw('DATE(occurred_at)'))
            ->pluck('credits', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $series[] = [
                'date' => $day,
                'credits' => (int) ($rows[$day] ?? 0),
            ];
        }

        return $series;
    }
}
