<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'plan' => $this->plan->value,
            'plan_label' => $this->plan->label(),
            'credits_balance' => $this->credits_balance,
            'concurrent_limit' => $this->plan->concurrentGenerations(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
