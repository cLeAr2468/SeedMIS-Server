<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\Production;
use App\Models\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller
{
    /**
     * Get all targets with progress
     */
    public function index()
    {
        try {
            $currentYear = date('Y');
            $currentMonth = date('Y-m');

            $targets = Target::active()->get();

            return response()->json([
                'success' => true,
                'data' => $targets
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching targets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get targets with progress calculation
     */
    public function getProgress()
    {
        try {
            $currentYear = date('Y');
            $currentMonth = date('Y-m');

            // Annual Production Target
            $annualTarget = Target::active()
                ->ofType('annual_production')
                ->forPeriod($currentYear)
                ->first();

            $totalProduced = Production::whereYear('created_at', $currentYear)->sum('quantity');

            // Monthly Distribution Target
            $monthlyTarget = Target::active()
                ->ofType('monthly_distribution')
                ->forPeriod($currentMonth)
                ->first();

            $monthlyDistributed = Request::where('status', 'Released')
                ->whereYear('updated_at', date('Y'))
                ->whereMonth('updated_at', date('m'))
                ->sum('quantity');

            // Revenue Target
            $revenueTarget = Target::active()
                ->ofType('revenue')
                ->forPeriod($currentYear)
                ->first();

            $totalRevenue = Request::where('status', 'Released')
                ->whereYear('updated_at', $currentYear)
                ->sum('total_price');

            // Seedling Type Targets
            $seedlingTargets = Target::active()
                ->ofType('seedling_type')
                ->forPeriod($currentYear)
                ->get()
                ->map(function($target) use ($currentYear) {
                    $produced = Production::where('seedling_type', $target->seedling_type)
                        ->whereYear('created_at', $currentYear)
                        ->sum('quantity');

                    $percentage = $target->target_value > 0 
                        ? ($produced / $target->target_value) * 100 
                        : 0;

                    return [
                        'id' => $target->id,
                        'seedling_type' => $target->seedling_type,
                        'target' => (float) $target->target_value,
                        'current' => (float) $produced,
                        'percentage' => round($percentage, 2),
                        'remaining' => max(0, $target->target_value - $produced),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : ($percentage >= 50 ? 'needs-attention' : 'behind')),
                    ];
                });

            $response = [
                'annual_production' => $annualTarget ? [
                    'id' => $annualTarget->id,
                    'target' => (float) $annualTarget->target_value,
                    'current' => (float) $totalProduced,
                    'percentage' => $annualTarget->target_value > 0 
                        ? round(($totalProduced / $annualTarget->target_value) * 100, 2) 
                        : 0,
                    'remaining' => max(0, $annualTarget->target_value - $totalProduced),
                    'period' => $annualTarget->period,
                ] : null,

                'monthly_distribution' => $monthlyTarget ? [
                    'id' => $monthlyTarget->id,
                    'target' => (float) $monthlyTarget->target_value,
                    'current' => (float) $monthlyDistributed,
                    'percentage' => $monthlyTarget->target_value > 0 
                        ? round(($monthlyDistributed / $monthlyTarget->target_value) * 100, 2) 
                        : 0,
                    'remaining' => max(0, $monthlyTarget->target_value - $monthlyDistributed),
                    'period' => $monthlyTarget->period,
                ] : null,

                'revenue' => $revenueTarget ? [
                    'id' => $revenueTarget->id,
                    'target' => (float) $revenueTarget->target_value,
                    'current' => (float) $totalRevenue,
                    'percentage' => $revenueTarget->target_value > 0 
                        ? round(($totalRevenue / $revenueTarget->target_value) * 100, 2) 
                        : 0,
                    'remaining' => max(0, $revenueTarget->target_value - $totalRevenue),
                    'period' => $revenueTarget->period,
                ] : null,

                'seedling_types' => $seedlingTargets,
            ];

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new target
     */
    public function store(HttpRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'target_type' => 'required|in:annual_production,monthly_distribution,revenue,seedling_type',
                'seedling_type' => 'required_if:target_type,seedling_type|nullable|string',
                'target_value' => 'required|numeric|min:0',
                'period' => 'required|string',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if target already exists for this type and period
            $existing = Target::where('target_type', $request->target_type)
                ->where('period', $request->period);

            if ($request->target_type === 'seedling_type') {
                $existing->where('seedling_type', $request->seedling_type);
            }

            $existingTarget = $existing->first();

            if ($existingTarget) {
                // Update existing
                $existingTarget->update([
                    'target_value' => $request->target_value,
                    'description' => $request->description,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Target updated successfully',
                    'data' => $existingTarget
                ]);
            }

            // Create new
            $target = Target::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Target created successfully',
                'data' => $target
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating target',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update target
     */
    public function update(HttpRequest $request, $id)
    {
        try {
            $target = Target::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'target_value' => 'sometimes|numeric|min:0',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $target->update($request->only(['target_value', 'description', 'is_active']));

            return response()->json([
                'success' => true,
                'message' => 'Target updated successfully',
                'data' => $target
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating target',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete target
     */
    public function destroy($id)
    {
        try {
            $target = Target::findOrFail($id);
            $target->delete();

            return response()->json([
                'success' => true,
                'message' => 'Target deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting target',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly target vs actual distribution progress.
     */
    public function getMonthlyTargetVsActual()
    {
        try {
            $currentYearMonth = date('Y-m');
            $currentYear = date('Y');
            $currentMonth = date('m');

            // Get the monthly distribution target
            $monthlyTarget = Target::active()
                ->ofType('monthly_distribution')
                ->forPeriod($currentYearMonth)
                ->first();

            $targetValue = $monthlyTarget ? $monthlyTarget->target_value : 0;

            // Get actual distributed (sum of quantity from Released requests in current month)
            $actualDistributed = Request::where('status', 'Released')
                ->whereYear('updated_at', $currentYear)
                ->whereMonth('updated_at', $currentMonth)
                ->sum('quantity');

            // Calculate remaining and percentage
            $remaining = max(0, $targetValue - $actualDistributed);
            $percentage = $targetValue > 0 ? round(($actualDistributed / $targetValue) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'target' => (float) $targetValue,
                    'actual' => (float) $actualDistributed,
                    'remaining' => (float) $remaining,
                    'percentage' => (float) $percentage,
                    'month' => $currentYearMonth
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching monthly target vs actual',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
