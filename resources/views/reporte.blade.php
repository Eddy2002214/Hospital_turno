<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Rol Mensual</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 3px;
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .col-day {
            width: 20px;
        }
        .legends {
            display: flex;
            justify-content: flex-start;
            gap: 20px;
            margin-bottom: 40px;
        }
        .legends div {
            border: 1px solid #000;
            padding: 5px 10px;
        }
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 50px;
        }
        .signature-box {
            text-align: center;
            width: 200px;
            border-top: 1px solid black;
            padding-top: 5px;
        }
        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>HOSPITAL JAPONÉS</h1>
        <h2>ROL DE TURNOS MENSUAL</h2>
        <p><strong>SERVICIO/DEPARTAMENTO:</strong> {{ $departamento }} &nbsp;&nbsp;&nbsp; <strong>MES:</strong> {{ $mes }} <strong>AÑO:</strong> {{ $anio }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">Ítem</th>
                <th rowspan="2">Cód. Biom.</th>
                <th rowspan="2" style="width: 200px;">Nombre Completo</th>
                <th rowspan="2">Cargo</th>
                <th colspan="31">Días del Mes</th>
            </tr>
            <tr>
                @for ($i = 1; $i <= 31; $i++)
                    <th class="col-day">{{ $i }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @php $currentDept = null; @endphp
            @foreach ($matrix as $index => $row)
                @if ($departamento === 'TODOS LOS SERVICIOS' && $currentDept !== $row['departamento'])
                    @php $currentDept = $row['departamento']; @endphp
                    <tr style="background-color: #e2e8f0; font-weight: bold;">
                        <td colspan="36" class="text-left" style="padding: 5px 8px; font-size: 11px; text-transform: uppercase;">
                            ÁREA / SERVICIO: {{ $row['departamento'] }}
                        </td>
                    </tr>
                @endif
                <tr @if($row['is_jefe']) style="background-color: #f8fafc;" @endif>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ $row['codigo_biometrico'] }}</td>
                    <td class="text-left">
                        <strong>{{ $row['nombre'] }}</strong>
                        @if($row['is_jefe']) <span style="font-size: 9px; color: #b45309; font-weight: bold;">(JEFE)</span> @endif
                    </td>
                    <td>{{ $row['is_jefe'] ? ($row['cargo'].': '.$row['departamento']) : $row['cargo'] }}</td>
                    @for ($i = 1; $i <= 31; $i++)
                        <td>{{ $row['shifts'][$i] }}</td>
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legends">
        <strong>LEYENDA DE TURNOS:</strong>
        @foreach ($turnos as $t)
            <div><strong>{{ $t->codigo_turno }}</strong>: {{ $t->nombre_turno }} ({{ $t->hora_entrada }} - {{ $t->hora_salida }})</div>
        @endforeach
        <div><strong>L</strong>: Libre</div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            Firma Jefe de Servicio
        </div>
        <div class="signature-box">
            Firma Recursos Humanos
        </div>
        <div class="signature-box">
            Sello de Recepción
        </div>
    </div>

    <script>
        // Imprimir automáticamente al abrir (opcional)
        // window.print();
    </script>
</body>
</html>
