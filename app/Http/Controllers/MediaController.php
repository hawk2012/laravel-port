<?php

namespace App\Http\Controllers;

use App\Repositories\AssetRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Отдача ассетов для Inertia-страниц (сессионная аутентификация).
 * API-клиенты используют /api/v1/assets/{uuid}/download с Bearer-токеном.
 */
class MediaController extends Controller
{
    public function __construct(private readonly AssetRepository $assets) {}

    public function show(Request $request, string $uuid): StreamedResponse
    {
        $user = $request->user();

        abort_if($user === null, Response::HTTP_UNAUTHORIZED);

        $asset = $this->assets->findForUser($user, $uuid);

        abort_if($asset === null, Response::HTTP_NOT_FOUND, 'Ассет не найден.');

        $disk = Storage::disk($asset->disk);

        abort_unless($disk->exists($asset->path), Response::HTTP_GONE, 'Файл ассета недоступен.');

        return $disk->response($asset->path, null, [
            'Content-Type' => $asset->mime_type,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
