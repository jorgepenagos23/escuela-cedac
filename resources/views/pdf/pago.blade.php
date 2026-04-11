<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1a237e; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; font-size: 12px; }
        .folio-box { position: absolute; top: 20px; right: 20px; text-align: right; }
        .folio-number { font-size: 18px; font-weight: bold; color: #d32f2f; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f5f5f5; color: #1a237e; width: 40%; font-size: 12px; text-transform: uppercase;}
        td { font-size: 14px; }
        .amount-row { background-color: #e8f5e9; }
        .amount { font-size: 18px; font-weight: bold; color: #2e7d32; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 20px; }
        .signature-box { margin-top: 60px; text-align: center; }
        .signature-line { width: 200px; border-bottom: 1px solid #333; margin: 0 auto; display: block; }
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
        <p>Comprobante Oficial de Pago de Colegiatura</p>
    </div>

    <div class="folio-box">
        <div>FOLIO DE RECIBO</div>
        <div class="folio-number">N° {{ str_pad($pago->idpago, 6, '0', STR_PAD_LEFT) }}</div>
    </div>

    <table>
        <tr>
            <th>Fecha de Operación</th>
            <td>{{ \Carbon\Carbon::parse($pago->fecha)->format('d \d\e F \d\e Y') }}</td>
        </tr>
        <tr>
            <th>Nombre del Alumno</th>
            <td><strong>{{ $pago->alumno }}</strong></td>
        </tr>
        <tr>
            <th>Concepto de Pago</th>
            <td>Pago de Mensualidad - Diplomado en <strong>{{ $pago->diplomado }}</strong></td>
        </tr>
        <tr>
            <th>Método de Depósito</th>
            <td>
                Cuenta: {{ $pago->titular }}<br>
                Banco: {{ $pago->banco }}<br>
                {{ $pago->numero_cuenta ? 'Número: ' . $pago->numero_cuenta : '' }}
                {{ $pago->CLABE ? 'CLABE: ' . $pago->CLABE : '' }}
            </td>
        </tr>
        <tr class="amount-row">
            <th>Monto Total Recibido</th>
            <td class="amount">${{ number_format($pago->monto, 2) }} MXN</td>
        </tr>
        <tr>
            <th>Cajero Receptor (Sistema)</th>
            <td>{{ $pago->cajero }}</td>
        </tr>
    </table>

    <div class="signature-box">
        <span class="signature-line"></span>
        <br>Sello y Firma de Caja
    </div>

    <div class="footer">
        Este documento es un comprobante de pago con validez académica para el Centro Educativo. Conserve este documento para futuras aclaraciones.<br>
        Impreso el {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>
