<?php

namespace App\Services\Ai\Providers;

use App\Contracts\AiProvider;
use App\DTO\CreateGenerationData;
use App\DTO\ProviderAsset;
use App\DTO\ProviderResult;
use App\Enums\AssetKind;
use App\Enums\GenerationType;
use App\Exceptions\ProviderException;

/**
 * Локальный провайдер для демонстрации и тестов: не требует внешних ключей,
 * но отдаёт валидные артефакты (PNG, WAV-подобный поток, текст), поэтому
 * весь пайплайн — очередь, сторедж, usage-логи — работает по-настоящему.
 */
final class StubProvider implements AiProvider
{
    public function name(): string
    {
        return 'stub';
    }

    public function modelFor(CreateGenerationData $data): string
    {
        return match ($data->type) {
            GenerationType::Image => 'stub-image-v1',
            GenerationType::Video => 'stub-video-v1',
            GenerationType::Audio => 'stub-audio-v1',
            GenerationType::Text => 'stub-text-v1',
        };
    }

    public function generate(CreateGenerationData $data): ProviderResult
    {
        if (trim($data->prompt) === '') {
            throw ProviderException::rejected('пустой промпт');
        }

        $seed = crc32($data->prompt.'|'.$data->type->value);

        return new ProviderResult(
            provider: $this->name(),
            model: $this->modelFor($data),
            assets: [$this->assetFor($data, $seed)],
            inputTokens: max(1, (int) ceil(mb_strlen($data->prompt) / 4)),
            outputTokens: $data->type === GenerationType::Text ? 128 : 0,
            costMicros: $data->type->creditCost() * 1_000,
            raw: ['seed' => $seed, 'stub' => true],
        );
    }

    private function assetFor(CreateGenerationData $data, int $seed): ProviderAsset
    {
        return match ($data->type) {
            GenerationType::Image => $this->imageAsset($seed),
            GenerationType::Video => $this->videoAsset($seed),
            GenerationType::Audio => $this->audioAsset($seed),
            GenerationType::Text => $this->textAsset($data),
        };
    }

    private function imageAsset(int $seed): ProviderAsset
    {
        [$width, $height] = $this->dimensions($seed);

        if (!function_exists('imagecreatetruecolor')) {
            return new ProviderAsset(
                kind: AssetKind::Image,
                mimeType: 'image/svg+xml',
                binary: $this->svg($seed, $width, $height),
                width: $width,
                height: $height,
            );
        }

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw ProviderException::unreachable('не удалось создать холст GD');
        }

        $from = $this->paletteColor($seed);
        $to = $this->paletteColor($seed >> 7);

        for ($y = 0; $y < $height; $y++) {
            $ratio = $height > 1 ? $y / ($height - 1) : 0;
            $color = imagecolorallocate(
                $image,
                (int) round($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) round($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) round($from[2] + ($to[2] - $from[2]) * $ratio),
            );
            imagefilledrectangle($image, 0, $y, $width, $y, $color);
        }

        mt_srand($seed);
        for ($i = 0; $i < 28; $i++) {
            $radius = mt_rand(20, (int) ($width / 3));
            $cx = mt_rand(0, $width);
            $cy = mt_rand(0, $height);
            $tint = $this->paletteColor(mt_rand(0, 0xFFFFFF));
            $color = imagecolorallocatealpha($image, $tint[0], $tint[1], $tint[2], 96);
            imagefilledellipse($image, $cx, $cy, $radius, $radius, $color);
        }
        mt_srand();

        ob_start();
        imagepng($image, null, 6);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return new ProviderAsset(
            kind: AssetKind::Image,
            mimeType: 'image/png',
            binary: $binary,
            width: $width,
            height: $height,
        );
    }

    private function videoAsset(int $seed): ProviderAsset
    {
        // Заглушка: валидный MP4-контейнер не генерируем, отдаём постер + метаданные.
        $poster = $this->imageAsset($seed);

        return new ProviderAsset(
            kind: AssetKind::Video,
            mimeType: 'image/png',
            binary: $poster->binary,
            width: $poster->width,
            height: $poster->height,
            durationMs: 4000,
            meta: ['note' => 'stub poster frame', 'frames' => 96],
        );
    }

    private function audioAsset(int $seed): ProviderAsset
    {
        $samples = 8000;
        $wav = $this->wavHeader($samples);

        mt_srand($seed);
        $frequency = mt_rand(220, 660);
        for ($i = 0; $i < $samples; $i++) {
            $value = (int) (sin(2 * M_PI * $frequency * $i / 8000) * 12000);
            $wav .= pack('v', $value < 0 ? $value + 65536 : $value);
        }
        mt_srand();

        return new ProviderAsset(
            kind: AssetKind::Audio,
            mimeType: 'audio/wav',
            binary: $wav,
            durationMs: 1000,
            meta: ['frequency_hz' => $frequency],
        );
    }

    private function textAsset(CreateGenerationData $data): ProviderAsset
    {
        $text = "Сгенерированный текст по промпту «{$data->prompt}».\n\n"
            .'Это демонстрационный ответ stub-провайдера. В продакшене здесь был бы ответ '
            .'LLM (OpenAI / Anthropic / YandexGPT) — контракт AiProvider от этого не меняется.';

        return new ProviderAsset(
            kind: AssetKind::Text,
            mimeType: 'text/plain',
            text: $text,
        );
    }

    /**
     * @return array{0:int,1:int}
     */
    private function dimensions(int $seed): array
    {
        return match ($seed % 3) {
            0 => [1024, 1024],
            1 => [1024, 576],
            default => [576, 1024],
        };
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function paletteColor(int $seed): array
    {
        $hue = ($seed % 360) / 360;

        return $this->hsvToRgb($hue, 0.62, 0.92);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function hsvToRgb(float $h, float $s, float $v): array
    {
        $i = (int) floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        [$r, $g, $b] = match ($i % 6) {
            0 => [$v, $t, $p],
            1 => [$q, $v, $p],
            2 => [$p, $v, $t],
            3 => [$p, $q, $v],
            4 => [$t, $p, $v],
            default => [$v, $p, $q],
        };

        return [(int) round($r * 255), (int) round($g * 255), (int) round($b * 255)];
    }

    private function svg(int $seed, int $width, int $height): string
    {
        [$r1, $g1, $b1] = $this->paletteColor($seed);
        [$r2, $g2, $b2] = $this->paletteColor($seed >> 7);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d">'
            .'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0" stop-color="rgb(%3$d,%4$d,%5$d)"/>'
            .'<stop offset="1" stop-color="rgb(%6$d,%7$d,%8$d)"/></linearGradient></defs>'
            .'<rect width="100%%" height="100%%" fill="url(#g)"/></svg>',
            $width,
            $height,
            $r1,
            $g1,
            $b1,
            $r2,
            $g2,
            $b2,
        );
    }

    private function wavHeader(int $samples): string
    {
        $dataSize = $samples * 2;

        return 'RIFF'.pack('V', 36 + $dataSize).'WAVE'
            .'fmt '.pack('V', 16).pack('v', 1).pack('v', 1)
            .pack('V', 8000).pack('V', 16000).pack('v', 2).pack('v', 16)
            .'data'.pack('V', $dataSize);
    }
}
