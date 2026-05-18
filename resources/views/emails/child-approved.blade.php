<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account approved</title>
</head>
<body style="margin:0;padding:24px;font-family:Georgia,'Times New Roman',serif;font-size:16px;line-height:1.6;color:#1a2a3a;background:#f6f8fa;">
    <p style="margin:0 0 16px;">Dear {{ $childName }},</p>

    <p style="margin:0 0 16px;">
        Your registration at <strong>{{ $centreName }}</strong> has been approved.
    </p>

    <p style="margin:0 0 16px;">
        You can now log in to your dashboard using your registered email address and password.
    </p>

    <p style="margin:0 0 16px;">
        <a href="{{ $loginUrl }}" style="color:#0d9488;font-weight:600;">Login here</a>
    </p>

    <p style="margin:0 0 16px;">
        From your dashboard, you can view your assessments, enrollment details, schedule, fee summary, and payment history.
    </p>

    <p style="margin:24px 0 0;">
        Thank you,<br>
        <strong>{{ $centreName }}</strong>
    </p>
</body>
</html>
