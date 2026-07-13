<x-mail::message>
# Captura tu disponibilidad

Hola {{ $docente->user->name }},

El periodo escolar **{{ $periodo->nombre }}** está por comenzar (o ya inició) y todavía no has registrado tu
disponibilidad horaria. Sin ella, el administrador no puede asignarte tu carga académica.

<x-mail::button :url="url('/mi/disponibilidad')">
Capturar mi disponibilidad
</x-mail::button>

Si ya la registraste, puedes ignorar este mensaje.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
