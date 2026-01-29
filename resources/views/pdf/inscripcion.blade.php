<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripció - {{ $participante->nombre }} {{ $participante->apellidos }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1e293b;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #dc2626;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 16px;
            color: #64748b;
            font-weight: normal;
        }
        .content {
            display: table;
            width: 100%;
        }
        .main-info {
            display: table-cell;
            width: 65%;
            vertical-align: top;
            padding-right: 20px;
        }
        .qr-section {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            text-align: center;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #dc2626;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #475569;
            display: inline-block;
            width: 140px;
        }
        .info-value {
            color: #1e293b;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-green {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-blue {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-amber {
            background-color: #fef3c7;
            color: #92400e;
        }
        .qr-code {
            margin-bottom: 10px;
        }
        .qr-code img {
            width: 150px;
            height: 150px;
        }
        .qr-text {
            font-size: 10px;
            color: #64748b;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #64748b;
        }
        .total-box {
            background-color: #f8fafc;
            border: 2px solid #dc2626;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-top: 20px;
        }
        .total-label {
            font-size: 12px;
            color: #64748b;
        }
        .total-amount {
            font-size: 28px;
            font-weight: bold;
            color: #dc2626;
        }
        .services-list {
            margin-top: 10px;
        }
        .service-item {
            padding: 5px 0;
            border-bottom: 1px dotted #e2e8f0;
        }
        .service-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nocturna Fredes-Paüls {{ $edicion->anio }}</h1>
        <h2>Confirmació d'Inscripció</h2>
    </div>

    <div class="content">
        <div class="main-info">
            <div class="section">
                <div class="section-title">Dades del Participant</div>
                <div class="info-row">
                    <span class="info-label">Nom complet:</span>
                    <span class="info-value">{{ $participante->nombre }} {{ $participante->apellidos }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">DNI:</span>
                    <span class="info-value">{{ $participante->dni }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $participante->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Telèfon:</span>
                    <span class="info-value">{{ $participante->telefono }}</span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Dades de la Inscripció</div>
                <div class="info-row">
                    <span class="info-label">Nº Inscripció:</span>
                    <span class="info-value"><strong>#{{ $inscripcion->id }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data inscripció:</span>
                    <span class="info-value">{{ $inscripcion->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tarifa aplicada:</span>
                    <span class="info-value">{{ $inscripcion->tarifa_aplicada }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estat:</span>
                    <span class="badge badge-green">PAGAT</span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Opcions Contractades</div>
                <div class="services-list">
                    <div class="service-item">
                        <span class="info-label">Soci UEC Tortosa:</span>
                        <span class="info-value">{{ $inscripcion->es_socio_uec ? 'Sí' : 'No' }}</span>
                    </div>
                    <div class="service-item">
                        <span class="info-label">Federat:</span>
                        <span class="info-value"> {{ $inscripcion->esta_federado ? 'Sí' : 'No' }}
                            @if($inscripcion->esta_federado && $inscripcion->numero_licencia)
                                ({{ $inscripcion->numero_licencia }})
                            @endif
                        </span>
                    </div>
                    @if($inscripcion->club)
                    <div class="service-item">
                        <span class="info-label">Club:</span>
                        <span class="info-value">{{ $inscripcion->club }}</span>
                    </div>
                    @endif
                    <div class="service-item">
                        <span class="info-label">Autobús:</span>
                        <span class="info-value">
                            @if($inscripcion->necesita_autobus)
                                Sí - Sortida des de {{ ucfirst($inscripcion->parada_autobus) }}
                            @else
                                No
                            @endif
                        </span>
                    </div>
                    <div class="service-item">
                        <span class="info-label">Assegurança anul·lació:</span>
                        <span class="info-value">{{ $inscripcion->seguro_anulacion ? 'Sí' : 'No' }}</span>
                    </div>
                    <div class="service-item">
                        <span class="info-label">Celíac:</span>
                        <span class="info-value">{{ $inscripcion->es_celiaco ? 'Sí' : 'No' }}</span>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Talles de Samarreta</div>
                <div class="info-row">
                    <span class="info-label">Samarreta Caro:</span>
                    <span class="info-value">{{ strtoupper($inscripcion->talla_camiseta_caro) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Samarreta Paüls:</span>
                    <span class="info-value">{{ strtoupper($inscripcion->talla_camiseta_pauls) }}</span>
                </div>
            </div>
        </div>

        <div class="qr-section">
            <div class="qr-code">
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code">
            </div>
            <div class="qr-text">
                Escaneja aquest codi QR<br>
                per verificar la inscripció
            </div>

            <div class="total-box">
                <div class="total-label">Total Pagat</div>
                <div class="total-amount">{{ number_format($inscripcion->precio_total, 2, ',', '.') }}€</div>
                @if($inscripcion->descuento_cupon > 0)
                    <div style="font-size: 10px; color: #166534; margin-top: 5px;">
                        (Descompte cupó: -{{ number_format($inscripcion->descuento_cupon, 2, ',', '.') }}€)
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>UEC Tortosa</strong> - Nocturna del Caro {{ $edicion->anio }}</p>
        <p>Aquest document serveix com a comprovant d'inscripció. Presenta'l el dia de la cursa per recollir el teu dorsal.</p>
        <p style="margin-top: 10px;">Nº Pedido: {{ $inscripcion->numero_pedido }} | Nº Autorització: {{ $inscripcion->numero_autorizacion }}</p>
    </div>
</body>
</html>
