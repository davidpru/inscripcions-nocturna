<?php

namespace App\Mail;

use App\Models\Inscripcion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DorsalTransferit extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inscripcion $inscripcion,
        public string $nomOriginal,
        public string $nomNouParticipant
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'El teu dorsal ha estat transferit - Nocturna Fredes-Paüls',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dorsal-transferit',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
