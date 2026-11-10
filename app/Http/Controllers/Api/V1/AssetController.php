<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Repositories\AssetRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function __construct(private readonly AssetRepository $assets) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'kind' => ['nullable', Rule::in(['image', 'video', 'audio', 'text'])],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return AssetResource::collection(
            $this->assets->paginateForUser($request->user(), $validated, (int) ($validated['per_page'] ?? 24)),
        );
    }

    /**
     * Отдача файла через приложение: проверяем владельца, не светим путь на диске.
     */
    public function download(Request $request, string $uuid): StreamedResponse
    {
        $asset = $this->assets->findForUser($request->user(), $uuid);

        abort_if($asset === null, Response::HTTP_NOT_FOUND, 'Ассет не найден.');

        $disk = Storage::disk($asset->disk);

        abort_unless($disk->exists($asset->path), Response::HTTP_GONE, 'Файл ассета недоступен.');

        return $disk->response($asset->path, null, [
            'Content-Type' => $asset->mime_type,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
