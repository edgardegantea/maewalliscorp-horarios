<?php

namespace App\Mail;

use App\Models\CargaAcademica;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CargaAcademicaReportada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CargaAcademica $carga,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "El docente reportó un problema — {$this->carga->asignatura->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.carga-academica-reportada',
            with: [
                'carga' => $this->carga,
            ],
        );
    }
}
