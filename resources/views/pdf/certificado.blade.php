<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Finalización</title>
    <style>
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            text-align: center; 
            border: 15px solid #283593; /* Indigo darken-3 border */
            padding: 40px; 
            background-color: #ffffff; 
            margin: 0;
            box-sizing: border-box;
            height: 100vh;
        }
        .header { 
            margin-bottom: 20px; 
        }
        .title { 
            font-size: 54px; 
            font-weight: bold; 
            color: #1a237e; 
            margin-top: 20px; 
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .subtitle { 
            font-size: 24px; 
            color: #555; 
            margin-top: 30px; 
        }
        .name { 
            font-size: 48px; 
            font-weight: bold; 
            margin-top: 30px; 
            color: #000; 
            border-bottom: 2px solid #ccc;
            display: inline-block;
            padding-bottom: 10px;
            min-width: 60%;
        }
        .course-text {
            font-size: 20px;
            color: #555;
            margin-top: 30px;
        }
        .course { 
            font-size: 38px; 
            font-style: italic; 
            margin-top: 20px; 
            color: #b71c1c; 
            font-weight: bold;
        }
        .info {
            font-size: 16px;
            color: #666;
            margin-top: 40px;
        }
        .date { 
            font-size: 18px; 
            margin-top: 50px; 
            color: #333; 
        }
        .signature-container {
            margin-top: 80px;
            width: 100%;
        }
        .signature { 
            border-top: 2px solid #000; 
            display: inline-block; 
            padding-top: 10px; 
            font-size: 18px; 
            font-weight: bold; 
            width: 300px;
        }
    </style>
</head>
<body>
    <div class="header">
        @php
            $image_path = public_path('images/logo-cedac.jpg');
            $image_data = base64_encode(file_get_contents($image_path));
            $src = 'data:image/jpeg;base64,'.$image_data;
        @endphp
        <img src="{{ $src }}" style="height: 120px; margin-bottom: 10px; border-radius: 8px;">
        <p style="margin: 5px 0 0 0; color: #777;">Centro de Educación, Desarrollo y Actualización Continua</p>
    </div>

    <div class="title">Certificado de Término</div>
    
    <div class="subtitle">Se otorga el presente documento a:</div>
    
    <div class="name">{{ $inscripcion->nombre_alumno }}</div>
    
    <div class="course-text">Por haber concluido, acreditado y liquidado satisfactoriamente el programa académico de:</div>
    
    <div class="course">{{ $inscripcion->diplomado->nombre }}</div>
    
    <div class="info">
        Inscrito bajo la matrícula oficial No. <strong>{{ str_pad($inscripcion->id, 5, '0', STR_PAD_LEFT) }}</strong><br>
        Fecha Inicial de Registro: <strong>{{ \Carbon\Carbon::parse($inscripcion->fecha_inscripcion)->format('d/m/Y') }}</strong>
    </div>

    <div class="date">Expedido en la República Mexicana, a los {{ date('d') }} días del mes de {{ date('m') }} de {{ date('Y') }}</div>
    
    <div class="signature-container">
        <div class="signature">
            Dirección Académica CEDAC
        </div>
    </div>
</body>
</html>
