<?php

namespace App\Services\Ai\Providers;

use App\Contracts\AiProvider;
use App\DTO\CreateGenerationData;
use App\DTO\ProviderAsset;
use App\DTO\ProviderResult;
use App\Enums\AssetKind;
use App\Enums\GenerationType;
use App\Exceptions\ProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Провайдер для OpenAI-совместимых API (OpenAI, OpenRouter, YandexGPT-совместимые шлюзы).
 * Сетевые ошибки и 5xx помечаются как retryable — задача уйдёт на повтор в очереди.
 */
final class OpenAiCompatibleProvider implements AiProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $imageModel,
        private readonly string $textModel,
        private readonly int $timeout = 60,
    ) {}

    public function name(): string
    {
        return 'openai-compatible';
    }

    public function modelFor(CreateGenerationData $data): string
    {
        return match ($data->type) {
            GenerationType::Image => $this->imageModel,
            GenerationType::Text => $this->textModel,
            default => throw ProviderException::rejected("тип {$data->type->value} не поддерживается HTTP-провайдером"),
        };
    }

    public function generate(CreateGenerationData $data): ProviderResult
    {
        return match ($data->type) {
            GenerationType::Image => $this->generateImage($data),
            GenerationType::Text => $this->generateText($data),
            default => throw ProviderException::rejected("тип {$data->type->value} не поддерживается HTTP-провайдером"),
        };
    }

    private function generateImage(CreateGenerationData $data): ProviderResult
    {
        $payload = [
            'model' => $this->imageModel,
            'prompt' => $data->prompt,
            'size' => $data->params['size'] ?? '1024x1024',
            'n' => 1,
        ];

        $response = $this->post('/images/generations', $payload);

        $first = $response['data'][0] ?? null;
        if (!is_array($first)) {
            throw ProviderException::unreachable('ответ без поля data[0]');
        }

        $binary = $this->resolveImagePayload($first);

        return new ProviderResult(
            provider: $this->name(),
            model: $this->imageModel,
            assets: [new ProviderAsset(kind: AssetKind::Image, mimeType: 'image/png', binary: $binary)],
            inputTokens: 0,
            outputTokens: 0,
            costMicros: 0,
            raw: ['revised_prompt' => $first['revised_prompt'] ?? null],
        );
    }

    private function generateText(CreateGenerationData $data): ProviderResult
    {
        $payload = [
            'model' => $this->textModel,
            'messages' => [
                ['role' => 'system', 'content' => 'Ты помощник по генерации маркетингового контента.'],
                ['role' => 'user', 'content' => $data->prompt],
            ],
            'temperature' => (float) ($data->params['temperature'] ?? 0.7),
        ];

        $response = $this->post('/chat/completions', $payload);

        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            throw ProviderException::unreachable('ответ без текста');
        }

        return new ProviderResult(
            provider: $this->name(),
            model: $this->textModel,
            assets: [new ProviderAsset(kind: AssetKind::Text, mimeType: 'text/plain', text: $content)],
            inputTokens: (int) ($response['usage']['prompt_tokens'] ?? 0),
            outputTokens: (int) ($response['usage']['completion_tokens'] ?? 0),
            costMicros: 0,
            raw: ['finish_reason' => $response['choices'][0]['finish_reason'] ?? null],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(string $path, array $payload): Response
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(2, 300, throw: false)
                ->post(rtrim($this->baseUrl, '/').$path, $payload);
        } catch (ConnectionException $e) {
            throw ProviderException::unreachable($e->getMessage(), $e);
        }

        if ($response->serverError()) {
            throw ProviderException::unreachable("HTTP {$response->status()}");
        }

        if ($response->failed()) {
            Log::warning('AI provider rejected request', [
                'provider' => $this->name(),
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            throw ProviderException::rejected("HTTP {$response->status()}: ".mb_substr($response->body(), 0, 200));
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveImagePayload(array $payload): string
    {
        if (isset($payload['b64_json']) && is_string($payload['b64_json'])) {
            $decoded = base64_decode($payload['b64_json'], true);
            if ($decoded === false) {
                throw ProviderException::unreachable('некорректный base64 в ответе');
            }

            return $decoded;
        }

        if (isset($payload['url']) && is_string($payload['url'])) {
            try {
                $file = Http::timeout($this->timeout)->get($payload['url']);
            } catch (Throwable $e) {
                throw ProviderException::unreachable('не удалось скачать изображение', $e);
            }

            if ($file->failed()) {
                throw ProviderException::unreachable("скачивание изображения: HTTP {$file->status()}");
            }

            return $file->body();
        }

        throw ProviderException::unreachable('ответ не содержит b64_json или url');
    }
}
