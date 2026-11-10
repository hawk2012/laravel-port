<?php

namespace Database\Factories;

use App\Enums\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Дублируем дефолты схемы: без них модель после create() держит null
            // в plan/credits_balance и падает на $user->plan->...
            'plan' => Plan::Free,
            'credits_balance' => (int) config('billing.default_credits'),
        ];
    }

    public function plan(Plan $plan): static
    {
        return $this->state(fn (): array => ['plan' => $plan]);
    }

    public function withCredits(int $credits): static
    {
        return $this->state(fn (): array => ['credits_balance' => $credits]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
