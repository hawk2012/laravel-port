<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\DisablesDnsEmailValidation;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use DisablesDnsEmailValidation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableDnsEmailValidation();
    }

    #[Test]
    public function it_registers_user_and_returns_token_with_default_credits(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Михаил Губин',
            'email' => 'mikhail@example.com',
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
            'device_name' => 'phpunit',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'mikhail@example.com')
            ->assertJsonPath('data.user.plan', 'free')
            ->assertJsonPath('data.user.credits_balance', (int) config('billing.default_credits'))
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'plan', 'credits_balance'], 'token']]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('users', ['email' => 'mikhail@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    #[Test]
    public function it_rejects_registration_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Михаил Губин',
            'email' => 'taken@example.com',
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function it_rejects_registration_with_weak_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Михаил Губин',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_logs_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password12345',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password12345',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    #[Test]
    public function it_rejects_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password12345',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function it_requires_authentication_for_me_endpoint(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized()->assertJsonPath('code', 'unauthenticated');
    }

    #[Test]
    public function it_returns_current_user_for_authenticated_request(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'me@example.com')
            ->assertJsonPath('data.concurrent_limit', $user->plan->concurrentGenerations());
    }

    #[Test]
    public function it_revokes_token_on_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phpunit');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
