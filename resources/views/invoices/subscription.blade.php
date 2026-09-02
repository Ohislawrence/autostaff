<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoice->invoice_number }}</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 40px; }
    .invoice { max-width: 800px; margin: 0 auto; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111827; padding-bottom: 20px; }
    .muted { color: #6b7280; }
    table { width: 100%; border-collapse: collapse; margin: 24px 0; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
    th { background: #f9fafb; }
    .totals { width: 320px; margin-left: auto; }
    .totals td { border: none; }
    .paid { display: inline-block; background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 4px; font-weight: bold; }
    .print-btn { position: fixed; top: 20px; right: 20px; background: #2563eb; color: #fff; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; }
    @media print { .print-btn { display: none; } body { padding: 0; } }
</style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print / Save PDF</button>

    @php $symbol = $invoice->currency === 'NGN' ? '₦' : '$'; @endphp

    <div class="invoice">
        <div class="header">
            <div>
                <h1 style="margin:0">{{ $settings->company_name ?: config('app.name') }}</h1>
                @if($settings->company_address)<p class="muted" style="margin:4px 0">{{ $settings->company_address }}</p>@endif
                @if($settings->company_tax_id)<p class="muted" style="margin:0">TIN / VAT No: {{ $settings->company_tax_id }}</p>@endif
            </div>
            <div style="text-align:right">
                <h2 style="margin:0">INVOICE</h2>
                <p style="margin:6px 0 2px"><strong>{{ $invoice->invoice_number }}</strong></p>
                <p class="muted" style="margin:0">Issued: {{ $invoice->created_at->format('d M Y') }}</p>
                @if($invoice->status === 'paid')<span class="paid" style="margin-top:8px">PAID</span>@endif
            </div>
        </div>

        <div style="margin-top:20px">
            <p style="margin:0"><strong>Billed to:</strong></p>
            <p style="margin:2px 0">{{ $organization->name }}</p>
            @if($organization->email)<p class="muted" style="margin:0">{{ $organization->email }}</p>@endif
            @if($organization->address)<p class="muted" style="margin:0">{{ $organization->address }}</p>@endif
        </div>

        <table>
            <thead><tr><th>Description</th><th>Qty</th><th style="text-align:right">Amount</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $invoice->description }} — {{ $plan?->name ?? 'Plan' }} (monthly)</td>
                    <td>1</td>
                    <td style="text-align:right">{{ $symbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Subtotal</td><td style="text-align:right">{{ $symbol }}{{ number_format($invoice->subtotal, 2) }}</td></tr>
            @foreach(($invoice->taxes ?? []) as $tax)
                <tr><td>{{ $tax['name'] }} ({{ $tax['rate'] }}%)</td><td style="text-align:right">{{ $symbol }}{{ number_format($tax['amount'], 2) }}</td></tr>
            @endforeach
            <tr><td><strong>Total</strong></td><td style="text-align:right"><strong>{{ $symbol }}{{ number_format($invoice->total, 2) }}</strong></td></tr>
        </table>

        <p class="muted" style="margin-top:28px">
            Payment reference: {{ $invoice->payment_reference ?? '—' }}<br>
            Paid on: {{ $invoice->paid_at?->format('d M Y H:i') ?? '—' }}
        </p>
        <p class="muted" style="margin-top:20px; font-size:12px">This is a computer-generated invoice and is valid without a signature.</p>
    </div>
</body>
</html>
