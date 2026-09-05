<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_request_a_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/email/verification-notification')
            ->assertStatus(202);

        Notification::assertSentTo($user, QueuedVerifyEmail::class);
    }

    public function test_verification_endpoint_is_not_sent_when_already_verified(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('status', 'already-verified');

        Notification::assertNothingSent();
    }

    public function test_email_can_be_verified_from_a_signed_link(): void
    {
        Event::fake();
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect(config('app.frontend_url').'/verify-email?status=verified');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_email_is_not_verified_with_an_invalid_hash(): void
    {
        Event::fake();
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('wrong@example.com'),
        ]);

        $this->get($url)->assertRedirect(config('app.frontend_url').'/verify-email?status=invalid');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        Event::assertNotDispatched(Verified::class);
    }

    public function test_verification_link_requires_a_valid_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get("/api/email/verify/{$user->id}/".sha1($user->email))
            ->assertForbidden();
    }
}
