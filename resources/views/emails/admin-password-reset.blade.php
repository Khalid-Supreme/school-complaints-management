<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Temporary Password</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f8fa; color: #1f2937; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 12px;">Your temporary password</h2>
        <p style="line-height: 1.6;">Hi {{ $user->full_name }},</p>
        <p style="line-height: 1.6;">
            An administrator has set a temporary password for your account ({{ $user->institution_id }}).
            Sign in with the password below and you will be required to choose a new one.
        </p>
        <p id="temporary-password" style="margin: 28px 0; padding: 16px; background: #f3f4f6; border-radius: 10px; font-family: monospace; font-size: 18px; letter-spacing: 1px;">
            {{ $temporary_password }}
        </p>
        @if ($frontend_url)
            <p style="margin: 28px 0;">
                <a href="{{ $frontend_url }}" style="display:inline-block;background:#2f6b57;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;">Go to login</a>
            </p>
        @endif
        <p style="line-height: 1.6; font-size: 14px; color: #6b7280;">
            If you did not request this, please contact your school administration immediately.
        </p>
    </div>
</body>
</html>
