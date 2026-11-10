<?php

namespace App\Http\Resources;

use App\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Generation
 */
class GenerationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'prompt' => $this->prompt,
            'negative_prompt' => $this->negative_prompt,
            // Пустой массив в PHP сериализуется как [], а контракт обещает объект.
            'params' => (object) ($this->params ?? []),
            'provider' => $this->provider,
            'model' => $this->model,
            'credits_used' => $this->credits_used,
            'input_tokens' => $this->input_tokens,
            'output_tokens' => $this->output_tokens,
            'latency_ms' => $this->latency_ms,
            'error_message' => $this->error_message,
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'name' => $this->project->name,
                'slug' => $this->project->slug,
            ]),
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'queued_at' => $this->queued_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
