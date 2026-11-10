<?php

namespace App\Providers;

use App\Services\Ai\AiProviderManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiProviderManager::class, function (): AiProviderManager {
            return new AiProviderManager((string) config('ai.default', 'stub'));
        });
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Лимиты по смыслу, а не «один на всех»: auth защищён от брутфорса,
     * генерации — от случайных штормов, остальной API — общий бюджет.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request): Limit {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(6)->by($key);
        });

        RateLimiter::for('generations', function (Request $request): Limit {
            $user = $request->user();

            if ($user === null) {
                return Limit::perMinute(10)->by($request->ip());
            }

            $perMinute = match ($user->plan->value) {
                'scale' => 60,
                'pro' => 20,
                default => 6,
            };

            return Limit::perMinute($perMinute)->by("generations:{$user->id}");
        });

        RateLimiter::for('api', function (Request $request): Limit {
            /** @var int|null $userId */
            $userId = $request->user()?->getKey();

            return Limit::perMinute(120)->by($userId ?? $request->ip());
        });
    }
}
