<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationProvider
{
    private Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    public function generarSecreto(): string
    {
        return $this->engine->generateSecretKey();
    }

    public function verificarCodigo(string $secreto, string $codigo): bool
    {
        return $this->engine->verifyKey($secreto, $codigo);
    }

    /**
     * Código válido para el instante actual. Solo se usa en pruebas
     * automatizadas (simula lo que generaría la app autenticadora del usuario).
     */
    public function generarCodigoActual(string $secreto): string
    {
        return $this->engine->getCurrentOtp($secreto);
    }

    public function qrSvg(string $email, string $secreto): string
    {
        $url = $this->engine->getQRCodeUrl(config('app.name'), $email, $secreto);

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($url);
    }
}
