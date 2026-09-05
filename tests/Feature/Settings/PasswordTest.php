<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated_and_a_fresh_token_is_returned(): void
    {
        $user = User::factory()->create();
        $user->createToken('spa');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertCount(1, $user->fresh()->tokens);
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertStatus(422)->assertJsonValidationErrors('current_password');
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'different',
            ])
            ->assertStatus(422)->assertJsonValidationErrors('password');
    }
}
