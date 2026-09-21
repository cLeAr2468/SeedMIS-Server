<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\InventoryController;

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
    // Auth routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    // Client routes
    Route::apiResource('clients', ClientController::class);
    
    // Staff routes
    Route::apiResource('staff', StaffController::class);
    
    // Production routes
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
