<?php

namespace App\Mail;

use App\Models\Clinica;
use App\Models\SolicitudReferencia;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevaSolicitudReferenciaInterna extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Clinica $clinica, public SolicitudReferencia $solicitud) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Nueva solicitud de referencia — '.$this->clinica->nombre,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.nueva-solicitud-referencia-interna',
        );
    }
}
