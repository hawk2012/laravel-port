<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\UsageLogRepository;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function __construct(
        private readonly UsageLogRepository $usage,
        private readonly ProjectService $projects,
    ) {}

    /**
     * Сводка по использованию за период + остаток кредитов.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $user = $request->user();

        return response()->json([
            'data' => [
                'user' => UserResource::make($user)->resolve(),
                'period_days' => $days,
                'summary' => $this->usage->summaryForUser($user, now()->subDays($days))->toArray(),
                'projects' => $this->projects->stats($user),
                'daily_credits' => $this->usage->dailyCredits($user, min($days, 30)),
            ],
        ]);
    }
}
