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
        <p style="line-height: 1.6;">Hi {{ $user->name }},</p>
        <p style="line-height: 1.6;">Use the link below to verify your account and activate your access.</p>
        <p style="margin: 28px 0;">
            <a href="{{ $verificationUrl }}" style="display:inline-block;background:#2f6b57;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;">Verify Email</a>
        </p>
        <p style="line-height: 1.6; font-size: 14px; color: #6b7280;">If the button does not work, copy and paste this URL into your browser:</p>
        <p style="word-break: break-all; font-size: 13px; color: #2563eb;">{{ $verificationUrl }}</p>
    </div>
</body>
</html>