<x-mail::message>
# El docente reportó un problema con su horario

**{{ $carga->docente->user->name }}** marcó como conflictiva la siguiente clase:

- **Asignatura:** {{ $carga->asignatura->nombre }}
- **Grupo(s):** {{ $carga->nombreGrupos() }}
- **Aula:** {{ $carga->aula->nombre }}
- **Horario:** {{ substr($carga->hora_inicio, 0, 5) }} - {{ substr($carga->hora_fin, 0, 5) }}

**Comentario del docente:**
{{ $carga->comentario_docente }}

Revisa esta asignación en el sistema.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
