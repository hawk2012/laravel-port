<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Проект '.fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->sentence(12),
            'status' => ProjectStatus::Active,
            'settings' => ['default_type' => 'image'],
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => ProjectStatus::Archived]);
    }
}
