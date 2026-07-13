<?php

namespace App\Mail;

use App\Models\Carrera;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConcentradoDescargado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $usuario,
        public readonly PeriodoEscolar $periodo,
        public readonly ?Carrera $carrera = null,
    ) {}

    public function envelope(): Envelope
    {
        $alcance = $this->carrera ? "· {$this->carrera->nombre}" : '(todas las carreras)';

        return new Envelope(
            subject: "Concentrado de horarios descargado — {$this->periodo->nombre} {$alcance}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.concentrado-descargado',
            with: [
                'usuario' => $this->usuario,
                'periodo' => $this->periodo,
                'carrera' => $this->carrera,
            ],
        );
    }
}
