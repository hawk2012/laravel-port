<?php

namespace Tests\Feature;

use App\Models\User;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\EmailValidation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProbeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function probe_dns_bypass(): void
    {
        $this->app->bind(EmailValidator::class, fn () => new class extends EmailValidator
        {
            public function isValid(string $email, EmailValidation $emailValidation): bool
            {
                return true;
            }
        });

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Тест Тестов',
            'email' => 'probe@example.com',
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
        ]);

        fwrite(STDERR, "\nREGISTER: ".$response->status().' '.substr($response->getContent(), 0, 200)."\n");
        $this->assertTrue(true);
    }

    #[Test]
    public function probe_logout_with_acting_as(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        fwrite(STDERR, "\nLOGOUT actingAs: ".$response->status().' '.substr($response->getContent(), 0, 300)."\n");
        $this->assertTrue(true);
    }

    #[Test]
    public function probe_logout_with_real_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('probe');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        fwrite(STDERR, "\nLOGOUT token: ".$response->status().' tokens='.\DB::table('personal_access_tokens')->count()."\n");
        $this->assertTrue(true);
    }
}
