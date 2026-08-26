<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complaint Assigned to You</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f8fa; color: #1f2937; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 8px;">Complaint Assigned to You — {{ $complaint->reference_no }}</h2>
        <p style="margin: 0 0 16px; color: #6b7280; font-size: 14px;">You have been assigned a complaint that requires your attention.</p>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px; line-height: 1.6;">
            <tr><td style="padding: 6px 0; color: #6b7280; width: 160px;">Reference</td><td style="padding: 6px 0; font-weight: 600;">{{ $complaint->reference_no }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Subject</td><td style="padding: 6px 0;">{{ $title }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Category</td><td style="padding: 6px 0;">{{ $category }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Assigned At</td><td style="padding: 6px 0;">{{ $assignedAt }}</td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Status</td><td style="padding: 6px 0;"><span style="display:inline-block; background:#f3f4f6; border-radius:999px; padding:2px 10px; font-size:12px; text-transform: capitalize;">{{ str_replace('_',' ', $status) }}</span></td></tr>
            <tr><td style="padding: 6px 0; color: #6b7280;">Assigned By</td><td style="padding: 6px 0;">{{ $assignedBy }}</td></tr>
        </table>

        @if ($frontendUrl)
            <p style="margin: 28px 0;">
                <a href="{{ rtrim($frontendUrl,'/') }}/officer/complaints/{{ $complaint->id }}" style="display:inline-block;background:#2f6b57;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;">View Complaint</a>
            </p>
        @endif

        <p style="line-height: 1.6; font-size: 13px; color: #9ca3af; margin-top: 24px;">
            The complaint description is not included in this email. Open the complaint to see full details.
        </p>
    </div>
</body>
</html>
