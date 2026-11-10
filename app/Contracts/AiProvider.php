<?php

namespace App\Contracts;

use App\DTO\CreateGenerationData;
use App\DTO\ProviderResult;

interface AiProvider
{
    /**
     * Имя провайдера для логов и usage-логов.
     */
    public function name(): string;

    /**
     * Модель по умолчанию для типа генерации.
     */
    public function modelFor(CreateGenerationData $data): string;

    /**
     * Синхронный вызов провайдера. Может бросить ProviderException.
     */
    public function generate(CreateGenerationData $data): ProviderResult;
}
