<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;

it('registra una alerta en el canal de seguridad cuando el login se bloquea por intentos repetidos', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('seguridad')
        ->andReturnSelf();
    Log::shouldReceive('warning')->once();

    User::factory()->create(['email' => 'objetivo@example.com']);

    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', ['login' => 'objetivo@example.com', 'password' => 'incorrecta']);
    }
});
