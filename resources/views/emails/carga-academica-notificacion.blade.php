<x-mail::message>
@if ($accion === 'asignada')
# Nueva clase asignada

Se te asignó una nueva clase en tu horario:
@elseif ($accion === 'actualizada')
# Se actualizó una de tus clases

Uno de tus horarios cambió:
@else
# Se eliminó una de tus clases

Se eliminó la siguiente clase de tu horario:
@endif

- **Asignatura:** {{ $carga->asignatura->nombre }}
- **Grupo:** {{ $carga->grupo->nombre }}
- **Aula:** {{ $carga->aula->nombre }}
- **Día:** {{ $dia }}
- **Horario:** {{ substr($carga->hora_inicio, 0, 5) }} - {{ substr($carga->hora_fin, 0, 5) }}
- **Periodo escolar:** {{ $carga->periodoEscolar->nombre }}

Si crees que esto es un error, contacta al administrador del sistema.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
