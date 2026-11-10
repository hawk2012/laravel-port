<?php

namespace App\Enums;

enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Scale = 'scale';

    public function monthlyCredits(): int
    {
        return match ($this) {
            self::Free => 500,
            self::Pro => 10_000,
            self::Scale => 100_000,
        };
    }

    /**
     * Максимальное число одновременных генераций в очереди.
     */
    public function concurrentGenerations(): int
    {
        return match ($this) {
            self::Free => 2,
            self::Pro => 8,
            self::Scale => 32,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Pro => 'Pro',
            self::Scale => 'Scale',
        };
    }
}
