<?php

namespace App\Repositories;

use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GenerationRepository
{
    /**
     * @param array{status?: string|null, type?: string|null, project_id?: int|null, search?: string|null} $filters
     * @return LengthAwarePaginator<int, Generation>
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($user, $filters)
            ->with(['project:id,name,slug', 'assets'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForUser(User $user, string $uuid): ?Generation
    {
        return Generation::query()
            ->ownedBy($user)
            ->where('uuid', $uuid)
            ->with(['project:id,name,slug', 'assets'])
            ->first();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Generation
    {
        return Generation::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Generation $generation, array $attributes): Generation
    {
        $generation->fill($attributes)->save();

        return $generation->refresh();
    }

    public function countRunning(User $user): int
    {
        return Generation::query()->ownedBy($user)->running()->count();
    }

    public function findByUuid(string $uuid): ?Generation
    {
        return Generation::query()->where('uuid', $uuid)->first();
    }

    public function findByIdempotencyKey(User $user, string $key): ?Generation
    {
        return Generation::query()
            ->ownedBy($user)
            ->where('idempotency_key', $key)
            ->first();
    }

    /**
     * Последние генерации для дашборда.
     *
     * @return Collection<int, Generation>
     */
    public function recentForUser(User $user, int $limit = 6): Collection
    {
        return Generation::query()
            ->ownedBy($user)
            ->with(['project:id,name,slug', 'assets'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Количество генераций по статусам за период.
     *
     * @return array<string, int>
     */
    public function statusCounts(User $user, ?\DateTimeInterface $from = null): array
    {
        $query = Generation::query()->ownedBy($user);

        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        /** @var array<string, int> $counts */
        $counts = $query
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    public function typeCounts(User $user, ?\DateTimeInterface $from = null): array
    {
        $query = Generation::query()->ownedBy($user);

        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        /** @var array<string, int> $counts */
        $counts = $query
            ->toBase()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        $result = [];
        foreach (GenerationType::cases() as $type) {
            $result[$type->value] = $counts[$type->value] ?? 0;
        }

        return $result;
    }

    /**
     * Дневная динамика генераций (для графика на дашборде).
     *
     * @return list<array{date: string, total: int, failed: int}>
     */
    public function dailySeries(User $user, int $days = 14): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = Generation::query()
            ->ownedBy($user)
            ->where('created_at', '>=', $from)
            ->toBase()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $failedRows = Generation::query()
            ->ownedBy($user)
            ->where('created_at', '>=', $from)
            ->where('status', GenerationStatus::Failed)
            ->toBase()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('aggregate', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $series[] = [
                'date' => $day,
                'total' => (int) ($rows[$day] ?? 0),
                'failed' => (int) ($failedRows[$day] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @param array{status?: string|null, type?: string|null, project_id?: int|null, search?: string|null} $filters
     * @return Builder<Generation>
     */
    private function filteredQuery(User $user, array $filters): Builder
    {
        $query = Generation::query()->ownedBy($user);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where('prompt', 'like', "%{$search}%");
        }

        return $query;
    }
}
