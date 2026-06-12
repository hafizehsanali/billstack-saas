<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertStatus(200)
            ->assertSee('Create your account')
            ->assertSee('billing, stock, customers, suppliers, expenses, and reports');
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'business_name' => 'Test Store',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertNotNull($user->terms_accepted_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_requires_policy_agreement(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'business_name' => 'Test Store',
            'email' => 'terms@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('terms');

        $this->assertDatabaseMissing('users', ['email' => 'terms@example.com']);
    }

    public function test_public_policy_pages_are_available(): void
    {
        $this->get(route('legal.terms'))->assertOk()->assertSee('Terms of Service');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Privacy Policy');
        $this->get(route('legal.refunds'))->assertOk()->assertSee('Refund Policy');
    }

    public function test_unverified_owner_is_redirected_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
