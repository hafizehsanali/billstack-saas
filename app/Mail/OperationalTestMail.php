<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;

class OperationalTestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: platform_name().' mail test');
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.operational-test'
        );
    }
}
