<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; padding: 30px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        .table th { background-color: #f5f5f5; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div class="title">{{ $gymProfile->name ?? 'GMS GYM' }}</div>
        <div>{{ $title }}</div>
        <div style="font-size: 12px; color: #666;">Period: {{ $startDate }} to {{ $endDate }}</div>
    </div>

    <table class="table">
        <thead>
            <tr>
                @foreach($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Revenue</td>
                <td style="color: green; font-weight: bold;">PKR {{ number_format($data['total_revenue'], 2) }}</td>
                <td>-</td>
            </tr>
            <tr>
                <td>Total Expenses</td>
                <td style="color: red; font-weight: bold;">PKR {{ number_format($data['total_expenses'], 2) }}</td>
                <td>-</td>
            </tr>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td>NET CASHFLOW</td>
                <td style="font-size: 16px;">PKR {{ number_format($data['net_cashflow'], 2) }}</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
