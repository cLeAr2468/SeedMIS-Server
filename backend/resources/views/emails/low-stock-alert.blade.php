<!DOCTYPE html>
<html lang="en">
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
            background-color: #f59e0b;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #fff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .alert-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .details {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .details-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #6b7280;
        }
        .value {
            color: #111827;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 12px;
        }
        .warning-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="warning-icon">⚠️</div>
        <h1 style="margin: 0;">Low Stock Alert</h1>
    </div>
    
    <div class="content">
        <div class="alert-box">
            <h2 style="margin-top: 0; color: #92400e;">Action Required: Seedling Inventory Low</h2>
            <p style="margin-bottom: 0;">
                The inventory for <strong>{{ $inventory->seedling_type }}</strong> has reached or fallen below the minimum stock level.
            </p>
        </div>

        <div class="details">
            <h3 style="margin-top: 0; color: #374151;">Inventory Details</h3>
            
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

        <div style="background-color: #dbeafe; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h3 style="margin-top: 0; color: #1e40af;">Recommended Actions:</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Review current production batches for {{ $inventory->seedling_type }}</li>
                <li>Consider starting a new production batch if needed</li>
                <li>Check if any batches are close to "Ready" stage</li>
                <li>Update the minimum stock level if necessary</li>
            </ul>
        </div>

        <p style="margin-top: 30px; color: #6b7280;">
            This is an automated notification from the SeedMIS System. Please take appropriate action to maintain adequate inventory levels.
        </p>
    </div>

    <div class="footer">
        <p style="margin: 5px 0;">
            <strong>SeedMIS - Seedling Management Information System</strong>
        </p>
        <p style="margin: 5px 0;">
            © {{ date('Y') }} All rights reserved.
        </p>
        <p style="margin: 5px 0; font-size: 11px; color: #9ca3af;">
            This email was sent to you because you are registered as an administrator in the system.
        </p>
    </div>
</body>
</html>
