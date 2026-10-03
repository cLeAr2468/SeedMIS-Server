<?php
/**
 * Quick test script for email notifications
 * Run: php test-email.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Request;
use App\Mail\RequestStatusChanged;
use Illuminate\Support\Facades\Mail;

echo "=== Email Notification Test ===\n\n";

// Get the first request from database
$request = Request::with('client')->first();

if (!$request) {
    echo "❌ No requests found in database. Please create a request first.\n";
    exit(1);
}

if (!$request->client) {
    echo "❌ Request #{$request->id} has no associated client.\n";
    exit(1);
}

if (!$request->client->email) {
    echo "❌ Client has no email address.\n";
    exit(1);
}

echo "📋 Request Details:\n";
echo "   - ID: #{$request->id}\n";
echo "   - Client: {$request->requester_name}\n";
echo "   - Email: {$request->client->email}\n";
echo "   - Seedling: {$request->seedling_type}\n";
echo "   - Quantity: {$request->quantity}\n";
echo "   - Status: {$request->status}\n\n";

// Test all three email types
$statuses = ['Approved', 'Rejected', 'Released'];

foreach ($statuses as $status) {
    echo "📧 Sending {$status} email to {$request->client->email}... ";
    
    try {
        Mail::to($request->client->email)->send(
            new RequestStatusChanged($request, $status)
        );
        echo "✅ Sent!\n";
    } catch (\Exception $e) {
        echo "❌ Failed: {$e->getMessage()}\n";
    }
}

echo "\n✨ Test completed!\n";
echo "💡 Check your email inbox (and spam folder)\n";
echo "📝 Check logs: storage/logs/laravel.log\n";
echo "🔍 Monitor queue: php artisan queue:work\n";
