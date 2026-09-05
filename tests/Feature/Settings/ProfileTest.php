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

        $this->assertSame('updated@example.com', $user->fresh()->email);
    }

    public function test_profile_update_requires_a_unique_email(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/user/profile', ['name' => 'X', 'email' => 'taken@example.com'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_guests_cannot_update_the_profile(): void
    {
        $this->patchJson('/api/user/profile', ['name' => 'X', 'email' => 'x@example.com'])
            ->assertUnauthorized();
    }
}
