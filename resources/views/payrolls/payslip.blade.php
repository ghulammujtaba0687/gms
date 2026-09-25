<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $payroll->payroll_code }}</title>
    <style>
        body { font-family: sans-serif; padding: 30px; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; }
        .subtitle { font-size: 14px; color: #555; }
        .grid { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .bold { font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        .table th { background-color: #f5f5f5; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div class="title">{{ $gymProfile->name ?? 'GMS GYM' }}</div>
        <div class="subtitle">{{ $payroll->branch->name ?? '' }} — STAFF SALARY PAYSLIP</div>
    </div>

    <div class="grid"><span class="bold">Payslip #:</span> <span>{{ $payroll->payroll_code }}</span></div>
    <div class="grid"><span class="bold">Salary Month:</span> <span>{{ $payroll->salary_month_year }}</span></div>
    <div class="grid"><span class="bold">Staff Name:</span> <span>{{ $payroll->staffProfile->user->name ?? '' }}</span></div>
    <div class="grid"><span class="bold">Designation:</span> <span>{{ $payroll->staffProfile->designation ?? '' }} ({{ $payroll->staffProfile->staff_code ?? '' }})</span></div>
    <div class="grid"><span class="bold">Disbursement Date:</span> <span>{{ $payroll->payment_date->format('Y-m-d') }}</span></div>

    <table class="table">
        <thead>
            <tr>
                <th>Earnings / Deductions Description</th>
                <th style="text-align: right;">Amount (PKR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Base Monthly Salary</td>
                <td style="text-align: right;">{{ number_format($payroll->base_salary_snapshot, 2) }}</td>
            </tr>
            <tr>
                <td>Bonus / Performance Incentives</td>
                <td style="text-align: right; color: green;">+ {{ number_format($payroll->bonus_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Deductions</td>
                <td style="text-align: right; color: red;">- {{ number_format($payroll->deduction_amount, 2) }}</td>
            </tr>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td>NET DISBURSED SALARY</td>
                <td style="text-align: right;">PKR {{ number_format($payroll->net_salary, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This is an official computer-generated salary payslip.</p>
    </div>
</body>
</html>
