<?php

namespace App\Models;

use App\Enums\GenerationType;
use Database\Factories\UsageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $project_id
 * @property int|null $generation_id
 * @property GenerationType $type
 * @property string|null $provider
 * @property int $credits
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $cost_micros
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UsageLog extends Model
{
    /** @use HasFactory<UsageLogFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'generation_id',
        'type',
        'provider',
        'credits',
        'input_tokens',
        'output_tokens',
        'cost_micros',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GenerationType::class,
            'credits' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost_micros' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Generation, $this>
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }
}
