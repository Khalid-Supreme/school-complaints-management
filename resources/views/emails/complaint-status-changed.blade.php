<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complaint Status Updated</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f8fa; color: #1f2937; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 8px;">Complaint Status Updated — {{ $complaint->reference_no }}</h2>
        <p style="margin: 0 0 16px; color: #6b7280; font-size: 14px;">Your complaint status has changed from <strong style="text-transform: capitalize;">{{ str_replace('_',' ', $oldStatus) }}</strong> to <strong style="text-transform: capitalize;">{{ str_replace('_',' ', $newStatus) }}</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px; line-height: 1.6;">
            <tr><td style="padding: 6px 0; color: #6b7280; width: 160px;">Reference</td><td style="padding: 6px 0; font-weight: 600;">{{ $complaint->reference_no }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Subject</td><td style="padding: 6px 0;">{{ $title }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Previous Status</td><td style="padding: 6px 0; text-transform: capitalize;">{{ str_replace('_',' ', $oldStatus) }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">New Status</td><td style="padding: 6px 0;"><span style="display:inline-block; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:999px; padding:2px 10px; font-size:12px; text-transform: capitalize;">{{ str_replace('_',' ', $newStatus) }}</span></td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Updated At</td><td style="padding: 6px 0;">{{ $changedAt }}</td></tr>
        </table>

        @if ($frontendUrl)
            <p style="margin: 28px 0;">
                @php
                    $role = $complainant->role->slug ?? 'student';
                    $prefix = $role === 'staff' ? '/staff' : '/student';
                @endphp
                <a href="{{ rtrim($frontendUrl,'/') }}{{ $prefix }}/complaints/{{ $complaint->id }}" style="display:inline-block;background:#2f6b57;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;">View Complaint</a>
            </p>
        @endif

        <p style="line-height: 1.6; font-size: 13px; color: #9ca3af; margin-top: 24px;">
            If you have questions, reply within the application. This is an automated notification — do not reply to this email.
        </p>
    </div>
</body>
</html>
