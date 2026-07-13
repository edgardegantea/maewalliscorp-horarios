<?php

namespace App\Mail;

use App\Models\CargaAcademica;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CargaAcademicaNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public const ASIGNADA = 'asignada';

    public const ACTUALIZADA = 'actualizada';

    public const ELIMINADA = 'eliminada';

    private const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    public function __construct(
        public readonly CargaAcademica $carga,
        public readonly string $accion,
    ) {}

    public function envelope(): Envelope
    {
        $titulos = [
            self::ASIGNADA => 'Nueva clase asignada',
            self::ACTUALIZADA => 'Se actualizó una de tus clases',
            self::ELIMINADA => 'Se eliminó una de tus clases',
        ];

        return new Envelope(
            subject: "{$titulos[$this->accion]} — {$this->carga->asignatura->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.carga-academica-notificacion',
            with: [
                'accion' => $this->accion,
                'carga' => $this->carga,
                'dia' => self::DIAS[$this->carga->dia_semana],
            ],
        );
    }
}
