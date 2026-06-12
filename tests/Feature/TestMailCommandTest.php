<?php

namespace Tests\Feature;

use App\Mail\OperationalTestMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestMailCommandTest extends TestCase
{
    public function test_mail_command_sends_a_test_message(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'owner@example.com'])
            ->expectsOutput('Test email sent to owner@example.com.')
            ->assertSuccessful();

        Mail::assertSent(OperationalTestMail::class, fn ($mail) => $mail->hasTo('owner@example.com'));
    }

    public function test_mail_command_rejects_an_invalid_address(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'invalid-address'])
            ->assertFailed();

        Mail::assertNothingSent();
    }
}
