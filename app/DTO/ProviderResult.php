<?php

namespace App\DTO;

/**
 * Нормализованный результат от AI-провайдера.
 */
final readonly class ProviderResult
{
    /**
     * @param list<ProviderAsset> $assets
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $provider,
        public string $model,
        public array $assets,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public int $costMicros = 0,
        public array $raw = [],
    ) {}
}
