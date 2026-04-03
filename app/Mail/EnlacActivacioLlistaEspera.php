<?php

namespace App\Mail;

use App\Models\Inscripcion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnlacActivacioLlistaEspera extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inscripcion $inscripcion,
        public string $urlActivacio
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tens una plaça disponible! - Nocturna Fredes-Paüls',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enlac-activacio-llista-espera',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
