<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Доступ к данным проектов. Никакой бизнес-логики — только запросы.
 */
final class ProjectRepository
{
    /**
     * @param array{search?: string|null, status?: string|null} $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($user, $filters)
            ->withCount('generations')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array{search?: string|null, status?: string|null} $filters
     * @return Collection<int, Project>
     */
    public function listForUser(User $user, array $filters = []): Collection
    {
        return $this->filteredQuery($user, $filters)
            ->withCount('generations')
            ->latest('id')
            ->limit(100)
            ->get();
    }

    public function findForUser(User $user, int $id): ?Project
    {
        return Project::query()->ownedBy($user)->whereKey($id)->first();
    }

    public function findBySlug(User $user, string $slug): ?Project
    {
        return Project::query()->ownedBy($user)->where('slug', $slug)->first();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(User $user, array $attributes): Project
    {
        return $user->projects()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Project $project, array $attributes): Project
    {
        $project->fill($attributes)->save();

        return $project->refresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    /**
     * @return array{active: int, archived: int, total: int}
     */
    public function countsByStatus(User $user): array
    {
        /** @var array<string, int> $counts */
        $counts = Project::query()
            ->ownedBy($user)
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        return [
            'active' => $counts['active'] ?? 0,
            'archived' => $counts['archived'] ?? 0,
            'total' => array_sum($counts),
        ];
    }

    /**
     * @param array{search?: string|null, status?: string|null} $filters
     * @return Builder<Project>
     */
    private function filteredQuery(User $user, array $filters): Builder
    {
        $query = Project::query()->ownedBy($user);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
