<x-mail::message>
# Tu código de acceso

Hola {{ $usuario->name }},

Usa el siguiente código para continuar con el acceso a tu cuenta y establecer tu contraseña:

<x-mail::panel>
<div style="font-size: 28px; font-weight: bold; letter-spacing: 4px; text-align: center;">
{{ $codigo }}
</div>
</x-mail::panel>

Este código expira en 15 minutos. Si tú no solicitaste este código, puedes ignorar este mensaje.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
