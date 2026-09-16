<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; padding: 24px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="color: #2c3e50; margin-top: 0;">{{ $companyName }}</h2>
        <p>Dear {{ $greetingName }},</p>
        <p style="white-space: pre-line;">{{ $bodyText }}</p>
        <p style="margin-top: 32px; color: #888; font-size: 12px;">
            This is an automated message from {{ $companyName }}. Please contact us directly if you have any questions.
        </p>
    </div>
</body>
</html>
