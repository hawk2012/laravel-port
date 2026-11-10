<?php

namespace App\DTO;

use App\Enums\AssetKind;

/**
 * Один сгенерированный артефакт: файл или текстовый блок.
 */
final readonly class ProviderAsset
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public AssetKind $kind,
        public string $mimeType,
        public ?string $binary = null,
        public ?string $text = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $durationMs = null,
        public array $meta = [],
    ) {}

    public function extension(): string
    {
        return match ($this->mimeType) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            default => 'txt',
        };
    }
}
