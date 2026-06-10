<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>High discount approval required</title>
</head>
<body style="margin:0;padding:24px;font-family:Georgia,'Times New Roman',serif;font-size:16px;line-height:1.6;color:#1a2a3a;background:#f6f8fa;">
    <p style="margin:0 0 16px;">Dear Team,</p>

    <p style="margin:0 0 16px;">
        A new enrollment at <strong>{{ $centreName }}</strong> requires <strong>high discount approval</strong>.
    </p>

    <table cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;border-collapse:collapse;margin:0 0 20px;background:#fff;border:1px solid #d8e3ec;border-radius:8px;">
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;width:42%;">Enrollment ID</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;font-weight:600;color:#11517c;">#{{ $enrollment->id }}</td>
        </tr>
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Child name</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;font-weight:600;color:#11517c;">{{ $child?->full_name ?? '—' }}</td>
        </tr>
        @if($child?->gr_number)
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">GR number</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">{{ $child->gr_number }}</td>
        </tr>
        @endif
        @if($child?->email)
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Child email</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">{{ $child->email }}</td>
        </tr>
        @endif
        @if($child?->phone_number)
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Child phone</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">{{ $child->phone_number }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Branch</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">{{ $enrollment->branch?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Service</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">{{ $enrollment->service?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Therapist</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">{{ $enrollment->therapist?->full_name ?? '—' }}</td>
        </tr>
        @if($enrollment->zakatEligibilityLabel())
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Zakat eligibility</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">{{ $enrollment->zakatEligibilityLabel() }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Discount</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">
                {{ frc_percent($enrollment->discount_percentage) }}% ({{ frc_pkr($enrollment->discount_amount) }})
            </td>
        </tr>
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Fee before discount</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;">{{ frc_pkr($enrollment->subtotal) }}</td>
        </tr>
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;">Final payable</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;font-weight:700;color:#16acac;">{{ frc_pkr($enrollment->final_total) }}</td>
        </tr>
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#6c7a8d;vertical-align:top;">Discount reason</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e8eef3;color:#11517c;white-space:pre-wrap;">{{ filled($enrollment->discount_reason) ? $enrollment->discount_reason : '—' }}</td>
        </tr>
        <tr>
            <td style="padding:10px 14px;color:#6c7a8d;">Created by</td>
            <td style="padding:10px 14px;color:#11517c;">{{ $enrollment->createdBy?->full_name ?? '—' }}</td>
        </tr>
    </table>

    @if(filled($enrollment->discount_file))
        <p style="margin:0 0 16px;">The supporting discount document is attached to this email.</p>
    @else
        <p style="margin:0 0 16px;">No supporting discount document was uploaded.</p>
    @endif

    <p style="margin:0 0 16px;">
        <a href="{{ $queueUrl }}" style="color:#0d9488;font-weight:600;">Open high discount approval queue</a>
    </p>

    <p style="margin:24px 0 0;">
        Thank you,<br>
        <strong>{{ $centreName }}</strong>
    </p>
</body>
</html>
