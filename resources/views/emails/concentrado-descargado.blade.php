<x-mail::message>
# Concentrado de horarios descargado

Hola {{ $usuario->name }},

Se descargó el concentrado de horarios con los siguientes datos:

- **Periodo escolar:** {{ $periodo->nombre }}
- **Carrera:** {{ $carrera?->nombre ?? 'Todas las carreras' }}
- **Fecha y hora:** {{ now()->format('d/m/Y H:i') }}

Si tú no realizaste esta descarga, contacta al administrador del sistema.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
