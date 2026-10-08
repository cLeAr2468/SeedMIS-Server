<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert</title>
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
            background-color: #dc2626;
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
        .alert-box {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-label {
            font-weight: bold;
            color: #6b7280;
        }
        .info-value {
            color: #111827;
        }
        .warning-icon {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
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
        <h1>⚠️ Low Stock Alert</h1>
    </div>
    
    <div class="content">
        <div class="warning-icon">
            🔔
        </div>
        
        <div class="alert-box">
            <h2 style="margin-top: 0; color: #dc2626;">Stock Level Warning</h2>
            <p>
                The inventory for <strong>{{ $seedlingType }}</strong> has reached or fallen below the low stock threshold of <strong>{{ $threshold }} units</strong>.
            </p>
        </div>

        <h3>Inventory Details:</h3>
        
        <div class="info-row">
            <span class="info-label">Seedling Type:</span>
            <span class="info-value">{{ $seedlingType }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Classification:</span>
            <span class="info-value">{{ $classification }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Current Quantity:</span>
            <span class="info-value" style="color: #dc2626; font-weight: bold;">{{ $currentQuantity }} units</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Threshold Level:</span>
            <span class="info-value">{{ $threshold }} units</span>
        </div>
        
        <div class="info-row" style="border-bottom: none;">
            <span class="info-label">Location:</span>
            <span class="info-value">{{ $location }}</span>
        </div>

        <div style="margin-top: 30px; padding: 15px; background-color: #dbeafe; border-radius: 4px;">
            <p style="margin: 0; color: #1e40af;">
                <strong>📋 Action Required:</strong> Please review the inventory levels and consider restocking this seedling type to maintain adequate supply.
            </p>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated notification from SeedMIS - San Jorge Experiment Station</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>
