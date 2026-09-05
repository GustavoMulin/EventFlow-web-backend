<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_and_receive_a_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $this->assertCount(1, $user->fresh()->tokens);
    }

    public function test_users_cannot_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429);
    }

    public function test_users_with_two_factor_enabled_must_provide_a_code(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertExactJson(['two_factor' => true]);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_users_can_complete_the_two_factor_challenge_with_a_valid_code(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $code = app(Google2FA::class)
            ->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_users_can_complete_the_two_factor_challenge_with_a_recovery_code(): void
    {
        $user = User::factory()->withTwoFactor()->create();
        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1', 'recovery-code-2'])),
        ])->save();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'recovery_code' => 'recovery-code-1',
        ])->assertOk()->assertJsonStructure(['token']);

        $this->assertNotContains('recovery-code-1', $user->fresh()->recoveryCodes());
    }

    public function test_authenticated_user_can_be_retrieved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('two_factor_enabled', false);
    }

    public function test_users_can_logout_and_the_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
