<?php

namespace Database\Factories;

use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use App\Models\Generation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    protected $model = Generation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(GenerationType::cases());

        return [
            'uuid' => (string) Str::uuid(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'type' => $type,
            'status' => GenerationStatus::Completed,
            'prompt' => fake()->sentence(10),
            'negative_prompt' => null,
            'params' => ['aspect_ratio' => '1:1'],
            'provider' => 'stub',
            'model' => 'stub-v1',
            'result' => null,
            'credits_used' => $type->creditCost(),
            'input_tokens' => 0,
            'output_tokens' => 0,
            'latency_ms' => fake()->numberBetween(300, 4000),
            'queued_at' => now()->subMinutes(2),
            'started_at' => now()->subMinutes(2),
            'finished_at' => now()->subMinute(),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'project_id' => $project->id,
            'user_id' => $project->user_id,
        ]);
    }

    public function queued(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Queued,
            'started_at' => null,
            'finished_at' => null,
            'latency_ms' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Failed,
            'error_message' => 'Провайдер вернул ошибку',
            'finished_at' => now(),
        ]);
    }
}
