<table>
    <thead>
        <tr>
            <th colspan="2" style="font-weight: bold; font-size: 14px; text-align: center;">Resumen de {{ $datos['tipoRegion'] }}: {{ $datos['region']->nombre }}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight: bold;">Población Total</td>
            <td>{{ number_format($datos['poblacionTotal']) }} habitantes</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Superficie Total</td>
            <td>{{ number_format($datos['superficieTotal'], 2) }} km²</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Total de Municipios</td>
            <td>{{ $datos['municipios']->count() }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
        </tr>
        <tr>
            <th colspan="2" style="font-weight: bold; background-color: #f3f3f3;">Lista de Municipios Integrantes</th>
        </tr>
        @foreach($datos['municipios'] as $idx => $muni)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $muni->nombre }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
