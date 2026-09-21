<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function index()
    {
        try {
            $inventories = Inventory::orderBy('seedling_type', 'asc')->get();
            
            // Add batch count to each inventory
            foreach ($inventories as $inventory) {
                $inventory->batch_count = $inventory->batches()->count();
            }
            
            return response()->json([
                'success' => true,
                'data' => $inventories,
                'message' => 'Inventories retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function metrics()
    {
        try {
            // Total stock = sum of all total_quantity
            $totalStock = Inventory::sum('total_quantity');
            $totalValue = Inventory::selectRaw('SUM(total_quantity * price_per_unit) as total')->first()->total ?? 0;
            $totalTypes = Inventory::count();
            $lowStock = Inventory::whereRaw('total_quantity <= min_stock_level')->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_stock' => $totalStock,
                    'total_value' => round($totalValue, 2),
                    'total_types' => $totalTypes,
                    'low_stock' => $lowStock,
                ],
                'message' => 'Inventory metrics retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'seedling_type' => 'required|string|unique:inventories,seedling_type|max:255',
                'classification' => 'required|in:Crafted,Seedling',
                'total_quantity' => 'required|integer|min:0',
                'price_per_unit' => 'required|numeric|min:0',
                'unit' => 'nullable|string|max:50',
                'min_stock_level' => 'nullable|integer|min:0',
                'location' => 'nullable|string|max:255',
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
            $data['reserved_quantity'] = 0; // Default reserved to 0

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/inventory'), $imageName);
                $data['image_url'] = 'uploads/inventory/' . $imageName;
            }

            $inventory = Inventory::create($data);

            return response()->json([
                'success' => true,
                'data' => $inventory,
                'message' => 'Inventory created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $inventory,
                'message' => 'Inventory retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $inventory = Inventory::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'seedling_type' => 'sometimes|string|unique:inventories,seedling_type,' . $id . '|max:255',
                'classification' => 'sometimes|in:Crafted,Seedling',
                'total_quantity' => 'sometimes|integer|min:0',
                'price_per_unit' => 'sometimes|numeric|min:0',
                'unit' => 'nullable|string|max:50',
                'min_stock_level' => 'nullable|integer|min:0',
                'location' => 'nullable|string|max:255',
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

            if ($request->hasFile('image')) {
                if ($inventory->image_url && file_exists(public_path($inventory->image_url))) {
                    unlink(public_path($inventory->image_url));
                }

                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/inventory'), $imageName);
                $data['image_url'] = 'uploads/inventory/' . $imageName;
            }

            $inventory->update($data);

            // Check if inventory is now low stock and send email notification
            $this->checkLowStockAndNotify($inventory->fresh());

            return response()->json([
                'success' => true,
                'data' => $inventory->fresh(),
                'message' => 'Inventory updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory',
                'error' => $e->getMessage()
            ], 500);
        }
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

    public function destroy(string $id)
    {
        try {
            $inventory = Inventory::findOrFail($id);

            if ($inventory->image_url && file_exists(public_path($inventory->image_url))) {
                unlink(public_path($inventory->image_url));
            }

            $inventory->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inventory deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batches for specific inventory item
     */
    public function getBatches(string $id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            $batches = $inventory->batches()
                ->orderBy('date_received', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $batches,
                'message' => 'Batches retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batches',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
