<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activated</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #016146;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .welcome-box {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .credentials-box {
            background-color: #fff;
            border: 2px solid #016146;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .credential-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .credential-row:last-child {
            border-bottom: none;
        }
        .credential-label {
            font-weight: bold;
            color: #6b7280;
        }
        .credential-value {
            color: #111827;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .password-value {
            color: #dc2626;
            font-size: 18px;
            background-color: #fef2f2;
            padding: 8px 12px;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            background-color: #016146;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #014d38;
        }
        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .steps {
            background-color: #eff6ff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .step {
            margin: 10px 0;
            padding-left: 25px;
            position: relative;
        }
        .step:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #016146;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Welcome to SeedMIS!</h1>
    </div>
    
    <div class="content">
        <div class="welcome-box">
            <h2 style="margin-top: 0; color: #059669;">Account Successfully Activated</h2>
            <p style="margin-bottom: 0;">
                Hello <strong>{{ $clientName }}</strong>, your customer account has been upgraded to a full client account. You can now login to the SeedMIS portal!
            </p>
        </div>

        <h3>Your Login Credentials:</h3>
        
        <div class="credentials-box">
            <div class="credential-row">
                <span class="credential-label">Client ID:</span>
                <span class="credential-value">{{ $clientId }}</span>
            </div>
            
            <div class="credential-row">
                <span class="credential-label">Email:</span>
                <span class="credential-value">{{ $email }}</span>
            </div>
            
            <div class="credential-row">
                <span class="credential-label">Temporary Password:</span>
                <span class="credential-value password-value">{{ $tempPassword }}</span>
            </div>
        </div>

        <div class="warning-box">
            <p style="margin: 0; color: #92400e;">
                <strong>⚠️ Important Security Notice:</strong> This is a temporary password. For your security, you must change this password after your first login.
            </p>
        </div>

        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="button">Login to SeedMIS Portal</a>
        </div>

        <div class="steps">
            <h4 style="margin-top: 0; color: #1e40af;">How to Login:</h4>
            <div class="step">Visit the SeedMIS login page</div>
            <div class="step">Enter your email: <strong>{{ $email }}</strong></div>
            <div class="step">Enter the temporary password shown above</div>
            <div class="step">You will be prompted to change your password</div>
            <div class="step">Create a strong new password</div>
            <div class="step">Start managing your seedling requests!</div>
        </div>

        <h4>What You Can Do Now:</h4>
        <ul>
            <li>Submit seedling requests online</li>
            <li>Track your request status in real-time</li>
            <li>View your transaction history</li>
            <li>Update your profile information</li>
            <li>Manage your account settings</li>
        </ul>

        <div style="margin-top: 30px; padding: 15px; background-color: #e0f2fe; border-radius: 4px;">
            <p style="margin: 0; color: #075985;">
                <strong>📞 Need Help?</strong> If you have any questions or need assistance, please contact the San Jorge Experiment Station administration.
            </p>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated notification from SeedMIS - San Jorge Experiment Station</p>
        <p>Seedling Management Information System</p>
        <p style="color: #dc2626; font-size: 11px; margin-top: 10px;">
            ⚠️ For security reasons, do not share this email with anyone.
        </p>
    </div>
</body>
</html>
