<?php

namespace App\Services;

use App\DTO\ProviderResult;
use App\Models\Asset;
use App\Models\Generation;
use App\Repositories\AssetRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class AssetService
{
    public function __construct(private readonly AssetRepository $assets) {}

    /**
     * Сохраняет артефакты провайдера на диск и создаёт записи в БД.
     *
     * @return list<Asset>
     */
    public function storeProviderResult(Generation $generation, ProviderResult $result): array
    {
        $disk = (string) config('filesystems.assets_disk', 'local');
        $stored = [];

        foreach ($result->assets as $index => $providerAsset) {
            $payload = $providerAsset->text ?? $providerAsset->binary;
            if ($payload === null) {
                continue;
            }

            $path = sprintf(
                'generations/%d/%s-%d.%s',
                $generation->project_id,
                $generation->uuid,
                $index,
                $providerAsset->extension(),
            );

            if ($providerAsset->text !== null) {
                Storage::disk($disk)->put($path, $providerAsset->text);
            } else {
                Storage::disk($disk)->put($path, $providerAsset->binary);
            }

            $stored[] = $this->assets->create([
                'generation_id' => $generation->id,
                'project_id' => $generation->project_id,
                'user_id' => $generation->user_id,
                'kind' => $providerAsset->kind->value,
                'mime_type' => $providerAsset->mimeType,
                'disk' => $disk,
                'path' => $path,
                'checksum' => hash('sha256', $payload),
                'size_bytes' => strlen($payload),
                'width' => $providerAsset->width,
                'height' => $providerAsset->height,
                'duration_ms' => $providerAsset->durationMs,
                'meta' => $providerAsset->meta,
            ]);
        }

        return $stored;
    }

    /**
     * Текст результата для отдачи в API (без чтения файла).
     */
    public function textContent(Asset $asset): ?string
    {
        if ($asset->kind->value !== 'text') {
            return null;
        }

        try {
            return Storage::disk($asset->disk)->get($asset->path);
        } catch (Throwable $e) {
            Log::warning('Не удалось прочитать текстовый ассет', [
                'asset' => $asset->uuid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteFile(Asset $asset): void
    {
        if (Storage::disk($asset->disk)->exists($asset->path)) {
            Storage::disk($asset->disk)->delete($asset->path);
        }
    }

    /**
     * @return list<Asset>
     */
    public function forGeneration(Generation $generation): array
    {
        return $generation->assets()->get()->all();
    }
}
