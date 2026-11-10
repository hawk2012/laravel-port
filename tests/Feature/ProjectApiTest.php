<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function it_lists_only_projects_of_current_user(): void
    {
        $own = Project::factory()->count(2)->create(['user_id' => $this->user->id]);
        Project::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/projects');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing(
            $own->pluck('id')->all(),
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    #[Test]
    public function it_filters_projects_by_search(): void
    {
        Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Неоновый город']);
        Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Лесной пейзаж']);

        $response = $this->getJson('/api/v1/projects?search=Неоновый');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Неоновый город', $response->json('data.0.name'));
    }

    #[Test]
    public function it_filters_projects_by_archived_status(): void
    {
        Project::factory()->create(['user_id' => $this->user->id]);
        $archived = Project::factory()->archived()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/projects?status=archived');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($archived->id, $response->json('data.0.id'));
        $this->assertSame('archived', $response->json('data.0.status'));
    }

    #[Test]
    public function it_creates_project_with_generated_slug(): void
    {
        $response = $this->postJson('/api/v1/projects', [
            'name' => 'Кампания Весна',
            'description' => 'Промо-материалы',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Кампания Весна')
            ->assertJsonPath('data.status', 'active');

        $slug = $response->json('data.slug');
        $this->assertNotEmpty($slug);
        $this->assertStringStartsWith(Str::slug('Кампания Весна').'-', $slug);

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'name' => 'Кампания Весна',
            'slug' => $slug,
        ]);
    }

    #[Test]
    public function it_rejects_project_with_invalid_name(): void
    {
        $response = $this->postJson('/api/v1/projects', ['name' => 'X']);

        $response->assertStatus(422)->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('projects', 0);
    }

    #[Test]
    public function it_forbids_viewing_foreign_project(): void
    {
        $foreign = Project::factory()->create();

        $this->getJson("/api/v1/projects/{$foreign->id}")->assertForbidden();
    }

    #[Test]
    public function it_updates_own_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Старое имя']);

        $response = $this->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Новое имя',
            'status' => 'archived',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Новое имя')
            ->assertJsonPath('data.status', 'archived');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Новое имя']);
    }

    #[Test]
    public function it_soft_deletes_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $this->deleteJson("/api/v1/projects/{$project->id}")->assertNoContent();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    #[Test]
    public function it_respects_per_page_parameter(): void
    {
        Project::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/projects?per_page=2');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame(5, $response->json('meta.total'));
        $this->assertSame(2, $response->json('meta.per_page'));
    }
}
