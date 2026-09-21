<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Services\ProductionHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $productions = Production::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $productions,
                'message' => 'Production batches retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve production batches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get production metrics/statistics
     */
    public function metrics()
    {
        try {
            // Total seedlings sown
            $seedlingsSown = Production::sum('quantity_sown');
            
            // Count of unique seedling types
            $seedlingTypes = Production::distinct('seedling_type')->count('seedling_type');
            
            // Average survivability
            $productions = Production::all();
            $totalSurvivability = 0;
            $count = 0;
            
            foreach ($productions as $production) {
                if ($production->quantity_sown > 0) {
                    $survivability = ($production->current_quantity / $production->quantity_sown) * 100;
                    $totalSurvivability += $survivability;
                    $count++;
                }
            }
            
            $averageSurvivability = $count > 0 ? round($totalSurvivability / $count, 1) : 0;
            
            // Ready for distribution (stage = Ready)
            $readyForDistribution = Production::where('stage', 'Ready')->sum('current_quantity');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'seedlings_sown' => $seedlingsSown,
                    'seedling_types' => $seedlingTypes,
                    'average_survivability' => $averageSurvivability,
                    'ready_for_distribution' => $readyForDistribution,
                ],
                'message' => 'Production metrics retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve production metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all production history across all batches
     */
    public function getAllHistory()
    {
        try {
            $history = ProductionHistoryService::getAllHistory();
            
            return response()->json([
                'success' => true,
                'data' => $history,
                'message' => 'Production history retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve production history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get production history for a specific batch
     */
    public function getProductionHistory(string $id)
    {
        try {
            $production = Production::findOrFail($id);
            $history = ProductionHistoryService::getProductionHistory($id);
            
            return response()->json([
                'success' => true,
                'data' => $history,
                'message' => 'Production history retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve production history',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update production stage
     */
    public function updateStage(Request $request, string $id)
    {
        try {
            $production = Production::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'stage' => 'required|in:Germination,Seedling,Hardening,Ready',
                'current_quantity' => 'required|integer|min:0',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Store old values
            $oldStage = $production->stage;
            $oldQuantity = $production->current_quantity;

            $production->update([
                'stage' => $request->stage,
                'current_quantity' => $request->current_quantity,
            ]);

            // Log stage update history
            ProductionHistoryService::log($production, 'stage_update', [
                'previous_stage' => $oldStage,
                'new_stage' => $request->stage,
                'previous_quantity' => $oldQuantity,
                'new_quantity' => $request->current_quantity,
            ], $request->notes);

            return response()->json([
                'success' => true,
                'data' => $production->fresh(),
                'message' => 'Production stage updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update production stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer production to inventory
     */
    public function transferToInventory(Request $request, string $id)
    {
        try {
            $production = Production::findOrFail($id);

            if ($production->stage !== 'Ready') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only production batches with "Ready" stage can be transferred'
                ], 422);
            }

            // Check if inventory already exists for this seedling type
            $inventory = \App\Models\Inventory::where('seedling_type', $production->seedling_type)
                ->where('classification', $production->classification)
                ->first();

            if ($inventory) {
                // Update existing inventory - add to total quantity only
                $inventory->total_quantity += $production->current_quantity;
                $inventory->save();
                
                // Refresh to get updated data
                $inventory = $inventory->fresh();
            } else {
                // Create new inventory - price is required for new seedlings
                $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                    'price_per_unit' => 'required|numeric|min:0',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Price is required for new seedling types',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $inventory = \App\Models\Inventory::create([
                    'seedling_type' => $production->seedling_type,
                    'classification' => $production->classification,
                    'total_quantity' => $production->current_quantity,
                    'reserved_quantity' => 0,
                    'price_per_unit' => $request->price_per_unit,
                    'unit' => 'pieces',
                    'min_stock_level' => 0,
                    'location' => $production->location,
                    'image_url' => $production->image_url,
                ]);
            }

            // Create batch record for traceability
            $batchNumber = $this->generateBatchNumber($production->seedling_type);
            
            \App\Models\InventoryBatch::create([
                'inventory_id' => $inventory->id,
                'batch_number' => $batchNumber,
                'production_batch_id' => $production->batch_id,
                'quantity' => $production->current_quantity,
                'date_received' => now(),
                'date_sown' => $production->date_sown,
                'expected_ready' => $production->expected_ready,
                'location' => $production->location,
                'quality_status' => 'Good',
                'notes' => 'Transferred from production',
            ]);

            // Log transfer history before deleting
            ProductionHistoryService::log($production, 'transferred', [
                'new_quantity' => $production->current_quantity,
            ], "Transferred to inventory (Batch: {$batchNumber})");

            // Delete production after successful transfer
            $production->delete();
            
            // Check if inventory is now low stock and send email notification
            $this->checkLowStockAndNotify($inventory);
            
            return response()->json([
                'success' => true,
                'data' => $inventory,
                'batch_number' => $batchNumber,
                'message' => 'Production transferred to inventory successfully (Batch: ' . $batchNumber . ')'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer to inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique batch number
     */
    private function generateBatchNumber($seedlingType)
    {
        // Get first 2-3 letters of seedling type
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $seedlingType), 0, 3));
        
        // Get current date
        $date = date('Ymd');
        
        // Get count of batches today for this prefix
        $todayCount = \App\Models\InventoryBatch::where('batch_number', 'like', "BTH-{$prefix}-{$date}-%")
            ->count();
        
        $sequence = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);
        
        return "BTH-{$prefix}-{$date}-{$sequence}";
    }

    /**
     * Check if inventory is low and send email notification to all admins
     */
    private function checkLowStockAndNotify($inventory)
    {
        try {
            // Check if total_quantity is at or below min_stock_level
            if ($inventory->total_quantity <= $inventory->min_stock_level && $inventory->min_stock_level > 0) {
                // Get all admin emails
                $admins = \App\Models\Admin::all();
                
                if ($admins->count() > 0) {
                    foreach ($admins as $admin) {
                        if ($admin->email) {
                            \Illuminate\Support\Facades\Mail::to($admin->email)
                                ->send(new \App\Mail\LowStockAlert($inventory));
                        }
                    }
                    
                    \Illuminate\Support\Facades\Log::info('Low stock alert sent for: ' . $inventory->seedling_type);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            \Illuminate\Support\Facades\Log::error('Failed to send low stock email: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'batch_id' => 'required|string|unique:productions,batch_id|max:255',
                'seedling_type' => 'required|string|max:255',
                'scientific_name' => 'nullable|string|max:255',
                'classification' => 'required|in:Crafted,Seedling',
                'date_sown' => 'required|date',
                'expected_ready' => 'required|date|after:date_sown',
                'quantity_sown' => 'required|integer|min:1',
                'current_quantity' => 'required|integer|min:0',
                'survivability' => 'nullable|numeric|min:0|max:100',
                'stage' => 'required|in:Germination,Seedling,Hardening,Ready',
                'location' => 'required|string|max:255',
                'assigned_staff' => 'nullable|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->except('image');

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/seedlings'), $imageName);
                $data['image_url'] = 'uploads/seedlings/' . $imageName;
            }

            $production = Production::create($data);

            // Log creation history
            ProductionHistoryService::log($production, 'created', [
                'new_stage' => $production->stage,
                'new_quantity' => $production->current_quantity,
            ], 'Production batch created');

            return response()->json([
                'success' => true,
                'data' => $production,
                'message' => 'Production batch created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create production batch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $production = Production::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $production,
                'message' => 'Production batch retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Production batch not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $production = Production::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'batch_id' => 'sometimes|string|unique:productions,batch_id,' . $id . '|max:255',
                'seedling_type' => 'sometimes|string|max:255',
                'scientific_name' => 'nullable|string|max:255',
                'classification' => 'sometimes|in:Crafted,Seedling',
                'date_sown' => 'sometimes|date',
                'expected_ready' => 'sometimes|date',
                'quantity_sown' => 'sometimes|integer|min:1',
                'current_quantity' => 'sometimes|integer|min:0',
                'survivability' => 'nullable|numeric|min:0|max:100',
                'stage' => 'sometimes|in:Germination,Seedling,Hardening,Ready',
                'location' => 'sometimes|string|max:255',
                'assigned_staff' => 'nullable|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->except('image');

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($production->image_url && file_exists(public_path($production->image_url))) {
                    unlink(public_path($production->image_url));
                }

                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/seedlings'), $imageName);
                $data['image_url'] = 'uploads/seedlings/' . $imageName;
            }

            // Store old values for history
            $oldStage = $production->stage;
            $oldQuantity = $production->current_quantity;

            $production->update($data);

            // Log edit history
            ProductionHistoryService::log($production, 'edited', [
                'previous_stage' => $oldStage,
                'new_stage' => $production->stage,
                'previous_quantity' => $oldQuantity,
                'new_quantity' => $production->current_quantity,
            ], 'Production batch edited');

            return response()->json([
                'success' => true,
                'data' => $production->fresh(),
                'message' => 'Production batch updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update production batch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $production = Production::findOrFail($id);

            // Delete image if exists
            if ($production->image_url && file_exists(public_path($production->image_url))) {
                unlink(public_path($production->image_url));
            }

            $production->delete();

            return response()->json([
                'success' => true,
                'message' => 'Production batch deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete production batch',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
