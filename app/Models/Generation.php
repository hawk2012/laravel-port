<?php

namespace App\Models;

use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property int $user_id
 * @property GenerationType $type
 * @property GenerationStatus $status
 * @property string $prompt
 * @property string|null $negative_prompt
 * @property array<string, mixed>|null $params
 * @property string|null $provider
 * @property string|null $model
 * @property array<string, mixed>|null $result
 * @property int $credits_used
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int|null $latency_ms
 * @property string|null $error_message
 * @property string|null $idempotency_key
 * @property Carbon|null $queued_at
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read User $user
 */
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'project_id',
        'user_id',
        'type',
        'status',
        'prompt',
        'negative_prompt',
        'params',
        'provider',
        'model',
        'result',
        'credits_used',
        'input_tokens',
        'output_tokens',
        'latency_ms',
        'error_message',
        'idempotency_key',
        'queued_at',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GenerationType::class,
            'status' => GenerationStatus::class,
            'params' => 'array',
            'result' => 'array',
            'credits_used' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'latency_ms' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $generation): void {
            $generation->uuid ??= (string) Str::uuid();
            $generation->queued_at ??= now();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * @param Builder<Generation> $query
     * @return Builder<Generation>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @param Builder<Generation> $query
     * @return Builder<Generation>
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereIn('status', [GenerationStatus::Queued, GenerationStatus::Processing]);
    }
}
