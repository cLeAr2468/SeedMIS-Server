<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #016146 0%, #014d38 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #333333;
            font-size: 20px;
            margin-top: 0;
        }
        .content p {
            color: #666666;
            line-height: 1.6;
            font-size: 15px;
        }
        .otp-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px dashed #016146;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #016146;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .otp-label {
            color: #666666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
            color: #999999;
            font-size: 13px;
        }
        .footer strong {
            color: #016146;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌱 SeedMIS</h1>
            <p>San Jorge Experiment Station - NWSSU</p>
        </div>
        
        <div class="content">
            <h2>Hello, {{ $userName }}!</h2>
            
            <p>We received a request to reset your password for your SeedMIS account.</p>
            
            <p>Please use the following One-Time Password (OTP) to complete your password reset:</p>
            
            <div class="otp-box">
                <div class="otp-label">Your OTP Code</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>
            
            <div class="warning">
                <p><strong>⚠️ Important:</strong> This OTP will expire in 10 minutes. If you didn't request this password reset, please ignore this email or contact your administrator.</p>
            </div>
            
            <p>For security reasons, never share this code with anyone.</p>
        </div>
        
        <div class="footer">
            <p><strong>Seedling Management Information System</strong></p>
            <p>Northwestern Visayan State University</p>
            <p style="margin-top: 15px;">This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
