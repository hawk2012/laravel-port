<?php

namespace Tests\Feature;

use App\Enums\AssetKind;
use App\Models\Asset;
use App\Models\Generation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $this->owner->id]);
        $generation = Generation::factory()->forProject($project)->create();

        $path = 'generations/'.uniqid('asset-', true).'.png';
        Storage::disk('local')->put($path, "\x89PNG\r\n\x1a\n binary-payload");

        $this->asset = Asset::factory()->forGeneration($generation)->create([
            'user_id' => $this->owner->id,
            'project_id' => $project->id,
            'kind' => AssetKind::Image,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'image/png',
        ]);
    }

    #[Test]
    public function it_lists_only_assets_of_current_user(): void
    {
        $stranger = User::factory()->create();
        Asset::factory()->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/assets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $this->asset->uuid)
            ->assertJsonPath('data.0.kind', 'image');
    }

    #[Test]
    public function it_filters_assets_by_kind(): void
    {
        Asset::factory()->create([
            'user_id' => $this->owner->id,
            'project_id' => $this->asset->project_id,
            'kind' => AssetKind::Text,
            'mime_type' => 'text/plain',
            'path' => 'generations/text.txt',
        ]);

        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/assets?kind=image')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/assets?kind=text')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kind', 'text');
    }

    #[Test]
    public function it_downloads_asset_for_owner(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->get("/api/v1/assets/{$this->asset->uuid}/download");

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('binary-payload', $response->streamedContent());
    }

    #[Test]
    public function it_hides_asset_of_another_user(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/assets/{$this->asset->uuid}/download")
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');
    }

    #[Test]
    public function it_returns_gone_when_file_is_missing_on_disk(): void
    {
        Storage::disk('local')->delete($this->asset->path);

        Sanctum::actingAs($this->owner);

        $this->get("/api/v1/assets/{$this->asset->uuid}/download")->assertStatus(410);
    }

    #[Test]
    public function it_streams_asset_for_authenticated_session(): void
    {
        $response = $this->actingAs($this->owner)->get("/media/{$this->asset->uuid}");

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('binary-payload', $response->streamedContent());
    }

    #[Test]
    public function it_redirects_guest_from_media_route(): void
    {
        $this->get("/media/{$this->asset->uuid}")->assertRedirect('/login');
    }

    #[Test]
    public function it_forbids_media_of_another_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get("/media/{$this->asset->uuid}")
            ->assertNotFound();
    }
}
