<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\DisablesDnsEmailValidation;
use Tests\TestCase;

class InertiaPageTest extends TestCase
{
    use DisablesDnsEmailValidation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableDnsEmailValidation();
    }

    #[Test]
    public function guest_sees_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Landing')
                ->has('stats.types', 4));
    }

    #[Test]
    public function guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    #[Test]
    public function authenticated_user_sees_dashboard_with_stats(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->has('stats.status_counts')
                ->has('stats.type_counts')
                ->has('stats.daily')
                ->has('stats.recent')
                ->has('stats.project_stats')
                ->has('stats.usage')
                ->has('projects')
                ->where('auth.user.id', $user->id));
    }

    #[Test]
    public function authenticated_user_sees_all_application_pages(): void
    {
        $user = User::factory()->create();

        $pages = [
            '/studio' => 'Studio',
            '/history' => 'History',
            '/library' => 'Library',
            '/projects' => 'Projects',
            '/usage' => 'Usage',
        ];

        foreach ($pages as $url => $component) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    #[Test]
    public function user_can_log_in_through_session(): void
    {
        $user = User::factory()->create([
            'email' => 'session@example.com',
            'password' => 'password12345',
        ]);

        $response = $this->post('/login', [
            'email' => 'session@example.com',
            'password' => 'password12345',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function user_can_register_through_session(): void
    {
        $response = $this->post('/register', [
            'name' => 'Новый Пользователь',
            'email' => 'newcomer@example.com',
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'newcomer@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame((int) config('billing.default_credits'), $user->credits_balance);
    }
}
