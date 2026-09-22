<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .warning-icon {
            font-size: 48px;
        }
        .content {
            padding: 40px 30px;
        }
        .alert-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-box h2 {
            margin-top: 0;
            color: #92400e;
            font-size: 18px;
        }
        .alert-box p {
            margin-bottom: 0;
            color: #78350f;
        }
        .details {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .details h3 {
            margin-top: 0;
            color: #374151;
            font-size: 16px;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .details-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
        }
        .value {
            color: #111827;
            font-size: 14px;
        }
        .recommendations {
            background-color: #dbeafe;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #3b82f6;
        }
        .recommendations h3 {
            margin-top: 0;
            color: #1e40af;
            font-size: 16px;
        }
        .recommendations ul {
            margin: 10px 0;
            padding-left: 20px;
            color: #1e3a8a;
        }
        .recommendations li {
            margin: 8px 0;
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
            <div class="warning-icon">⚠️</div>
            <h1>Low Stock Alert</h1>
            <p>SeedMIS - San Jorge Experiment Station</p>
        </div>
        
        <div class="content">
            <div class="alert-box">
                <h2>⚠️ Action Required: Seedling Inventory Low</h2>
                <p>
                    The inventory for <strong>{{ $inventory->seedling_type }}</strong> has reached or fallen below the minimum stock level.
                </p>
            </div>

            <div class="details">
                <h3>📊 Inventory Details</h3>
                
                <div class="details-row">
                    <span class="label">Seedling Type:</span>
                    <span class="value">{{ $inventory->seedling_type }}</span>
                </div>
                
                <div class="details-row">
                    <span class="label">Classification:</span>
                    <span class="value">{{ $inventory->classification }}</span>
                </div>
                
                <div class="details-row">
                    <span class="label">Current Stock:</span>
                    <span class="value" style="color: #dc2626; font-weight: bold;">{{ number_format($inventory->total_quantity) }} pieces</span>
                </div>
                
                <div class="details-row">
                    <span class="label">Minimum Stock Level:</span>
                    <span class="value">{{ number_format($inventory->min_stock_level) }} pieces</span>
                </div>
                
                <div class="details-row">
                    <span class="label">Location:</span>
                    <span class="value">{{ $inventory->location ?? 'N/A' }}</span>
                </div>
                
                <div class="details-row">
                    <span class="label">Price per Unit:</span>
                    <span class="value">₱{{ number_format($inventory->price_per_unit, 2) }}</span>
                </div>
            </div>

            <div class="recommendations">
                <h3>💡 Recommended Actions:</h3>
                <ul>
                    <li>Review current production batches for {{ $inventory->seedling_type }}</li>
                    <li>Consider starting a new production batch if needed</li>
                    <li>Check if any batches are close to "Ready" stage</li>
                    <li>Update the minimum stock level if necessary</li>
                </ul>
            </div>

            <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
                This is an automated notification from the SeedMIS System. Please take appropriate action to maintain adequate inventory levels.
            </p>
        </div>

        <div class="footer">
            <p><strong>Seedling Management Information System</strong></p>
            <p>Northwestern Visayan State University</p>
            <p style="margin-top: 15px;">This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
