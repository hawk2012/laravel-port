<?php

namespace App\Models;

use App\Enums\AssetKind;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $generation_id
 * @property int $project_id
 * @property int $user_id
 * @property AssetKind $kind
 * @property string $mime_type
 * @property string $disk
 * @property string $path
 * @property string|null $checksum
 * @property int $size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property int|null $duration_ms
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'generation_id',
        'project_id',
        'user_id',
        'kind',
        'mime_type',
        'disk',
        'path',
        'checksum',
        'size_bytes',
        'width',
        'height',
        'duration_ms',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AssetKind::class,
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_ms' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $asset): void {
            $asset->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Generation, $this>
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
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
}
