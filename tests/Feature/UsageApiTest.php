<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use App\Models\Generation;
use App\Models\Project;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsageApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 12:00:00');

        $this->user = User::factory()->create(['credits_balance' => 400]);
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_returns_usage_aggregates(): void
    {
        Sanctum::actingAs($this->user);

        $this->makeGeneration(GenerationType::Image, GenerationStatus::Completed, credits: 5);
        $this->makeGeneration(GenerationType::Image, GenerationStatus::Completed, credits: 5);
        $this->makeGeneration(GenerationType::Text, GenerationStatus::Completed, credits: 1);
        $this->makeGeneration(GenerationType::Text, GenerationStatus::Failed, credits: 0);
        $this->makeGeneration(GenerationType::Text, GenerationStatus::Cancelled, credits: 0);

        $response = $this->getJson('/api/v1/usage');

        $response->assertOk()
            ->assertJsonPath('data.period_days', 30)
            ->assertJsonPath('data.summary.generations', 5)
            ->assertJsonPath('data.summary.completed', 3)
            ->assertJsonPath('data.summary.failed', 1)
            ->assertJsonPath('data.summary.credits_spent', 11)
            // by_type считается по usage_logs, поэтому неуспешные генерации в него не попадают.
            ->assertJsonPath('data.summary.by_type.image', 2)
            ->assertJsonPath('data.summary.by_type.text', 1)
            ->assertJsonPath('data.user.credits_balance', 400);
    }

    #[Test]
    public function it_filters_usage_by_days_period(): void
    {
        Sanctum::actingAs($this->user);

        $this->makeGeneration(GenerationType::Image, GenerationStatus::Completed, credits: 5, occurredAt: now()->subDay());
        $this->makeGeneration(GenerationType::Image, GenerationStatus::Completed, credits: 5, occurredAt: now()->subDays(20));

        $this->getJson('/api/v1/usage?days=7')
            ->assertOk()
            ->assertJsonPath('data.period_days', 7)
            ->assertJsonPath('data.summary.generations', 1)
            ->assertJsonPath('data.summary.credits_spent', 5);

        $this->getJson('/api/v1/usage?days=30')
            ->assertOk()
            ->assertJsonPath('data.summary.generations', 2)
            ->assertJsonPath('data.summary.credits_spent', 10);
    }

    #[Test]
    public function it_requires_authentication_for_usage(): void
    {
        $this->getJson('/api/v1/usage')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    private function makeGeneration(
        GenerationType $type,
        GenerationStatus $status,
        int $credits,
        ?Carbon $occurredAt = null,
    ): Generation {
        $occurredAt ??= now();

        $generation = Generation::factory()->forProject($this->project)->create([
            'type' => $type,
            'status' => $status,
            'credits_used' => $credits,
            'created_at' => $occurredAt,
        ]);

        // usage_logs пишутся только по факту списания — для failed/cancelled их нет.
        if ($credits > 0) {
            UsageLog::factory()->create([
                'user_id' => $this->user->id,
                'project_id' => $this->project->id,
                'generation_id' => $generation->id,
                'type' => $type,
                'credits' => $credits,
                'occurred_at' => $occurredAt,
            ]);
        }

        return $generation;
    }
}
