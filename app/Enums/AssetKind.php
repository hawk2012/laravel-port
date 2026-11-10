<?php

namespace App\Enums;

enum AssetKind: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Text = 'text';

    public static function fromGenerationType(GenerationType $type): self
    {
        return match ($type) {
            GenerationType::Image => self::Image,
            GenerationType::Video => self::Video,
            GenerationType::Audio => self::Audio,
            GenerationType::Text => self::Text,
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
