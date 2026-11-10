<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use App\Exceptions\ProviderException;
use App\Jobs\ProcessGeneration;
use App\Models\Asset;
use App\Models\Generation;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\AiProviderManager;
use App\Services\GenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create(['credits_balance' => 500]);
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function it_runs_full_happy_path_through_sync_queue(): void
    {
        $cost = GenerationType::Image->creditCost();

        $created = $this->postJson("/api/v1/projects/{$this->project->id}/generations", [
            'type' => 'image',
            'prompt' => 'неоновый город под дождём',
        ]);

        $created->assertStatus(202);
        $uuid = $created->json('data.uuid');

        // QUEUE_CONNECTION=sync: джоб выполнился внутри POST, поэтому GET сразу видит completed.
        $response = $this->getJson("/api/v1/generations/{$uuid}");

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.provider', 'stub')
            ->assertJsonCount(1, 'data.assets')
            ->assertJsonPath('data.assets.0.kind', 'image');

        $generation = Generation::query()->where('uuid', $uuid)->firstOrFail();

        $this->assertSame(GenerationStatus::Completed, $generation->status);
        $this->assertNotNull($generation->finished_at);
        $this->assertNotNull($generation->latency_ms);
        $this->assertSame(1, $generation->assets()->count());

        $this->assertDatabaseHas('usage_logs', [
            'user_id' => $this->user->id,
            'generation_id' => $generation->id,
            'type' => 'image',
            'credits' => $cost,
        ]);

        $this->assertSame(500 - $cost, $this->user->refresh()->credits_balance);
    }

    #[Test]
    public function it_marks_generation_failed_and_refunds_credits_on_provider_error(): void
    {
        // AiProviderManager создаёт провайдера сам, поэтому подменяем именно менеджер:
        // bind(AiProvider::class) на резолв внутри manager не влияет.
        $this->mock(AiProviderManager::class, function ($mock): void {
            $mock->shouldReceive('driver')
                ->andThrow(new ProviderException('провайдер недоступен', false));
        });

        $cost = GenerationType::Image->creditCost();

        $created = $this->postJson("/api/v1/projects/{$this->project->id}/generations", [
            'type' => 'image',
            'prompt' => 'неоновый город под дождём',
        ]);

        $created->assertStatus(202);

        $generation = Generation::query()->where('uuid', $created->json('data.uuid'))->firstOrFail();

        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('провайдер недоступен', $generation->error_message);
        $this->assertSame(500, $this->user->refresh()->credits_balance);
        $this->assertSame(0, $generation->assets()->count());
        $this->assertDatabaseCount('usage_logs', 0);
    }

    #[Test]
    public function it_skips_job_for_generation_cancelled_before_start(): void
    {
        $generation = Generation::factory()->forProject($this->project)->create([
            'status' => GenerationStatus::Cancelled,
            'credits_used' => GenerationType::Image->creditCost(),
            'started_at' => null,
            'finished_at' => now(),
        ]);

        (new ProcessGeneration($generation->id))->handle(
            $this->app->make(AiProviderManager::class),
            $this->app->make(GenerationService::class),
        );

        $generation->refresh();

        $this->assertSame(GenerationStatus::Cancelled, $generation->status);
        $this->assertNull($generation->started_at);
        $this->assertSame(0, $generation->assets()->count());
        $this->assertDatabaseCount('usage_logs', 0);
    }

    #[Test]
    public function it_stores_asset_on_disk_with_checksum(): void
    {
        $created = $this->postJson("/api/v1/projects/{$this->project->id}/generations", [
            'type' => 'image',
            'prompt' => 'неоновый город под дождём',
        ]);

        $created->assertStatus(202);

        $asset = Asset::query()->where('user_id', $this->user->id)->firstOrFail();

        Storage::disk($asset->disk)->assertExists($asset->path);

        $contents = Storage::disk($asset->disk)->get($asset->path);

        $this->assertNotEmpty($contents);
        $this->assertStringStartsWith("\x89PNG", $contents);
        $this->assertSame(hash('sha256', $contents), $asset->checksum);
        $this->assertSame(strlen($contents), $asset->size_bytes);
        $this->assertSame('image/png', $asset->mime_type);
    }
}
