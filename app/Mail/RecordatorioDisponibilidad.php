<?php

namespace App\Mail;

use App\Models\Docente;
use App\Models\PeriodoEscolar;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioDisponibilidad extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Docente $docente,
        public readonly PeriodoEscolar $periodo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recordatorio: captura tu disponibilidad — {$this->periodo->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.recordatorio-disponibilidad',
            with: [
                'docente' => $this->docente,
                'periodo' => $this->periodo,
            ],
        );
    }
}
