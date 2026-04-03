<?php

namespace App\Mail;

use App\Models\Inscripcion;
use App\Models\Participante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CambioDorsalConfirmado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inscripcion $inscripcion,
        public Participante $nouParticipant
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Canvi de dorsal confirmat - Nocturna Fredes-Paüls',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.canvi-dorsal-confirmado',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
