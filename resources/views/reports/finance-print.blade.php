<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finance Report — Print</title>
<link rel="stylesheet" href="{{ asset('css/frc.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body { background: #fff; padding: 16px 20px; font-family: 'Poppins', system-ui, sans-serif; }
    @media print {
        .no-print { display: none !important; }
        body { padding: 0; }
    }
    .print-actions { margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
    .mini-sum { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; font-size: 12px; color: var(--navy); }
    .mini-sum span { background: var(--bg-light); padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border-soft); }
</style>
</head>
<body>
<div class="no-print print-actions">
    <button type="button" onclick="window.print()" class="btn-teal"><i class="fa-solid fa-print"></i> Print</button>
    <button type="button" onclick="window.close()" class="btn-outline-teal">Close</button>
</div>
<h5 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:12px;">Finance Report</h5>
<div class="mini-sum">
    <span><strong>Total Expected</strong> PKR {{ number_format((float) ($summary['total_expected'] ?? 0)) }}</span>
    <span><strong>Total Paid</strong> PKR {{ number_format((float) ($summary['total_paid'] ?? 0)) }}</span>
    <span><strong>Pending</strong> PKR {{ number_format((float) ($summary['total_pending'] ?? 0)) }}</span>
    <span><strong>Cash</strong> PKR {{ number_format((float) ($summary['cash_received'] ?? 0)) }}</span>
    <span><strong>Online/Bank</strong> PKR {{ number_format((float) ($summary['online_received'] ?? 0)) }}</span>
    <span><strong>Pending Verif.</strong> PKR {{ number_format((float) ($summary['pending_verification'] ?? 0)) }}</span>
</div>
<div class="table-responsive">
    <table class="table-frc">
        <thead>
            <tr>
                <th style="width:40px;text-align:center;">#</th>
                <th>Receipt#</th>
                <th>Child</th>
                <th>Status</th>
                <th>Branch</th>
                <th>Total Fee</th>
                <th>Paid</th>
                <th>Remaining</th>
                <th>Payment</th>
                <th>Amount</th>
                <th>Verification</th>
                <th>Method</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td style="text-align:center;font-weight:600;color:var(--navy);">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $r['receipt'] }}</td>
                    <td>{{ $r['child_name'] }}</td>
                    <td>{{ $r['child_status'] }}</td>
                    <td>{{ $r['branch'] }}</td>
                    <td>{{ $r['enrollment_total'] !== '' ? 'PKR '.$r['enrollment_total'] : '—' }}</td>
                    <td style="color:var(--success);">{{ $r['enrollment_paid'] !== '' ? 'PKR '.$r['enrollment_paid'] : '—' }}</td>
                    <td style="color:var(--danger);">{{ $r['enrollment_remaining'] !== '' ? 'PKR '.$r['enrollment_remaining'] : '—' }}</td>
                    <td>{{ $r['enrollment_payment_status'] }}</td>
                    <td style="font-weight:600;color:var(--teal);">PKR {{ $r['amount'] }}</td>
                    <td>{{ $r['verification_status'] }}</td>
                    <td style="font-size:13px;">{{ $r['payment_method'] }}</td>
                    <td>{{ $r['payment_date'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(request('autoprint'))
<script>
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 400);
});
</script>
@endif
</body>
</html>
