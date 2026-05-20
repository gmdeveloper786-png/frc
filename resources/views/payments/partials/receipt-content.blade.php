<div class="receipt-hdr">
    <div class="receipt-logo-text">{{ $frc['receipt_logo_text'] ?? 'FRC' }}</div>
    <h2 class="receipt-org-name">{{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}</h2>
    <p class="receipt-doc-title">Official Payment Receipt</p>
    @if($frc['contact_address'] || $frc['contact_phone'] || $frc['contact_email'])
        <p class="receipt-contact">
            @if($frc['contact_address']){{ $frc['contact_address'] }}<br>@endif
            @if($frc['contact_phone']){{ $frc['contact_phone'] }}@endif
            @if($frc['contact_phone'] && $frc['contact_email']) · @endif
            @if($frc['contact_email']){{ $frc['contact_email'] }}@endif
        </p>
    @endif
    @if(!empty($receipt['from_uploaded_slip']))
        <p class="receipt-slip-note">Digital receipt — verified against uploaded fee slip</p>
    @endif
</div>

<p class="receipt-rn">{{ $receipt['receipt_number'] }}</p>

<table class="receipt-table">
    <tbody>
        <tr><td>Receipt Number</td><td>{{ $receipt['receipt_number'] }}</td></tr>
        <tr><td>Payment Date</td><td>{{ $receipt['payment_date'] }}</td></tr>
        @if(!empty($receipt['verified_at']))
            <tr><td>Verified On</td><td>{{ $receipt['verified_at'] }}</td></tr>
        @endif
        @if(!empty($receipt['verified_by']))
            <tr><td>Verified By</td><td>{{ $receipt['verified_by'] }}</td></tr>
        @endif
        <tr><td>Child Name</td><td>{{ $receipt['child_name'] }}</td></tr>
        @if(!empty($receipt['child_gr_number']))
            <tr><td>GR Number</td><td>{{ $receipt['child_gr_number'] }}</td></tr>
        @endif
        <tr><td>Branch</td><td>{{ $receipt['branch'] }}</td></tr>
        <tr><td>Therapist</td><td>{{ $receipt['therapist'] }}</td></tr>
        <tr><td>Payment Method</td><td>{{ $receipt['payment_method'] }}</td></tr>
        @if(!empty($receipt['transaction_ref']))
            <tr><td>Transaction Reference</td><td>{{ $receipt['transaction_ref'] }}</td></tr>
        @endif
    </tbody>
</table>

<p class="receipt-amt">PKR {{ frc_money($receipt['amount']) }}</p>

<table class="receipt-table">
    <tbody>
        <tr><td>Total Enrollment Fee</td><td>PKR {{ frc_money($receipt['total_fee']) }}</td></tr>
        <tr><td>Total Paid</td><td>PKR {{ frc_money($receipt['paid_amount']) }}</td></tr>
        <tr><td>Remaining Balance</td><td>PKR {{ frc_money($receipt['remaining_amount']) }}</td></tr>
    </tbody>
</table>

@if(!empty($receipt['notes']))
    <p class="receipt-notes"><strong>Notes:</strong> {{ $receipt['notes'] }}</p>
@endif

<div class="receipt-foot">
    <p>Received by: {{ $receipt['received_by'] ?? 'Staff' }} | {{ $receipt['payment_date'] }}</p>
    <p>Computer-generated receipt. For queries, contact reception.</p>
</div>
