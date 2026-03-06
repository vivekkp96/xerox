<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .otp-box { background-color: #ffffff; border: 1px dashed #007bff; padding: 15px; text-align: center; margin: 20px 0; border-radius: 4px; }
        .otp { font-size: 28px; font-weight: bold; color: #007bff; letter-spacing: 5px; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to Our Service</h2>
        </div>
        <p>Thank you for registering!</p>
        <p>Please use the following OTP to verify your email address:</p>
        <div class="otp-box"><span class="otp">{{ $otp }}</span></div>
        <p>This OTP is valid for <strong>{{ $validity }} minutes</strong>.</p>
        <div class="footer">If you did not request this, please ignore this email.</div>
    </div>
</body>
</html>