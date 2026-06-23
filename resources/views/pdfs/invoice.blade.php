<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->uuid }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 40px; }
        h1 { font-size: 18px; color: #333; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header { margin-bottom: 20px; }
        .section { margin-bottom: 16px; }
        .label { font-weight: bold; color: #555; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background: #f5f5f5; font-weight: bold; }
        .totals { margin-top: 16px; text-align: right; }
        .totals p { margin: 4px 0; }
        .status-box { display: inline-block; padding: 4px 12px; border: 1px solid #333; font-weight: bold; }
        .footer { margin-top: 40px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Factura</h1>
        <p><span class="label">Nº:</span> {{ $invoice->uuid }}</p>
        <p><span class="label">Fecha:</span> {{ $invoice->created_at->format('d/m/Y') }}</p>
        @if($invoice->due_date)
        <p><span class="label">Vencimiento:</span> {{ $invoice->due_date->format('d/m/Y') }}</p>
        @endif
        <p><span class="label">Estado:</span> <span class="status-box">{{ $invoice->status->value }}</span></p>
    </div>

    <div class="section">
        <p><span class="label">Paciente:</span> {{ $invoice->patient->full_name ?? 'N/A' }}</p>
        <p><span class="label">Doctor/Proveedor:</span> {{ $invoice->user->full_name ?? 'N/A' }}</p>
        @if($invoice->clinicBranch)
        <p><span class="label">Clínica:</span> {{ $invoice->clinicBranch->name }}</p>
        @endif
    </div>

    <div class="section">
        <p><span class="label">Conceptos:</span></p>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $invoice->currency }} {{ number_format($item->total, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4">Sin items registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals">
        <p><span class="label">Subtotal:</span> {{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</p>
        @if($invoice->tax > 0)
        <p><span class="label">Impuesto:</span> {{ $invoice->currency }} {{ number_format($invoice->tax, 2) }}</p>
        @endif
        @if($invoice->discount > 0)
        <p><span class="label">Descuento:</span> -{{ $invoice->currency }} {{ number_format($invoice->discount, 2) }}</p>
        @endif
        <p style="font-size: 14px; font-weight: bold;"><span class="label">TOTAL:</span> {{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</p>
        <p><span class="label">Total Pagado:</span> {{ $invoice->currency }} {{ number_format($invoice->totalPaid(), 2) }}</p>
        <p><span class="label">Pendiente:</span> {{ $invoice->currency }} {{ number_format(max(0, $invoice->totalDue()), 2) }}</p>
    </div>

    @if($invoice->notes)
    <div class="section">
        <p><span class="label">Notas:</span></p>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Factura ID: {{ $invoice->uuid }} — Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
