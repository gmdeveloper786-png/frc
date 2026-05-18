<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Finance Report</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
    h1 { font-size: 14px; margin: 0 0 8px 0; color: #0f766e; }
    .sum { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
    .sum td { padding: 3px 6px; border: 1px solid #cbd5e1; }
    .sum td:first-child { font-weight: bold; background: #f1f5f9; width: 28%; }
    table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.data th, table.data td { border: 1px solid #cbd5e1; padding: 3px 4px; text-align: left; vertical-align: top; word-wrap: break-word; }
    table.data th { background: #f1f5f9; font-size: 8px; }
    .num { text-align: right; white-space: nowrap; }
    .seq { text-align: center; white-space: nowrap;}
</style>
</head>
<body>
<h1>Finance Report — {{ now()->format('d M Y H:i') }} ({{ count($rows) }} records)</h1>
<table class="sum">
    <tr><td>Total Expected</td><td class="num">PKR {{ number_format((float) ($summary['total_expected'] ?? 0), 2) }}</td></tr>
    <tr><td>Total Paid</td><td class="num">PKR {{ number_format((float) ($summary['total_paid'] ?? 0), 2) }}</td></tr>
    <tr><td>Pending / Overdue</td><td class="num">PKR {{ number_format((float) ($summary['total_pending'] ?? 0), 2) }}</td></tr>
    <tr><td>Cash Received</td><td class="num">PKR {{ number_format((float) ($summary['cash_received'] ?? 0), 2) }}</td></tr>
    <tr><td>Online/Bank</td><td class="num">PKR {{ number_format((float) ($summary['online_received'] ?? 0), 2) }}</td></tr>
    <tr><td>Pending Verification</td><td class="num">PKR {{ number_format((float) ($summary['pending_verification'] ?? 0), 2) }}</td></tr>
</table>
<table class="data">
    <thead>
        <tr>
            <th class="seq">#</th>
            <th>Receipt#</th>
            <th>Child</th>
            <th>Status</th>
            <th>Branch</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Rem.</th>
            <th>Pay st.</th>
            <th>Verif.</th>
            <th>Amt</th>
            <th>Method</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $r)
            <tr>
                <td class="seq">{{ $loop->iteration }}</td>
                <td>{{ $r['receipt'] }}</td>
                <td>{{ $r['child_name'] }}</td>
                <td>{{ $r['child_status'] }}</td>
                <td>{{ $r['branch'] }}</td>
                <td class="num">{{ $r['enrollment_total'] }}</td>
                <td class="num">{{ $r['enrollment_paid'] }}</td>
                <td class="num">{{ $r['enrollment_remaining'] }}</td>
                <td>{{ $r['enrollment_payment_status'] }}</td>
                <td>{{ $r['verification_status'] }}</td>
                <td class="num">{{ $r['amount'] }}</td>
                <td>{{ $r['payment_method'] }}</td>
                <td>{{ $r['payment_date'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
