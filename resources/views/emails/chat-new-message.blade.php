<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Message on Complaint</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f8fa; color: #1f2937; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 8px;">New Message on Complaint — {{ $complaint->reference_no }}</h2>
        <p style="margin: 0 0 16px; color: #6b7280; font-size: 14px;">You have a new message in the complaint conversation. Open the application to read the full message.</p>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px; line-height: 1.6;">
            <tr><td style="padding: 6px 0; color: #6b7280; width: 160px;">Reference</td><td style="padding: 6px 0; font-weight: 600;">{{ $complaint->reference_no }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Subject</td><td style="padding: 6px 0;">{{ $title }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">From</td><td style="padding: 6px 0;">{{ $senderName }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Date</td><td style="padding: 6px 0;">{{ $sentAt }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Preview</td><td style="padding: 6px 0; color: #374151; font-style: italic;">{{ $preview ?: '—' }}</td></tr>
        </table>

        @if ($viewUrl)
            <p style="margin: 28px 0;">
                <a href="{{ $viewUrl }}" style="display:inline-block;background:#2f6b57;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;">View Conversation</a>
            </p>
        @endif

        <p style="line-height: 1.6; font-size: 13px; color: #9ca3af; margin-top: 24px;">
            The full message is available only inside the application. Do not reply to this email.
        </p>
    </div>
</body>
</html>
