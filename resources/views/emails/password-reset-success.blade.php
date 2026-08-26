<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Reset Successful</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f8fa; color: #1f2937; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 12px;">Password reset successful</h2>
        <p style="line-height: 1.6;">Hi {{ $user->full_name }},</p>
        <p style="line-height: 1.6;">
            Your password for <strong>{{ $user->email }}</strong> was successfully reset on {{ now()->format('d M Y, h:i A') }}.
        </p>
        <p style="line-height: 1.6;">
            You can now sign in with your new password. If you did not request this change, please contact your school administration immediately and reset your password again to secure your account.
        </p>
        <p style="margin: 24px 0;">
            <a href="{{ config('app.frontend_url') }}/login" style="display: inline-block; background: #6a9c5e; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600;">Sign in to your account</a>
        </p>
        <p style="line-height: 1.6; font-size: 14px; color: #6b7280;">
            For your security, this is an automated confirmation. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
