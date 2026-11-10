<?php

namespace Database\Factories;

use App\Enums\GenerationType;
use App\Models\Generation;
use App\Models\Project;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageLog>
 */
class UsageLogFactory extends Factory
{
    protected $model = UsageLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'generation_id' => Generation::factory(),
            'type' => GenerationType::Image,
            'provider' => 'stub',
            'credits' => 5,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost_micros' => fake()->numberBetween(1000, 50_000),
            'occurred_at' => now(),
        ];
    }
}
