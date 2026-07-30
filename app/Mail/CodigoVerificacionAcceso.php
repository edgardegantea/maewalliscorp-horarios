<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodigoVerificacionAcceso extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $usuario,
        public readonly string $codigo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de acceso — Cargas Académicas',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.codigo-verificacion',
            with: [
                'usuario' => $this->usuario,
                'codigo' => $this->codigo,
            ],
        );
    }
}
