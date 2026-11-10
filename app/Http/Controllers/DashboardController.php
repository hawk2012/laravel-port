<?php

namespace App\Http\Controllers;

use App\DTO\CreateGenerationData;
use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use App\Http\Resources\GenerationResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Repositories\UsageLogRepository;
use App\Services\GenerationService;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GenerationService $generations,
        private readonly ProjectService $projects,
    ) {}

    /**
     * Дашборд: агрегаты за 30 дней + последние генерации + быстрый запуск.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $stats = $this->generations->stats($user);
        $usage = app(UsageLogRepository::class)->summaryForUser($user, now()->subDays(30));

        return Inertia::render('Dashboard', [
            'stats' => [
                'status_counts' => $stats['status_counts'],
                'type_counts' => $stats['type_counts'],
                'daily' => $stats['daily'],
                'recent' => GenerationResource::collection($stats['recent'])->resolve(),
                'project_stats' => $this->projects->stats($user),
                'usage' => $usage->toArray(),
            ],
            'projects' => ProjectResource::collection(
                $this->projects->paginate($user, ['status' => 'active'], 50),
            )->resolve(),
            'typeOptions' => GenerationType::values(),
            'statusOptions' => GenerationStatus::values(),
        ]);
    }

    /**
     * Быстрый запуск генерации из дашборда (Inertia-форма).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'type' => ['required', 'string', 'in:'.implode(',', GenerationType::values())],
            'prompt' => ['required', 'string', 'min:3', 'max:'.config('ai.max_prompt_length', 2000)],
        ]);

        $user = $request->user();
        $project = Project::query()->ownedBy($user)->findOrFail($validated['project_id']);

        $this->generations->create(
            $user,
            $project,
            CreateGenerationData::fromArray($validated, $project->id),
        );

        return back()->with('success', 'Генерация запущена — результат появится в истории.');
    }

    /**
     * Публичная страница-презентация.
     */
    public function landing(): Response
    {
        return Inertia::render('Landing', [
            'stats' => [
                'types' => array_map(fn (GenerationType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'credits' => $type->creditCost(),
                ], GenerationType::cases()),
            ],
        ]);
    }
}
