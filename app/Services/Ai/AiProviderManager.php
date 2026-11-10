<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Exceptions\ProviderException;
use App\Services\Ai\Providers\OpenAiCompatibleProvider;
use App\Services\Ai\Providers\StubProvider;

/**
 * Резолвер AI-провайдера по имени из конфига.
 * Позволяет держать несколько провайдеров и переключать их без изменения сервисов.
 */
final class AiProviderManager
{
    /** @var array<string, AiProvider> */
    private array $resolved = [];

    public function __construct(private readonly string $default) {}

    public function default(): AiProvider
    {
        return $this->driver($this->default);
    }

    public function driver(?string $name = null): AiProvider
    {
        $name ??= $this->default;

        return $this->resolved[$name] ??= $this->build($name);
    }

    private function build(string $name): AiProvider
    {
        return match ($name) {
            'stub' => new StubProvider,
            'openai' => new OpenAiCompatibleProvider(
                apiKey: (string) config('ai.providers.openai.api_key'),
                baseUrl: (string) config('ai.providers.openai.base_url'),
                imageModel: (string) config('ai.providers.openai.image_model'),
                textModel: (string) config('ai.providers.openai.text_model'),
                timeout: (int) config('ai.providers.openai.timeout', 60),
            ),
            default => throw ProviderException::rejected("неизвестный AI-провайдер «{$name}»"),
        };
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return ['stub', 'openai'];
    }
}
