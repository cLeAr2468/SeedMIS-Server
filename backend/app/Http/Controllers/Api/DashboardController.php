<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\Inventory;
use App\Models\Request;
use App\Models\Target;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData(HttpRequest $request)
    {
        try {
            $currentYear = date('Y');
            $currentMonth = date('m');
            
            // Current month dates
            $currentMonthStart = date('Y-m-01');
            $currentMonthEnd = date('Y-m-t');

            // Card Metrics - Current Month
            // Seedlings in Production (currently growing) with breakdown
            $inProduction = Production::sum('current_quantity');
            $inProductionGrafted = Production::whereIn('classification', ['grafted', 'crafted'])->sum('current_quantity');
            $inProductionSeedling = $inProduction - $inProductionGrafted;

            // Seedlings in Inventory (ready for distribution) with breakdown
            $inInventory = Inventory::sum('total_quantity');
            $inInventoryGrafted = Inventory::whereIn('classification', ['grafted', 'crafted'])->sum('total_quantity');
            $inInventorySeedling = $inInventory - $inInventoryGrafted;

            // Total Seedlings (Production + Inventory) with breakdown
            $totalSeedlings = $inProduction + $inInventory;
            $totalGrafted = $inProductionGrafted + $inInventoryGrafted;
            $totalSeedling = $inProductionSeedling + $inInventorySeedling;

            // Available Stock from inventories
            $availableStock = Inventory::where('status', 'Available')->sum('total_quantity');

            // Pending Requests
            $pendingRequests = Request::whereNotIn('status', ['Released', 'Rejected', 'Cancelled'])->count();

            // Distributed - current month only
            $distributed = Request::where('status', 'Released')
                ->where(function($q) use ($currentMonthStart, $currentMonthEnd) {
                    $q->whereBetween('requested_date', [$currentMonthStart, $currentMonthEnd])
                      ->orWhere(function($subQ) use ($currentMonthStart, $currentMonthEnd) {
                          $subQ->whereNull('requested_date')
                               ->whereBetween('updated_at', [$currentMonthStart, $currentMonthEnd]);
                      });
                })
                ->sum('quantity');

            // Target Progress (Annual Production)
            $annualTarget = Target::active()
                ->ofType('annual_production')
                ->forPeriod($currentYear)
                ->first();
            
            $annualProduced = DB::table('production_history')
                ->whereIn('action_type', ['created', 'transferred'])
                ->whereYear('changed_at', $currentYear)
                ->sum('new_quantity');
                
            $annualTargetValue = $annualTarget ? $annualTarget->target_value : 0;
            $annualPercentage = $annualTargetValue > 0 
                ? round(($annualProduced / $annualTargetValue) * 100, 2) 
                : 0;

            // Monthly Distribution Target
            $monthlyTarget = Target::active()
                ->ofType('monthly_distribution')
                ->forPeriod(date('Y-m'))
                ->first();
            
            $monthlyTargetValue = $monthlyTarget ? $monthlyTarget->target_value : 0;
            $monthlyPercentage = $monthlyTargetValue > 0 
                ? round(($distributed / $monthlyTargetValue) * 100, 2) 
                : 0;

            // Revenue Target
            $revenueTarget = Target::active()
                ->ofType('revenue')
                ->forPeriod($currentYear)
                ->first();
            
            $totalRevenue = Request::where('status', 'Released')
                ->where(function($q) use ($currentMonthStart, $currentMonthEnd) {
                    $q->whereBetween('requested_date', [$currentMonthStart, $currentMonthEnd]);
                })->orWhere(function($q) use ($currentMonthStart, $currentMonthEnd) {
                    $q->where('status', 'Released')
                      ->whereNull('requested_date')
                      ->whereBetween('updated_at', [$currentMonthStart, $currentMonthEnd]);
                })
                ->sum('total_price');
            
            $revenueTargetValue = $revenueTarget ? $revenueTarget->target_value : 0;
            $revenuePercentage = $revenueTargetValue > 0 
                ? round(($totalRevenue / $revenueTargetValue) * 100, 2) 
                : 0;

            // Seedling Type Progress
            $seedlingTargets = Target::active()
                ->ofType('seedling_type')
                ->forPeriod($currentYear)
                ->get();

            $seedlingProgress = [];
            foreach ($seedlingTargets as $target) {
                $produced = DB::table('production_history')
                    ->where('seedling_type', $target->seedling_type)
                    ->whereIn('action_type', ['created', 'transferred'])
                    ->whereYear('changed_at', $currentYear)
                    ->sum('new_quantity');

                $percentage = $target->target_value > 0 
                    ? round(($produced / $target->target_value) * 100, 2) 
                    : 0;

                $seedlingProgress[] = [
                    'seedling_type' => $target->seedling_type,
                    'target' => (float) $target->target_value,
                    'actual' => (float) $produced,
                    'percentage' => (float) $percentage,
                    'remaining' => max(0, $target->target_value - $produced),
                ];
            }

            // Production vs Ready Chart (Monthly data for current year)
            // Sown: Created batches
            // Ready: ALL batches that reached Ready stage (one-time count per batch when it became Ready)
            
            // Get unique batches that reached Ready stage (using DISTINCT batch_id)
            $readyBatches = DB::table('production_history')
                ->select('batch_id', DB::raw('MIN(changed_at) as first_ready_date'), 'new_quantity')
                ->where('new_stage', 'Ready')
                ->whereYear('changed_at', $currentYear)
                ->groupBy('batch_id', 'new_quantity')
                ->get();
            
            // Get sown batches by month
            $sownByMonth = DB::table('production_history')
                ->where('action_type', 'created')
                ->whereYear('changed_at', $currentYear)
                ->selectRaw('MONTH(changed_at) as month, SUM(new_quantity) as sown')
                ->groupBy('month')
                ->get()
                ->keyBy('month');
            
            // Group ready batches by month they became ready
            $readyByMonth = [];
            foreach ($readyBatches as $batch) {
                $month = date('n', strtotime($batch->first_ready_date)); // 1-12
                if (!isset($readyByMonth[$month])) {
                    $readyByMonth[$month] = 0;
                }
                $readyByMonth[$month] += $batch->new_quantity;
            }

            // Build chart data for all 12 months
            $productionChart = [];
            for ($month = 1; $month <= 12; $month++) {
                $sown = $sownByMonth->get($month)?->sown ?? 0;
                $ready = $readyByMonth[$month] ?? 0;
                
                $productionChart[] = [
                    'month' => date('M', mktime(0, 0, 0, $month, 1)),
                    'sown' => (float) $sown,
                    'ready' => (float) $ready,
                ];
            }

            // Actual vs Target Production (by seedling type from production_history)
            $actualVsTarget = [];
            foreach ($seedlingTargets as $target) {
                $actual = DB::table('production_history')
                    ->where('seedling_type', $target->seedling_type)
                    ->whereIn('action_type', ['created', 'transferred'])
                    ->sum('new_quantity');

                $actualVsTarget[] = [
                    'seedling' => $target->seedling_type,
                    'actual' => (float) $actual,
                    'target' => (float) $target->target_value,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'card_metrics' => [
                        'in_production' => (float) $inProduction,
                        'in_production_breakdown' => [
                            'grafted' => (float) $inProductionGrafted,
                            'seedling' => (float) $inProductionSeedling,
                        ],
                        'in_inventory' => (float) $inInventory,
                        'in_inventory_breakdown' => [
                            'grafted' => (float) $inInventoryGrafted,
                            'seedling' => (float) $inInventorySeedling,
                        ],
                        'total_seedlings' => (float) $totalSeedlings,
                        'total_seedlings_breakdown' => [
                            'grafted' => (float) $totalGrafted,
                            'seedling' => (float) $totalSeedling,
                        ],
                        'available_stock' => (float) $availableStock,
                        'pending_requests' => $pendingRequests,
                        'distributed' => (float) $distributed,
                    ],
                    'target_progress' => [
                        'annual_production' => [
                            'target' => (float) $annualTargetValue,
                            'actual' => (float) $annualProduced,
                            'percentage' => (float) $annualPercentage,
                        ],
                        'monthly_distribution' => [
                            'target' => (float) $monthlyTargetValue,
                            'actual' => (float) $distributed,
                            'percentage' => (float) $monthlyPercentage,
                        ],
                        'revenue' => [
                            'target' => (float) $revenueTargetValue,
                            'actual' => (float) $totalRevenue,
                            'percentage' => (float) $revenuePercentage,
                        ],
                    ],
                    'seedling_progress' => $seedlingProgress,
                    'production_chart' => $productionChart,
                    'actual_vs_target' => $actualVsTarget,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}
