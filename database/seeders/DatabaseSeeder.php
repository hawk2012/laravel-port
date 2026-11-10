<?php

namespace Database\Seeders;

use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use App\Models\Generation;
use App\Models\Project;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Демо-данные: один пользователь, три проекта, история генераций за две недели.
     * Пароль намеренно простой — это демо-стенд, не продакшен.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'demo@kontur.ai'],
            [
                'name' => 'Михаил Губин',
                'password' => 'demo-password-2026',
                'plan' => 'pro',
                'credits_balance' => 7_450,
            ],
        );

        $projects = collect([
            ['name' => 'Кампания «Осень»', 'description' => 'Баннеры и посты для осеннего запуска продукта.'],
            ['name' => 'Витрина продукта', 'description' => 'Изображения и видео для карточек на сайте.'],
            ['name' => 'Контент для соцсетей', 'description' => 'Тексты и аудио для Telegram и VK.'],
        ])->map(fn (array $data): Project => Project::query()->firstOrCreate(
            ['user_id' => $user->id, 'name' => $data['name']],
            [...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6))],
        ));

        $prompts = [
            GenerationType::Image->value => [
                'неоновый киберпанк-город под дождём, отражения в лужах, 35мм',
                'минималистичный продуктовый кадр на мраморном столе, мягкий свет',
                'абстрактный градиентный фон для баннера, пастельные тона',
            ],
            GenerationType::Text->value => [
                'пост для Telegram о запуске AI-функции, 600 знаков, дружелюбно',
                'описание преимуществ платформы для лендинга, 3 абзаца',
            ],
            GenerationType::Audio->value => [
                'спокойный эмбиент с мягким пэдом для видео-фона',
            ],
            GenerationType::Video->value => [
                'пролёт камеры над горным озером на рассвете, туман, плавно',
            ],
        ];

        if (Generation::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        for ($day = 13; $day >= 0; $day--) {
            $count = random_int(0, 4);

            for ($i = 0; $i < $count; $i++) {
                $type = fake()->randomElement(GenerationType::cases());
                $prompt = fake()->randomElement($prompts[$type->value]);
                $status = fake()->randomElement([
                    GenerationStatus::Completed,
                    GenerationStatus::Completed,
                    GenerationStatus::Completed,
                    GenerationStatus::Failed,
                ]);

                $createdAt = now()->subDays($day)->setTime(random_int(9, 21), random_int(0, 59));
                $latency = $status === GenerationStatus::Completed ? random_int(320, 4200) : null;

                $generation = Generation::query()->create([
                    'project_id' => $projects->random()->id,
                    'user_id' => $user->id,
                    'type' => $type,
                    'status' => $status,
                    'prompt' => $prompt,
                    'params' => ['aspect_ratio' => '1:1'],
                    'provider' => 'stub',
                    'model' => "stub-{$type->value}-v1",
                    'credits_used' => $type->creditCost(),
                    'input_tokens' => random_int(8, 40),
                    'output_tokens' => $type === GenerationType::Text ? random_int(80, 200) : 0,
                    'latency_ms' => $latency,
                    'error_message' => $status === GenerationStatus::Failed ? 'Провайдер вернул ошибку 502' : null,
                    'queued_at' => $createdAt,
                    'started_at' => $createdAt->copy()->addSecond(),
                    'finished_at' => $createdAt->copy()->addSeconds((int) ceil(($latency ?? 1000) / 1000)),
                ]);

                $generation->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

                if ($status === GenerationStatus::Completed) {
                    UsageLog::query()->create([
                        'user_id' => $user->id,
                        'project_id' => $generation->project_id,
                        'generation_id' => $generation->id,
                        'type' => $type->value,
                        'provider' => 'stub',
                        'credits' => $type->creditCost(),
                        'input_tokens' => $generation->input_tokens,
                        'output_tokens' => $generation->output_tokens,
                        'cost_micros' => $type->creditCost() * 1_000,
                        'occurred_at' => $createdAt,
                    ]);
                }
            }
        }
    }
}
