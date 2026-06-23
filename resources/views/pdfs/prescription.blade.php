<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta {{ $prescription->uuid }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 40px; }
        h1 { font-size: 18px; color: #333; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header { margin-bottom: 20px; }
        .section { margin-bottom: 16px; }
        .label { font-weight: bold; color: #555; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        .items-table th { background: #f5f5f5; font-weight: bold; }
        .med-name { font-weight: bold; }
        .footer { margin-top: 40px; font-size: 10px; color: #888; text-align: center; }
        .rx-box { border: 2px solid #333; padding: 16px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="rx-box">
        ℞ RECETA MÉDICA
    </div>

    <div class="header">
        <p><span class="label">Fecha:</span> {{ $prescription->date->format('d/m/Y') }}</p>
        <p><span class="label">Válida hasta:</span> {{ $prescription->expiration_date ? $prescription->expiration_date->format('d/m/Y') : 'N/A' }}</p>
        <p><span class="label">Estado:</span> {{ $prescription->status->value }}</p>
    </div>

    <div class="section">
        <p><span class="label">Paciente:</span> {{ $prescription->patient->full_name ?? 'N/A' }}</p>
        <p><span class="label">Cédula:</span> {{ $prescription->patient->national_id ?? 'N/A' }}</p>
    </div>

    <div class="section">
        <p><span class="label">Médico:</span> {{ $prescription->user->full_name ?? 'N/A' }}</p>
        <p><span class="label">Profesional ID:</span> {{ $prescription->user->professional_id ?? 'N/A' }}</p>
        <p><span class="label">Especialidad:</span> {{ $prescription->user->specialty->name ?? 'N/A' }}</p>
        @if($prescription->clinicBranch)
        <p><span class="label">Clínica:</span> {{ $prescription->clinicBranch->name }}</p>
        @endif
    </div>

    <div class="section">
        <p><span class="label">Medicamentos:</span></p>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Medicamento</th>
                    <th>Concentración</th>
                    <th>Presentación</th>
                    <th>Dósis</th>
                    <th>Frecuencia</th>
                    <th>Duración</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prescription->items as $item)
                <tr>
                    <td class="med-name">{{ $item->medication->name ?? 'N/A' }}</td>
                    <td>{{ $item->medication->concentration ?? 'N/A' }}</td>
                    <td>{{ $item->medication->presentation ?? 'N/A' }}</td>
                    <td>{{ $item->dosage }}</td>
                    <td>{{ $item->frequency }}</td>
                    <td>{{ $item->duration }}</td>
                    <td>{{ $item->quantity }}</td>
                </tr>
                @if($item->instructions)
                <tr>
                    <td colspan="7"><span class="label">Instrucciones:</span> {{ $item->instructions }}</td>
                </tr>
                @endif
                @empty
                <tr><td colspan="7">Sin medicamentos registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($prescription->notes)
    <div class="section">
        <p><span class="label">Notas:</span></p>
        <p>{{ $prescription->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Receta ID: {{ $prescription->uuid }} — Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
