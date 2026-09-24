<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $payment->payment_code }}</title>
    <style>
        body { font-family: monospace, sans-serif; padding: 20px; max-width: 400px; margin: 0 auto; border: 1px solid #ccc; }
        .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 18px; font-weight: bold; }
        .row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; }
        .bold { font-weight: bold; }
        .footer { text-align: center; border-top: 1px dashed #000; margin-top: 15px; padding-top: 10px; font-size: 12px; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div class="title">{{ $gymProfile->name ?? 'GMS GYM' }}</div>
        <div>{{ $payment->branch->name ?? '' }}</div>
        <div>{{ $payment->branch->phone ?? '' }}</div>
    </div>

    <div class="row"><span class="bold">Receipt #:</span> <span>{{ $payment->payment_code }}</span></div>
    <div class="row"><span class="bold">Date:</span> <span>{{ $payment->payment_date->format('Y-m-d') }}</span></div>
    <div class="row"><span class="bold">Member:</span> <span>{{ $payment->member->full_name ?? '' }}</span></div>
    <div class="row"><span class="bold">Member ID:</span> <span>{{ $payment->member->member_code ?? '' }}</span></div>

    <hr style="border: 0.5px dashed #ccc; margin: 10px 0;">

    <div class="row"><span class="bold">Amount Due:</span> <span>PKR {{ number_format($payment->amount_due, 2) }}</span></div>
    <div class="row"><span class="bold">Discount:</span> <span>PKR {{ number_format($payment->discount_amount, 2) }}</span></div>
    <div class="row"><span class="bold">Amount Paid:</span> <span class="bold">PKR {{ number_format($payment->amount_paid, 2) }}</span></div>
    <div class="row"><span class="bold">Balance Due:</span> <span>PKR {{ number_format($payment->remaining_balance, 2) }}</span></div>
    <div class="row"><span class="bold">Method:</span> <span style="text-transform: uppercase;">{{ $payment->payment_method }}</span></div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This is a computer generated receipt.</p>
    </div>
</body>
</html>
