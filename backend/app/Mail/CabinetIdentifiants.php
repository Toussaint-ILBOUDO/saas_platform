<?php

namespace App\Mail;

use App\Models\Cabinet;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Identifiants d'administration envoyés au créateur d'un cabinet (T2.4).
 */
class CabinetIdentifiants extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Cabinet $cabinet,
        public string $email,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SaasCD — ' . $this->cabinet->nom . ' : vos identifiants'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cabinet-identifiants'
        );
    }
}