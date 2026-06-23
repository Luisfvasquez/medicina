<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta {{ $consultation->uuid }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 40px; }
        h1 { font-size: 18px; color: #333; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header { margin-bottom: 20px; }
        .section { margin-bottom: 16px; }
        .label { font-weight: bold; color: #555; }
        .grid { display: table; width: 100%; }
        .row { display: table-row; }
        .col { display: table-cell; padding: 4px 8px; }
        .col-label { width: 35%; font-weight: bold; background: #f5f5f5; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background: #f5f5f5; font-weight: bold; }
        .footer { margin-top: 40px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Consulta Médica</h1>
        <p><span class="label">Fecha:</span> {{ $consultation->date->format('d/m/Y H:i') }}</p>
        <p><span class="label">Estado:</span> {{ $consultation->status->value }}</p>
    </div>

    <div class="section">
        <p><span class="label">Paciente:</span> {{ $consultation->patient->full_name ?? 'N/A' }}</p>
        <p><span class="label">Doctor:</span> {{ $consultation->user->full_name ?? 'N/A' }}</p>
        <p><span class="label">Especialidad:</span> {{ $consultation->user->specialty->name ?? 'N/A' }}</p>
        @if($consultation->clinicBranch)
        <p><span class="label">Clínica:</span> {{ $consultation->clinicBranch->name }}</p>
        @endif
    </div>

    @if($consultation->reason)
    <div class="section">
        <p><span class="label">Motivo de consulta:</span></p>
        <p>{{ $consultation->reason }}</p>
    </div>
    @endif

    @if($consultation->diagnosis)
    <div class="section">
        <p><span class="label">Diagnóstico:</span></p>
        <p>{{ $consultation->diagnosis }}</p>
    </div>
    @endif

    @if($consultation->physical_exam)
    <div class="section">
        <p><span class="label">Examen físico:</span></p>
        <p>{{ $consultation->physical_exam }}</p>
    </div>
    @endif

    @if($consultation->treatment_plan)
    <div class="section">
        <p><span class="label">Plan de tratamiento:</span></p>
        <p>{{ $consultation->treatment_plan }}</p>
    </div>
    @endif

    @if($consultation->vitalSign)
    <div class="section">
        <p><span class="label">Signos vitales:</span></p>
        <table class="items-table">
            <tr>
                <td><span class="label">PA:</span> {{ $consultation->vitalSign->blood_pressure ?? 'N/A' }}</td>
                <td><span class="label">FC:</span> {{ $consultation->vitalSign->heart_rate ?? 'N/A' }} lpm</td>
                <td><span class="label">FR:</span> {{ $consultation->vitalSign->respiratory_rate ?? 'N/A' }} rpm</td>
            </tr>
            <tr>
                <td><span class="label">Temperatura:</span> {{ $consultation->vitalSign->temperature ?? 'N/A' }} °C</td>
                <td><span class="label">Peso:</span> {{ $consultation->vitalSign->weight ?? 'N/A' }} kg</td>
                <td><span class="label">Talla:</span> {{ $consultation->vitalSign->height ?? 'N/A' }} cm</td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }} — ID: {{ $consultation->uuid }}</p>
    </div>
</body>
</html>
