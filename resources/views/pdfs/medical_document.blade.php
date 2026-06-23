<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documento Médico {{ $document->uuid }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 40px; }
        h1 { font-size: 18px; color: #333; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header { margin-bottom: 20px; }
        .section { margin-bottom: 16px; }
        .label { font-weight: bold; color: #555; }
        .content-box { border: 1px solid #ddd; padding: 16px; background: #fafafa; white-space: pre-wrap; min-height: 200px; }
        .footer { margin-top: 40px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Documento Médico</h1>
        <p><span class="label">Tipo:</span> {{ $document->type->value ?? 'N/A' }}</p>
        <p><span class="label">Fecha:</span> {{ $document->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section">
        <p><span class="label">Paciente:</span> {{ $document->patient->full_name ?? 'N/A' }}</p>
        <p><span class="label">Médico:</span> {{ $document->user->full_name ?? 'N/A' }}</p>
        <p><span class="label">Profesional ID:</span> {{ $document->user->professional_id ?? 'N/A' }}</p>
        @if($document->clinicBranch)
        <p><span class="label">Clínica:</span> {{ $document->clinicBranch->name }}</p>
        @endif
    </div>

    <div class="section">
        <p><span class="label">Contenido:</span></p>
        <div class="content-box">{{ $document->content ?? 'Sin contenido' }}</div>
    </div>

    <div class="footer">
        <p>Documento ID: {{ $document->uuid }} — Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
