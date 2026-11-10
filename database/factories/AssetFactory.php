<?php

namespace Database\Factories;

use App\Enums\AssetKind;
use App\Models\Asset;
use App\Models\Generation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::factory();

        return [
            'uuid' => (string) Str::uuid(),
            'generation_id' => null,
            'project_id' => $project,
            'user_id' => User::factory(),
            'kind' => AssetKind::Image,
            'mime_type' => 'image/png',
            'disk' => 'local',
            'path' => 'generations/'.Str::uuid().'.png',
            'checksum' => hash('sha256', Str::uuid()->toString()),
            'size_bytes' => fake()->numberBetween(20_000, 900_000),
            'width' => 1024,
            'height' => 1024,
            'duration_ms' => null,
            'meta' => [],
        ];
    }

    public function forGeneration(Generation $generation): static
    {
        return $this->state(fn (): array => [
            'generation_id' => $generation->id,
            'project_id' => $generation->project_id,
            'user_id' => $generation->user_id,
            'kind' => AssetKind::fromGenerationType($generation->type),
        ]);
    }
}
