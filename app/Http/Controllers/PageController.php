<?php

namespace App\Http\Controllers;

use App\Enums\AssetKind;
use App\Enums\GenerationType;
use App\Http\Resources\AssetResource;
use App\Http\Resources\GenerationResource;
use App\Http\Resources\ProjectResource;
use App\Models\Asset;
use App\Repositories\AssetRepository;
use App\Repositories\GenerationRepository;
use App\Repositories\UsageLogRepository;
use App\Services\AssetService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly GenerationRepository $generations,
        private readonly AssetRepository $assets,
        private readonly AssetService $assetService,
        private readonly ProjectService $projects,
    ) {}

    public function studio(Request $request): Response
    {
        $user = $request->user();
        $recent = $this->generations->recentForUser($user, 8);

        return Inertia::render('Studio', [
            'projects' => ProjectResource::collection(
                $this->projects->paginate($user, ['status' => 'active'], 50),
            )->resolve(),
            'recent' => GenerationResource::collection($recent)->resolve(),
            'typeOptions' => GenerationType::values(),
        ]);
    }

    public function history(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = $this->generations->paginateForUser($request->user(), $validated, 12);

        return Inertia::render('History', [
            'generations' => GenerationResource::collection($paginator)->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => $validated,
            'typeOptions' => GenerationType::values(),
        ]);
    }

    public function library(Request $request): Response
    {
        $validated = $request->validate([
            'kind' => ['nullable', 'string'],
            'project_id' => ['nullable', 'integer'],
        ]);

        $paginator = $this->assets->paginateForUser($request->user(), $validated, 18);

        return Inertia::render('Library', [
            'assets' => $this->mapAssets($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => $validated,
            'kindOptions' => AssetKind::values(),
        ]);
    }

    public function projects(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $paginator = $this->projects->paginate($request->user(), $validated, 12);

        return Inertia::render('Projects', [
            'projects' => ProjectResource::collection($paginator)->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => $validated,
            'stats' => $this->projects->stats($request->user()),
        ]);
    }

    public function usage(Request $request): Response
    {
        $days = (int) $request->integer('days', 30);
        $days = max(1, min($days, 365));

        $summary = app(UsageLogRepository::class)->summaryForUser($request->user(), now()->subDays($days));

        return Inertia::render('Usage', [
            'days' => $days,
            'summary' => $summary->toArray(),
            'daily' => app(UsageLogRepository::class)->dailyCredits($request->user(), min($days, 30)),
            'projects' => $this->projects->stats($request->user()),
        ]);
    }

    /**
     * Добавляет к ассетам URL предпросмотра и текст, чтобы страница рендерилась
     * целиком на сервере без дополнительных запросов.
     *
     * @param array<int, Asset> $items
     * @return array<int, array<string, mixed>>
     */
    private function mapAssets(array $items): array
    {
        return array_map(function (Asset $asset): array {
            $data = (new AssetResource($asset))->resolve();
            $data['preview_url'] = $asset->kind === AssetKind::Text ? null : route('media.show', $asset->uuid);
            $data['text'] = $this->assetService->textContent($asset);

            return $data;
        }, $items);
    }
}
