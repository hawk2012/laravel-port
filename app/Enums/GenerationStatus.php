<?php

namespace App\Enums;

enum GenerationStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Cancelled => true,
            default => false,
        };
    }

    public function isRunning(): bool
    {
        return $this === self::Queued || $this === self::Processing;
    }

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'В очереди',
            self::Processing => 'Генерация',
            self::Completed => 'Готово',
            self::Failed => 'Ошибка',
            self::Cancelled => 'Отменено',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
