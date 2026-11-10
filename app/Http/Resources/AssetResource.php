<?php

namespace App\Http\Resources;

use App\Enums\AssetKind;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Asset
 */
class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isApi = $request->is('api/*');

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'kind' => $this->kind->value,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'width' => $this->width,
            'height' => $this->height,
            'duration_ms' => $this->duration_ms,
            'meta' => $this->meta,
            // Inertia-страницы получают готовый URL предпросмотра, API — отдельный endpoint.
            'preview_url' => $this->when(
                !$isApi && $this->kind !== AssetKind::Text,
                fn (): string => route('media.show', $this->uuid),
            ),
            'text' => $this->when(
                !$isApi && $this->kind === AssetKind::Text,
                fn (): ?string => app(AssetService::class)->textContent($this->resource),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
