<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\CreateGenerationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexGenerationRequest;
use App\Http\Requests\Api\V1\StoreGenerationRequest;
use App\Http\Resources\GenerationResource;
use App\Models\Project;
use App\Services\GenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class GenerationController extends Controller
{
    public function __construct(private readonly GenerationService $generations) {}

    public function index(IndexGenerationRequest $request): AnonymousResourceCollection
    {
        $generations = $this->generations->paginate(
            $request->user(),
            $request->validated(),
            (int) $request->validated('per_page', 15),
        );

        return GenerationResource::collection($generations);
    }

    public function store(StoreGenerationRequest $request, Project $project): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->id, Response::HTTP_FORBIDDEN, 'Проект недоступен.');

        $data = CreateGenerationData::fromArray($request->validated(), $project->id);
        $generation = $this->generations->create($request->user(), $project, $data);

        return GenerationResource::make($generation->load('assets', 'project'))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function show(Request $request, string $uuid): GenerationResource
    {
        $generation = $this->generations->find($request->user(), $uuid);

        abort_if($generation === null, Response::HTTP_NOT_FOUND, 'Генерация не найдена.');

        return GenerationResource::make($generation);
    }

    public function cancel(Request $request, string $uuid): GenerationResource
    {
        $generation = $this->generations->find($request->user(), $uuid);

        abort_if($generation === null, Response::HTTP_NOT_FOUND, 'Генерация не найдена.');

        return GenerationResource::make($this->generations->cancel($generation));
    }
}
