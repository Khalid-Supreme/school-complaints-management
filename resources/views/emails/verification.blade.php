<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f8fa; color: #1f2937; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 12px;">Verify your email address</h2>
        <p style="line-height: 1.6;">Hi {{ $user->full_name }},</p>
        <p style="line-height: 1.6;">Your account has been created successfully. Use the link below to verify your account and activate your access.</p>
        <div style="margin: 20px 0; padding: 16px; background: #f3f4f6; border-radius: 10px; border: 1px solid #e5e7eb; text-align: center;">
            <p style="margin: 0 0 6px; font-size: 13px; color: #6b7280; letter-spacing: 0.08em; text-transform: uppercase;">Your Institution ID</p>
            <p style="margin: 0; font-family: monospace; font-size: 20px; font-weight: 700; letter-spacing: 2px; color: #1f2937;">{{ $user->institution_id }}</p>
            <p style="margin: 8px 0 0; font-size: 12px; color: #6b7280;">Use this ID to sign in. Keep it safe.</p>
        </div>
        <p style="margin: 28px 0;">
            <a href="{{ $verificationUrl }}" style="display:inline-block;background:#2f6b57;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;">Verify Email</a>
        </p>
        <p style="line-height: 1.6; font-size: 14px; color: #6b7280;">If the button does not work, copy and paste this URL into your browser:</p>
        <p style="word-break: break-all; font-size: 13px; color: #2563eb;">{{ $verificationUrl }}</p>
        <p style="margin-top: 24px; font-size: 12px; color: #9ca3af;">This link expires in 60 minutes. If you did not create this account, please ignore this email.</p>
    </div>
</body>
</html>