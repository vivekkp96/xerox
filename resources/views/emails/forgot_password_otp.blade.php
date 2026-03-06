<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset OTP</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 40px; margin-bottom: 40px; }
        .header { text-align: center; padding-bottom: 30px; border-bottom: 1px solid #f0f0f0; }
        .header h1 { color: #1a1a1a; margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 30px 0; text-align: center; color: #4a4a4a; line-height: 1.6; }
        .otp-wrapper { background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 25px 0; display: inline-block; }
        .otp-code { font-size: 36px; font-weight: 800; color: #2563eb; letter-spacing: 8px; font-family: monospace; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; padding-top: 20px; border-top: 1px solid #f0f0f0; }
        .warning { font-size: 13px; color: #6b7280; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>We received a request to reset your password. Please use the following One-Time Password (OTP) to proceed:</p>
            <div class="otp-wrapper">
                <div class="otp-code">{{ $otp }}</div>
            </div>
            <p>This OTP is valid for <strong>5 minutes</strong>.</p>
            <p class="warning">If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Xerox Project. All rights reserved.</p>
        </div>
    </div>
</body>
</html>