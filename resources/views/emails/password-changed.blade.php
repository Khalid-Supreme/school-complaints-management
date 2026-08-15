<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Changed</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f8fa; color: #1f2937; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 12px;">Your password has been changed</h2>
        <p style="line-height: 1.6;">Hi {{ $user->full_name }},</p>
        <p style="line-height: 1.6;">
            The password for your account ({{ $user->email }}) was recently changed. To complete the
            password-change process and access your account, enter the verification code below:
        </p>
        <p id="verification-code" style="margin: 28px 0; padding: 16px; background: #f3f4f6; border-radius: 10px; font-family: monospace; font-size: 24px; letter-spacing: 6px; text-align: center;">
            {{ $code }}
        </p>
        <p style="line-height: 1.6; font-size: 14px; color: #6b7280;">
            This code expires in {{ $expires_in_minutes }} minutes and can only be used once.
        </p>
        <p style="line-height: 1.6; font-size: 14px; color: #6b7280;">
            If you did <strong>not</strong> make this change, please contact your school administration
            immediately so they can secure your account.
        </p>
    </div>
</body>
</html>
