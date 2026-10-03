<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Status Update</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .email-header {
            padding: 30px;
            text-align: center;
        }
        .email-header.approved {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .email-header.rejected {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .email-header.released {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .email-header h1 {
            color: white;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin: 10px 0;
        }
        .status-badge.approved {
            background-color: #10b981;
            color: white;
        }
        .status-badge.rejected {
            background-color: #ef4444;
            color: white;
        }
        .status-badge.released {
            background-color: #3b82f6;
            color: white;
        }
        .request-details {
            background-color: #f9fafb;
            border-left: 4px solid #016146;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .request-details h3 {
            margin-top: 0;
            color: #016146;
            font-size: 16px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        .detail-value {
            color: #111827;
            text-align: right;
        }
        .message-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message-box.success {
            background-color: #d1fae5;
            border-left-color: #10b981;
        }
        .message-box.info {
            background-color: #dbeafe;
            border-left-color: #3b82f6;
        }
        .message-box.error {
            background-color: #fee2e2;
            border-left-color: #ef4444;
        }
        .cta-button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #016146;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            margin: 5px 0;
        }
        .contact-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .email-body {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header {{ strtolower($status) }}">
            <h1>
                @if($status === 'Approved')
                    ✓ Request Approved
                @elseif($status === 'Rejected')
                    Request Update
                @elseif($status === 'Released')
                    🌱 Seedlings Ready
                @endif
            </h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p class="greeting">Dear {{ $clientName }},</p>

            @if($status === 'Approved')
                <p>Great news! Your seedling request has been <strong>approved</strong> and is now ready for the next step.</p>
                
                <div class="message-box success">
                    <strong>Your request has been approved!</strong> The seedlings are reserved and will be prepared for release.
                </div>
            @elseif($status === 'Rejected')
                <p>We wanted to inform you about the status of your seedling request.</p>
                
                <div class="message-box error">
                    <strong>Unfortunately, your request could not be approved at this time.</strong> Please contact us for more information or to submit a new request.
                </div>
            @elseif($status === 'Released')
                <p>Excellent news! Your seedlings are now <strong>ready for pickup</strong>!</p>
                
                <div class="message-box info">
                    <strong>Your seedlings have been released and are ready for pickup.</strong> Please visit our office during business hours to collect them.
                </div>
            @endif

            <!-- Request Details -->
            <div class="request-details">
                <h3>📋 Request Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Request ID:</span>
                    <span class="detail-value">#{{ $request->id }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Seedling Type:</span>
                    <span class="detail-value">{{ $request->seedling_type }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Quantity:</span>
                    <span class="detail-value">{{ number_format($request->quantity) }} seedlings</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Purpose:</span>
                    <span class="detail-value">{{ $request->purpose }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Price:</span>
                    <span class="detail-value">
                        @if($request->total_price == 0)
                            <strong style="color: #10b981;">FREE (LGU)</strong>
                        @else
                            ₱{{ number_format($request->total_price, 2) }}
                        @endif
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge {{ strtolower($status) }}">{{ $status }}</span>
                    </span>
                </div>
            </div>

            @if($status === 'Approved')
                <p>Your seedlings are now reserved and being prepared. You will receive another notification once they are ready for pickup.</p>
            @elseif($status === 'Rejected')
                <p>If you have any questions or concerns about this decision, please don't hesitate to contact us. We're here to help!</p>
            @elseif($status === 'Released')
                <p><strong>Next Steps:</strong></p>
                <ul>
                    <li>Visit our office during business hours</li>
                    <li>Bring a valid ID for verification</li>
                    <li>Bring this email or your request ID (#{{ $request->id }})</li>
                    @if($request->total_price > 0)
                        <li>Prepare payment: ₱{{ number_format($request->total_price, 2) }}</li>
                    @endif
                </ul>
            @endif

            @if($status === 'Released')
                <center>
                    <a href="{{ config('app.url') }}" class="cta-button">View Request Details</a>
                </center>
            @endif
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>SeedMIS - NWSSU</strong></p>
            <p>Northwest Samar State University</p>
            
            <div class="contact-info">
                <p>For inquiries, please contact us:</p>
                <p>Email: noreply@transactlogs.pro</p>
                <p>Office Hours: Monday - Friday, 8:00 AM - 5:00 PM</p>
            </div>
            
            <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </div>
</body>
</html>
