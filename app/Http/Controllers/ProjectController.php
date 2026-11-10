<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Мутации проектов для Inertia-интерфейса (сессия + CSRF).
 * REST-клиенты работают через Api\V1\ProjectController.
 */
class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->projects->create($request->user(), $validated);

        return back()->with('success', 'Проект создан.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', 'in:active,archived'],
        ]);

        $this->projects->update($project, $validated);

        return back()->with('success', 'Проект обновлён.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);

        $this->projects->delete($project);

        return back()->with('success', 'Проект удалён.');
    }

    private function authorizeOwner(Request $request, Project $project): void
    {
        abort_unless($project->user_id === $request->user()->id, 403, 'Проект недоступен.');
    }
}
