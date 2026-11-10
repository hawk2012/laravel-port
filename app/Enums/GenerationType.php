<?php

namespace App\Enums;

enum GenerationType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Изображение',
            self::Video => 'Видео',
            self::Audio => 'Аудио',
            self::Text => 'Текст',
        };
    }

    public function creditCost(): int
    {
        return match ($this) {
            self::Image => 5,
            self::Video => 40,
            self::Audio => 10,
            self::Text => 1,
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
