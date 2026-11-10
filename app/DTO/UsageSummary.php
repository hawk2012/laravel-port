<?php

namespace App\DTO;

/**
 * Агрегированная статистика использования за период.
 */
final readonly class UsageSummary
{
    /**
     * @param array<string, int> $byType
     */
    public function __construct(
        public int $generations,
        public int $completed,
        public int $failed,
        public int $creditsSpent,
        public int $inputTokens,
        public int $outputTokens,
        public int $avgLatencyMs,
        public array $byType,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'generations' => $this->generations,
            'completed' => $this->completed,
            'failed' => $this->failed,
            'credits_spent' => $this->creditsSpent,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'avg_latency_ms' => $this->avgLatencyMs,
            'by_type' => $this->byType,
        ];
    }
}
