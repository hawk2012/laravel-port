<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return ProjectResource::collection(
            $this->projects->paginate($request->user(), $validated, (int) ($validated['per_page'] ?? 15)),
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->user(), $request->validated());

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Project $project): ProjectResource
    {
        $this->authorizeOwner($request, $project);

        return ProjectResource::make($project->loadCount('generations'));
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorizeOwner($request, $project);

        return ProjectResource::make($this->projects->update($project, $request->validated()));
    }

    public function destroy(Request $request, Project $project): Response
    {
        $this->authorizeOwner($request, $project);

        $this->projects->delete($project);

        return response()->noContent();
    }

    private function authorizeOwner(Request $request, Project $project): void
    {
        abort_unless($project->user_id === $request->user()->id, Response::HTTP_FORBIDDEN, 'Проект недоступен.');
    }
}
