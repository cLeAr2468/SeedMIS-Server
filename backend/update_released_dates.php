<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Update all Released requests to current month (October 2026)
    $updated = DB::table('requests')
        ->where('status', 'Released')
        ->update([
            'requested_date' => '2026-10-01',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    
    echo "✅ Successfully updated {$updated} released requests to October 2026\n";
    
    // Show the updated records
    $requests = DB::table('requests')
        ->where('status', 'Released')
        ->get(['id', 'status', 'total_price', 'requested_date', 'updated_at']);
    
    echo "\nUpdated Records:\n";
    foreach ($requests as $req) {
        echo "ID: {$req->id}, Total Price: {$req->total_price}, Requested Date: {$req->requested_date}, Updated At: {$req->updated_at}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
