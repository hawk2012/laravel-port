<?php

namespace App\DTO;

use App\Enums\GenerationType;

/**
 * Входные данные запроса на генерацию.
 */
final readonly class CreateGenerationData
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public int $projectId,
        public GenerationType $type,
        public string $prompt,
        public ?string $negativePrompt = null,
        public array $params = [],
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromArray(array $validated, int $projectId): self
    {
        return new self(
            projectId: $projectId,
            type: GenerationType::from($validated['type']),
            prompt: trim($validated['prompt']),
            negativePrompt: $validated['negative_prompt'] ?? null,
            params: $validated['params'] ?? [],
            idempotencyKey: $validated['idempotency_key'] ?? null,
        );
    }
}
