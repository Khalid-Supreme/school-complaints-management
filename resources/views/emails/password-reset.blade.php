<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Your Password</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f8fa; color: #1f2937; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 12px;">Reset your password</h2>
        <p style="line-height: 1.6;">Hi {{ $user->full_name }},</p>
        <p style="line-height: 1.6;">You requested to reset your password. Click the button below to create a new password:</p>
        <p style="margin: 28px 0;">
            <a href="{{ $resetUrl }}" style="display:inline-block;background:#2f6b57;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;">Reset Password</a>
        </p>
        <p style="line-height: 1.6; font-size: 14px; color: #6b7280;">This link will expire in {{ $count }} minutes.</p>
        <p style="line-height: 1.6; font-size: 14px; color: #6b7280;">If you didn't request this, you can safely ignore this email.</p>
        <hr style="margin: 24px 0; border: none; border-top: 1px solid #e5e7eb;">
        <p style="word-break: break-all; font-size: 13px; color: #2563eb;">{{ $resetUrl }}</p>
    </div>
</body>
</html>