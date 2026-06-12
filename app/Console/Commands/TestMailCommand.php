<?php

namespace App\Console\Commands;

use App\Mail\OperationalTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email : Recipient email address}';

    protected $description = 'Send a test email using the configured mail provider';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid recipient email address.');

            return self::FAILURE;
        }

        try {
            Mail::to($email)->send(new OperationalTestMail());
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Test email failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Test email sent to '.$email.'.');

        return self::SUCCESS;
    }
}
