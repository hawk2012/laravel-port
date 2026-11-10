<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Repositories\ProjectRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class ProjectService
{
    public function __construct(private readonly ProjectRepository $projects) {}

    /**
     * @param array{search?: string|null, status?: string|null} $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginate(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->projects->paginateForUser($user, $filters, $perPage);
    }

    /**
     * @param array{name: string, description?: string|null, settings?: array<string, mixed>|null} $data
     */
    public function create(User $user, array $data): Project
    {
        $project = $this->projects->create($user, $data);
        $this->forgetStats($user);

        return $project;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Project $project, array $data): Project
    {
        $project = $this->projects->update($project, $data);
        $this->forgetStats($project->user);

        return $project;
    }

    public function archive(Project $project): Project
    {
        $project = $this->projects->update($project, ['status' => 'archived']);
        $this->forgetStats($project->user);

        return $project;
    }

    public function delete(Project $project): void
    {
        $this->projects->delete($project);
        $this->forgetStats($project->user);
    }

    /**
     * @return array{active: int, archived: int, total: int}
     */
    public function stats(User $user): array
    {
        return Cache::remember(
            $this->statsCacheKey($user),
            now()->addMinutes(5),
            fn (): array => $this->projects->countsByStatus($user),
        );
    }

    private function forgetStats(User $user): void
    {
        Cache::forget($this->statsCacheKey($user));
    }

    private function statsCacheKey(User $user): string
    {
        return "projects:stats:{$user->id}";
    }
}
