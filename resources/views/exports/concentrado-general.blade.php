@php
    $dias = [1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO', 7 => 'DOMINGO'];
@endphp
<table>
    <tbody>
        @foreach ($bloques as $bloque)
            @php
                $estiloEncabezado = "background-color:#{$bloque['color']};color:#FFFFFF;font-weight:bold;";
                $estiloFila = "background-color:#{$bloque['colorClaro']};";
            @endphp
            <tr>
                <th style="{{ $estiloEncabezado }}">CARRERA:</th>
                <td colspan="{{ count($dias) + 2 }}" style="{{ $estiloEncabezado }}">{{ $bloque['carrera'] }}</td>
            </tr>
            <tr>
                <th style="{{ $estiloFila }}">SEMESTRE:</th>
                <td style="{{ $estiloFila }}">{{ $bloque['semestre'] }}</td>
                <td style="{{ $estiloFila }}"></td>
                <th style="{{ $estiloFila }}">GRUPO:</th>
                <td style="{{ $estiloFila }}">{{ $bloque['grupo'] }}</td>
                <td style="{{ $estiloFila }}"></td>
                <th colspan="{{ count($dias) - 3 }}" style="{{ $estiloFila }}">MODALIDAD: {{ $bloque['modalidad'] }}</th>
            </tr>
            <tr>
                <th style="{{ $estiloFila }}">CLAVE ASIGNATURA</th>
                <th style="{{ $estiloFila }}">ASIGNATURA</th>
                <th style="{{ $estiloFila }}">DOCENTE</th>
                @foreach ($dias as $nombre)
                    <th style="{{ $estiloFila }}">{{ $nombre }}</th>
                @endforeach
            </tr>
            @foreach ($bloque['filas'] as $fila)
                <tr>
                    <td style="{{ $estiloFila }}">{{ $fila['clave'] }}</td>
                    <td style="{{ $estiloFila }}">{{ $fila['asignatura'] }}</td>
                    <td style="{{ $estiloFila }}">{{ $fila['docente'] }}</td>
                    @foreach ($dias as $numero => $nombre)
                        <td style="{{ $estiloFila }}">{{ $fila['dias'][$numero] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td colspan="{{ count($dias) + 3 }}"></td>
            </tr>
        @endforeach
    </tbody>
</table>
