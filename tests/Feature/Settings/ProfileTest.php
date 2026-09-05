<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/user/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Name');

        $user->refresh();
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_kept_when_email_is_unchanged(): void
    {
        $user = User::factory()->create();
        $verifiedAt = $user->email_verified_at;

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/user/profile', [
                'name' => 'Updated Name',
                'email' => $user->email,
            ])
            ->assertOk();

        $this->assertEquals($verifiedAt, $user->fresh()->email_verified_at);
    }

    public function test_profile_update_requires_a_unique_email(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/user/profile', ['name' => 'X', 'email' => 'taken@example.com'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/user', ['password' => 'password'])
            ->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_account_deletion_requires_the_correct_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/user', ['password' => 'wrong-password'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_guests_cannot_access_profile_endpoints(): void
    {
        $this->patchJson('/api/user/profile', ['name' => 'X', 'email' => 'x@example.com'])
            ->assertUnauthorized();
    }
}
