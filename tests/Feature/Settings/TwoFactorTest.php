<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/two-factor')
            ->assertOk()
            ->assertJsonStructure(['svg', 'secret_key', 'recovery_codes'])
            ->assertJsonPath('confirmed', false);

        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_user_can_confirm_two_factor_authentication_with_a_valid_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/user/two-factor')->assertOk();

        $secret = decrypt($user->fresh()->two_factor_secret);
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/two-factor/confirm', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('confirmed', true);

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_confirming_with_an_invalid_code_fails(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/user/two-factor')->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/two-factor/confirm', ['code' => '000000'])
            ->assertStatus(422);

        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_user_can_disable_two_factor_authentication(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/user/two-factor')
            ->assertNoContent();

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_recovery_codes_can_be_listed_and_regenerated(): void
    {
        $user = User::factory()->withTwoFactor()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/user/two-factor')->assertOk();

        $original = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/two-factor/recovery-codes')
            ->assertOk()
            ->json('recovery_codes');

        $regenerated = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/two-factor/recovery-codes')
            ->assertOk()
            ->json('recovery_codes');

        $this->assertCount(8, $regenerated);
        $this->assertNotEquals($original, $regenerated);
    }

    public function test_guests_cannot_manage_two_factor_authentication(): void
    {
        $this->postJson('/api/user/two-factor')->assertUnauthorized();
    }
}
