@php
    $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
@endphp
<table>
    <thead>
        <tr>
            <th colspan="{{ count($dias) + 1 }}">{{ $titulo }}</th>
        </tr>
        <tr>
            <th>Hora</th>
            @foreach ($dias as $nombre)
                <th>{{ $nombre }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($slots as $hora)
            <tr>
                <td>{{ $hora }} - {{ $siguiente($hora) }}</td>
                @foreach ($dias as $numero => $nombre)
                    @php($carga = $celda($numero, $hora))
                    <td>
                        @if ($carga)
                            {{ $carga['linea1'] }}@if (! empty($carga['linea2']))
{{ "\n" }}{{ $carga['linea2'] }}@endif
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
