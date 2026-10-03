<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ActivityLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('api')->group(function () {
    // Public auth routes (no authentication required)
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    // Profile routes
    Route::get('profile', [AuthController::class, 'getProfile']);
    Route::put('profile', [AuthController::class, 'updateProfile']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
});
// Protected routes (requires authentication if needed in future)
Route::middleware('api')->group(function () {
    // Client routes
    Route::get('clients/next-client-id', [ClientController::class, 'getNextClientId']);
    Route::apiResource('clients', ClientController::class);
    
    // Staff routes
    Route::get('staff/next-staff-id', [StaffController::class, 'getNextStaffId']);
    Route::apiResource('staff', StaffController::class);
    
    // Production routes
    Route::get('productions/next-batch-id', [ProductionController::class, 'getNextBatchId']);
    Route::get('productions/metrics', [ProductionController::class, 'metrics']);
    Route::get('productions/history', [ProductionController::class, 'getAllHistory']);
    Route::get('productions/{id}/history', [ProductionController::class, 'getProductionHistory']);
    Route::post('productions/{id}/update-stage', [ProductionController::class, 'updateStage']);
    Route::post('productions/{id}/transfer-to-inventory', [ProductionController::class, 'transferToInventory']);
    Route::apiResource('productions', ProductionController::class);
    
    // Inventory routes
    Route::get('inventories/metrics', [InventoryController::class, 'metrics']);
    Route::get('inventories/{id}/batches', [InventoryController::class, 'getBatches']);
    Route::apiResource('inventories', InventoryController::class);
    
    // Request routes
    Route::get('users/search', [RequestController::class, 'searchUsers']);
    Route::get('requests/available-seedlings', [RequestController::class, 'getAvailableSeedlings']);
    Route::get('requests/metrics', [RequestController::class, 'metrics']);
    Route::get('requests/monthly-sales', [RequestController::class, 'getMonthlySales']);
    Route::apiResource('requests', RequestController::class);
    
    // Target routes
    Route::get('targets/progress', [TargetController::class, 'getProgress']);
    Route::get('targets/monthly-target-vs-actual', [TargetController::class, 'getMonthlyTargetVsActual']);
    Route::apiResource('targets', TargetController::class);
    
    // Dashboard route
    Route::get('dashboard', [DashboardController::class, 'getDashboardData']);
    
    // Report routes
    Route::get('reports/distribution', [ReportController::class, 'getDistributionReport']);
    Route::get('reports/production', [ReportController::class, 'getProductionReport']);
    Route::get('reports/inventory', [ReportController::class, 'getInventoryReport']);
    Route::get('reports/summary', [ReportController::class, 'getSummaryReport']);
    
    // Activity Log routes
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/statistics', [ActivityLogController::class, 'statistics']);
});

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running'
    ]);
});

Route::get('/test-db', function () {
    try {
        $admin = \App\Models\Admin::where('email', 'admin@seedmis.com')->first();
        $passwordCheck = $admin ? \Hash::check('admin123', $admin->password) : false;
        
        return response()->json([
            'success' => true,
            'admin_exists' => $admin ? true : false,
            'admin_email' => $admin ? $admin->email : null,
            'password_matches' => $passwordCheck,
            'password_hash_length' => $admin ? strlen($admin->password) : 0,
            'db_connected' => true
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'db_connected' => false
        ]);
    }
});

Route::get('/test-email', function () {
    try {
        $otp = '123456';
        $email = request('email', 'test@example.com'); // Get from query parameter or use default
        
        \Log::info('Starting email test...');
        \Log::info('Email config - Host: ' . config('mail.host') . ', Port: ' . config('mail.port'));
        \Log::info('From: ' . config('mail.from.address'));
        
        Mail::raw("Test email from SeedMIS\n\nYour OTP: $otp\n\nThis is a test.", function($message) use ($email) {
            $message->to($email)
                    ->subject('SeedMIS - Test Email');
        });
        
        \Log::info('Email sent successfully!');
        
        return response()->json([
            'success' => true,
            'message' => 'Test email sent successfully',
            'sent_to' => $email,
            'config' => [
                'host' => config('mail.host'),
                'port' => config('mail.port'),
                'from' => config('mail.from.address'),
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Email test failed: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'config' => [
                'host' => config('mail.host'),
                'port' => config('mail.port'),
                'from' => config('mail.from.address'),
            ]
        ], 500);
    }
});

Route::get('/seed-admin', function () {
    try {
        // Delete existing admin
        \App\Models\Admin::where('email', 'admin@seedmis.com')->delete();
        
        // Create new admin with correct password
        $admin = \App\Models\Admin::create([
            'name' => 'Admin',
            'email' => 'admin@seedmis.com',
            'password' => \Hash::make('admin123'),
            'role' => 'admin',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully',
            'admin' => [
                'email' => $admin->email,
                'name' => $admin->name,
            ],
            'test_password' => \Hash::check('admin123', $admin->password)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});
