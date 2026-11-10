<?php

namespace App\Repositories;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class AssetRepository
{
    /**
     * @param array{kind?: string|null, project_id?: int|null} $filters
     * @return LengthAwarePaginator<int, Asset>
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $query = Asset::query()->where('user_id', $user->id)->with('generation:id,uuid,prompt');

        if (!empty($filters['kind'])) {
            $query->where('kind', $filters['kind']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findForUser(User $user, string $uuid): ?Asset
    {
        return Asset::query()->where('user_id', $user->id)->where('uuid', $uuid)->first();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Asset
    {
        return Asset::query()->create($attributes);
    }

    /**
     * @return array<string, int>
     */
    public function kindCounts(User $user): array
    {
        /** @var array<string, int> $counts */
        $counts = Asset::query()
            ->where('user_id', $user->id)
            ->toBase()
            ->selectRaw('kind, COUNT(*) as aggregate')
            ->groupBy('kind')
            ->pluck('aggregate', 'kind')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        return $counts;
    }

    /**
     * @return Builder<Asset>
     */
    public function queryForUser(User $user): Builder
    {
        return Asset::query()->where('user_id', $user->id);
    }
}
