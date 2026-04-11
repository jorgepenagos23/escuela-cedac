<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Inscripción</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1a237e; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; font-size: 12px; }
        .folio-box { position: absolute; top: 20px; right: 20px; text-align: right; }
        .folio-number { font-size: 18px; font-weight: bold; color: #d32f2f; }
        .section-title { font-size: 16px; color: #1a237e; border-bottom: 1px solid #1a237e; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; font-weight: bold;}
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f5f5f5; color: #1a237e; width: 40%; font-size: 12px; text-transform: uppercase;}
        td { font-size: 14px; }
        .amount-row { background-color: #e8f5e9; }
        .amount { font-size: 18px; font-weight: bold; color: #2e7d32; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 20px; }
        .signature-box { margin-top: 50px; text-align: center; width: 45%; display: inline-block;}
        .signature-line { width: 80%; border-bottom: 1px solid #333; margin: 0 auto; display: block; }
    </style>
</head>
<body>
    <div class="header">
        @php
            $image_path = public_path('images/logo-cedac.jpg');
            $image_data = base64_encode(file_get_contents($image_path));
            $src = 'data:image/jpeg;base64,'.$image_data;
        @endphp
        <img src="{{ $src }}" style="height: 100px; margin-bottom: 5px; border-radius: 8px;">
        <br>
        <strong>Centro Educativo de Alta Capacitación</strong>
        <p>Ficha Individual de Inscripción y Generación de Expediente</p>
    </div>

    <div class="folio-box">
        <div>MATRÍCULA ASIGNADA</div>
        <div class="folio-number">N° {{ str_pad($inscripcion->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="section-title">Datos del Estudiante</div>
    <table>
        <tr>
            <th>Nombre Completo</th>
            <td><strong>{{ $inscripcion->nombre_alumno }}</strong></td>
        </tr>
        <tr>
            <th>CURP</th>
            <td>{{ $inscripcion->curp ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Fecha de Alta Organizacional</th>
            <td>{{ \Carbon\Carbon::parse($inscripcion->fecha_inscripcion)->format('d \d\e F \d\e Y') }}</td>
        </tr>
        <tr>
            <th>Celular de Contacto</th>
            <td>{{ $inscripcion->celular }}</td>
        </tr>
        <tr>
            <th>Correo Electrónico</th>
            <td>{{ $inscripcion->correo ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Dirección</th>
            <td>{{ $inscripcion->direccion_completa ?? 'N/A' }} {{ $inscripcion->municipio ? ', ' . $inscripcion->municipio : '' }} {{ $inscripcion->estado ? ', ' . $inscripcion->estado : '' }}</td>
        </tr>
        <tr>
            <th>Contacto de Emergencia</th>
            <td>{{ $inscripcion->nombre_emergencia ?? 'N/A' }} {{ $inscripcion->parentesco_emergencia ? '(' . $inscripcion->parentesco_emergencia . ')' : '' }}</td>
        </tr>
    </table>

    <div class="section-title">Datos del Programa Académico</div>
    <table>
        <tr>
            <th>Diplomado Matriculado</th>
            <td><strong>{{ $inscripcion->diplomado->nombre ?? 'N/A' }}</strong></td>
        </tr>
        <tr>
            <th>Grupo Asignado</th>
            <td>{{ $grupoData ? 'Grupo ' . $grupoData->grupo . ' (' . $grupoData->campaña . ')' : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Tutor Asignado</th>
            <td>{{ $inscripcion->usuarioTutor->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Asesor (Ejecutivo de Venta)</th>
            <td>{{ $inscripcion->usuarioAsesor->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="section-title">Condiciones Financieras de Ingreso</div>
    <table>
        <tr>
            <th>Costo Oficial del Diplomado</th>
            <td style="font-weight: bold;">
                ${{ number_format($inscripcion->diplomado->costo_total ?? 0, 2) }} MXN
            </td>
        </tr>
        <tr class="amount-row">
            <th>Monto de Inscripción Cubierto</th>
            <td class="amount">${{ number_format($inscripcion->monto_inscripcion, 2) }} MXN</td>
        </tr>
        <tr>
            <th>Saldo Restante (Colegiaturas)</th>
            <td>${{ number_format(($inscripcion->diplomado->costo_total ?? 0) - $inscripcion->monto_inscripcion, 2) }} MXN</td>
        </tr>
        <tr>
            <th>Compromiso de Primer Pago</th>
            <td>{{ \Carbon\Carbon::parse($inscripcion->fecha_primer_pago_colegiatura)->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 40px;">
        <div class="signature-box">
            <span class="signature-line"></span>
            <br>Firma del Alumno de Conformidad
        </div>
        <div class="signature-box">
            <span class="signature-line"></span>
            <br>Sello y Firma de Dirección
        </div>
    </div>

    <div class="footer">
        Este documento ampara la inscripción y aseguramiento de lugar para el programa referido.<br>
        Es responsabilidad del alumno cubrir el saldo restante mediante cuotas para tener derecho a certificación.<br>
        Impreso el {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>
