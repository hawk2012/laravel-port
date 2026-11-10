<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use App\Models\Generation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['credits_balance' => 500]);
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function it_creates_generation_and_charges_credits(): void
    {
        Queue::fake();

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/generations", [
            'type' => 'image',
            'prompt' => 'неоновый город под дождём',
            'idempotency_key' => 'draft-1',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.type', 'image')
            ->assertJsonPath('data.credits_used', GenerationType::Image->creditCost());

        $this->assertSame(500 - GenerationType::Image->creditCost(), $this->user->refresh()->credits_balance);

        $this->assertDatabaseHas('generations', [
            'uuid' => $response->json('data.uuid'),
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => 'queued',
            'idempotency_key' => 'draft-1',
        ]);
    }

    #[Test]
    public function it_returns_same_generation_for_repeated_idempotency_key_without_double_charge(): void
    {
        Queue::fake();

        $payload = [
            'type' => 'image',
            'prompt' => 'неоновый город под дождём',
            'idempotency_key' => 'draft-42',
        ];

        $first = $this->postJson("/api/v1/projects/{$this->project->id}/generations", $payload);
        $second = $this->postJson("/api/v1/projects/{$this->project->id}/generations", $payload);

        $first->assertStatus(202);
        $second->assertStatus(202);

        $this->assertSame($first->json('data.uuid'), $second->json('data.uuid'));
        $this->assertDatabaseCount('generations', 1);
        $this->assertSame(500 - GenerationType::Image->creditCost(), $this->user->refresh()->credits_balance);
    }

    #[Test]
    public function it_rejects_generation_when_credits_are_insufficient(): void
    {
        Queue::fake();

        $this->user->forceFill(['credits_balance' => 1])->save();

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/generations", [
            'type' => 'image',
            'prompt' => 'неоновый город под дождём',
        ]);

        $response->assertStatus(402)
            ->assertJsonPath('code', 'insufficient_credits')
            ->assertJsonPath('required', GenerationType::Image->creditCost())
            ->assertJsonPath('available', 1);

        $this->assertDatabaseCount('generations', 0);
        $this->assertSame(1, $this->user->refresh()->credits_balance);
    }

    #[Test]
    public function it_rejects_generation_when_concurrency_limit_is_reached(): void
    {
        Queue::fake();

        $limit = $this->user->plan->concurrentGenerations();

        Generation::factory()->count($limit)->queued()->forProject($this->project)->create();

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/generations", [
            'type' => 'image',
            'prompt' => 'неоновый город под дождём',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('code', 'concurrency_limit')
            ->assertJsonPath('limit', $limit);

        $this->assertDatabaseCount('generations', $limit);
    }

    #[Test]
    public function it_forbids_generation_in_foreign_project(): void
    {
        Queue::fake();

        $foreign = Project::factory()->create();

        $response = $this->postJson("/api/v1/projects/{$foreign->id}/generations", [
            'type' => 'image',
            'prompt' => 'неоновый город под дождём',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('generations', 0);
    }

    #[Test]
    public function it_validates_generation_payload(): void
    {
        Queue::fake();

        $this->postJson("/api/v1/projects/{$this->project->id}/generations", [
            'type' => 'image',
            'prompt' => '',
        ])->assertStatus(422)->assertJsonValidationErrors('prompt');

        $this->postJson("/api/v1/projects/{$this->project->id}/generations", [
            'type' => 'hologram',
            'prompt' => 'неоновый город под дождём',
        ])->assertStatus(422)->assertJsonValidationErrors('type');

        $this->assertDatabaseCount('generations', 0);
    }

    #[Test]
    public function it_hides_foreign_generation(): void
    {
        $foreign = Generation::factory()->create();

        $this->getJson("/api/v1/generations/{$foreign->uuid}")
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');
    }

    #[Test]
    public function it_cancels_queued_generation_and_refunds_credits(): void
    {
        $cost = GenerationType::Image->creditCost();
        $this->user->forceFill(['credits_balance' => 500 - $cost])->save();

        $generation = Generation::factory()->queued()->forProject($this->project)->create([
            'type' => GenerationType::Image,
            'credits_used' => $cost,
        ]);

        $response = $this->postJson("/api/v1/generations/{$generation->uuid}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(500, $this->user->refresh()->credits_balance);
        $this->assertDatabaseHas('generations', [
            'id' => $generation->id,
            'status' => GenerationStatus::Cancelled->value,
        ]);
    }

    #[Test]
    public function it_does_not_cancel_completed_generation(): void
    {
        $generation = Generation::factory()->forProject($this->project)->create([
            'status' => GenerationStatus::Completed,
            'credits_used' => GenerationType::Image->creditCost(),
        ]);

        $response = $this->postJson("/api/v1/generations/{$generation->uuid}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertSame(500, $this->user->refresh()->credits_balance);
    }

    #[Test]
    public function it_filters_generation_list_by_status_and_type(): void
    {
        Generation::factory()->forProject($this->project)->create([
            'type' => GenerationType::Image,
            'status' => GenerationStatus::Completed,
        ]);
        Generation::factory()->forProject($this->project)->create([
            'type' => GenerationType::Text,
            'status' => GenerationStatus::Completed,
        ]);
        Generation::factory()->forProject($this->project)->failed()->create([
            'type' => GenerationType::Image,
        ]);

        $this->getJson('/api/v1/generations?status=completed&type=image')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'image')
            ->assertJsonPath('data.0.status', 'completed');

        $this->getJson('/api/v1/generations?status=failed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'failed');
    }

    #[Test]
    public function it_rate_limits_generation_creation_for_free_plan(): void
    {
        // Очередь не фейкаем: задачи должны завершаться, иначе сработает
        // лимит параллельных генераций (2 для free) раньше, чем throttle.
        $payload = ['type' => 'text', 'prompt' => 'короткий текст для лимита'];

        for ($i = 0; $i < 6; $i++) {
            $this->postJson("/api/v1/projects/{$this->project->id}/generations", $payload)
                ->assertStatus(202);
        }

        $this->postJson("/api/v1/projects/{$this->project->id}/generations", $payload)
            ->assertStatus(429)
            ->assertJsonPath('code', 'rate_limited');
    }
}
