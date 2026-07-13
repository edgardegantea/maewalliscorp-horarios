<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;

class AlertaLoginFallido
{
    /**
     * Se dispara cuando el rate limiter de login bloquea un usuario/IP tras
     * varios intentos fallidos consecutivos (ver LoginRequest::ensureIsNotRateLimited).
     */
    public function handle(Lockout $event): void
    {
        Log::channel('seguridad')->warning('Bloqueo por intentos de login fallidos repetidos.', [
            'login' => $event->request->input('login'),
            'ip' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
        ]);
    }
}
