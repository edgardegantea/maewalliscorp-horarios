<?php

use App\Models\User;
use App\Services\TwoFactorAuthenticationProvider;

it('un admin puede activar 2fa: generar secreto, confirmar con código válido', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('two-factor.enable'))->assertRedirect();
    expect($admin->fresh()->two_factor_secret)->not->toBeNull();
    expect($admin->fresh()->hasTwoFactorEnabled())->toBeFalse();

    $provider = app(TwoFactorAuthenticationProvider::class);
    $codigo = $provider->generarCodigoActual($admin->fresh()->two_factor_secret);

    $this->actingAs($admin)
        ->post(route('two-factor.confirm'), ['codigo' => $codigo])
        ->assertRedirect();

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('un docente no puede activar 2fa', function () {
    $docente = User::factory()->docente()->create();

    $this->actingAs($docente)->post(route('two-factor.enable'))->assertForbidden();
});

it('al iniciar sesión con 2fa activo, se pide el código antes de completar el login', function () {
    $admin = User::factory()->admin()->create(['username' => 'admin2fa']);
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secreto = $provider->generarSecreto();
    $admin->forceFill(['two_factor_secret' => $secreto, 'two_factor_confirmed_at' => now()])->save();

    $respuesta = $this->post(route('login'), ['login' => 'admin2fa', 'password' => 'password']);
    $respuesta->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();

    $codigo = $provider->generarCodigoActual($secreto);

    $this->post(route('two-factor.challenge.store'), ['codigo' => $codigo])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
});

it('rechaza un código de dos factores inválido en el challenge', function () {
    $admin = User::factory()->admin()->create(['username' => 'admin2fa2']);
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secreto = $provider->generarSecreto();
    $admin->forceFill(['two_factor_secret' => $secreto, 'two_factor_confirmed_at' => now()])->save();

    $this->post(route('login'), ['login' => 'admin2fa2', 'password' => 'password']);

    $this->post(route('two-factor.challenge.store'), ['codigo' => '000000'])
        ->assertSessionHasErrors('codigo');

    $this->assertGuest();
});
