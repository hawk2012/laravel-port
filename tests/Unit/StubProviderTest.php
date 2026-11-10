<?php

namespace Tests\Unit;

use App\DTO\CreateGenerationData;
use App\DTO\ProviderAsset;
use App\DTO\ProviderResult;
use App\Enums\AssetKind;
use App\Enums\GenerationType;
use App\Exceptions\ProviderException;
use App\Services\Ai\Providers\StubProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StubProviderTest extends TestCase
{
    private StubProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new StubProvider;
    }

    private function data(GenerationType $type, string $prompt = 'неоновый город под дождём'): CreateGenerationData
    {
        return new CreateGenerationData(projectId: 1, type: $type, prompt: $prompt);
    }

    #[Test]
    public function it_returns_png_for_image_type(): void
    {
        $result = $this->provider->generate($this->data(GenerationType::Image));

        $this->assertInstanceOf(ProviderResult::class, $result);
        $this->assertSame('stub', $result->provider);
        $this->assertSame('stub-image-v1', $result->model);
        $this->assertCount(1, $result->assets);

        $asset = $result->assets[0];
        $this->assertSame('image/png', $asset->mimeType);
        $this->assertNotNull($asset->binary);
        $this->assertStringStartsWith("\x89PNG", $asset->binary);
        $this->assertGreaterThan(0, $asset->width);
        $this->assertGreaterThan(0, $asset->height);
    }

    #[Test]
    public function it_returns_text_with_prompt_for_text_type(): void
    {
        $result = $this->provider->generate($this->data(GenerationType::Text, 'пост про запуск продукта'));

        $asset = $result->assets[0];
        $this->assertSame('text/plain', $asset->mimeType);
        $this->assertStringContainsString('пост про запуск продукта', (string) $asset->text);
        $this->assertGreaterThan(0, $result->outputTokens);
    }

    #[Test]
    public function it_returns_riff_wav_for_audio_type(): void
    {
        $asset = $this->provider->generate($this->data(GenerationType::Audio))->assets[0];

        $this->assertSame('audio/wav', $asset->mimeType);
        $this->assertStringStartsWith('RIFF', (string) $asset->binary);
        $this->assertSame(1000, $asset->durationMs);
    }

    #[Test]
    public function it_returns_poster_and_duration_for_video_type(): void
    {
        $asset = $this->provider->generate($this->data(GenerationType::Video))->assets[0];

        $this->assertSame('video', $asset->kind->value);
        $this->assertSame(4000, $asset->durationMs);
        $this->assertNotNull($asset->binary);
    }

    #[Test]
    public function it_rejects_blank_prompt(): void
    {
        $this->expectException(ProviderException::class);

        $this->provider->generate($this->data(GenerationType::Image, '   '));
    }

    #[Test]
    public function it_reports_input_tokens_proportional_to_prompt_length(): void
    {
        $short = $this->provider->generate($this->data(GenerationType::Image, 'кот'));
        $long = $this->provider->generate($this->data(GenerationType::Image, str_repeat('длинный промпт ', 20)));

        $this->assertGreaterThan($short->inputTokens, $long->inputTokens);
    }

    #[Test]
    public function assets_are_deterministic_for_same_prompt(): void
    {
        $first = $this->provider->generate($this->data(GenerationType::Image))->assets[0];
        $second = $this->provider->generate($this->data(GenerationType::Image))->assets[0];

        $this->assertSame($first->binary, $second->binary);
    }

    #[Test]
    public function provider_asset_maps_mime_to_extension(): void
    {
        $this->assertSame('png', (new ProviderAsset(kind: AssetKind::Image, mimeType: 'image/png'))->extension());
        $this->assertSame('mp4', (new ProviderAsset(kind: AssetKind::Video, mimeType: 'video/mp4'))->extension());
        $this->assertSame('txt', (new ProviderAsset(kind: AssetKind::Text, mimeType: 'text/plain'))->extension());
    }
}
