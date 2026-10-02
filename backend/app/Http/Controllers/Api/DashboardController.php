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
            
            // Previous month dates for trend comparison
            $prevMonthStart = date('Y-m-01', strtotime('-1 month'));
            $prevMonthEnd = date('Y-m-t', strtotime('-1 month'));

            // Card Metrics - Current Month
            // Total Seedlings: sum of created + transferred from production_history
            $totalSeedlings = DB::table('production_history')
                ->whereIn('action_type', ['created', 'transferred'])
                ->whereBetween('changed_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('new_quantity');
                
            $totalSeedlingsPrevMonth = DB::table('production_history')
                ->whereIn('action_type', ['created', 'transferred'])
                ->whereBetween('changed_at', [$prevMonthStart, $prevMonthEnd])
                ->sum('new_quantity');

            // Available Stock from inventories
            $availableStock = Inventory::where('status', 'Available')->sum('total_quantity');

            // Pending Requests
            $pendingRequests = Request::whereNotIn('status', ['Released', 'Rejected', 'Cancelled'])->count();

            // Distributed - current month only
            $distributed = Request::where('status', 'Released')
                ->where(function($q) use ($currentMonthStart, $currentMonthEnd) {
                    $q->whereBetween('requested_date', [$currentMonthStart, $currentMonthEnd]);
                })->orWhere(function($q) use ($currentMonthStart, $currentMonthEnd) {
                    $q->where('status', 'Released')
                      ->whereNull('requested_date')
                      ->whereBetween('updated_at', [$currentMonthStart, $currentMonthEnd]);
                })
                ->sum('quantity');
                
            $distributedPrevMonth = Request::where('status', 'Released')
                ->where(function($q) use ($prevMonthStart, $prevMonthEnd) {
                    $q->whereBetween('requested_date', [$prevMonthStart, $prevMonthEnd]);
                })->orWhere(function($q) use ($prevMonthStart, $prevMonthEnd) {
                    $q->where('status', 'Released')
                      ->whereNull('requested_date')
                      ->whereBetween('updated_at', [$prevMonthStart, $prevMonthEnd]);
                })
                ->sum('quantity');

            // Calculate trends
            $totalSeedlingsTrend = $this->calculateTrend($totalSeedlings, $totalSeedlingsPrevMonth);
            $distributedTrend = $this->calculateTrend($distributed, $distributedPrevMonth);

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

            // Production vs Ready Chart (Monthly data for current year from production_history)
            $monthlyProduction = DB::table('production_history')
                ->whereYear('changed_at', $currentYear)
                ->select(
                    DB::raw('MONTH(changed_at) as month'),
                    DB::raw('SUM(CASE WHEN action_type = "created" THEN new_quantity ELSE 0 END) as sown'),
                    DB::raw('SUM(CASE WHEN new_stage = "Ready" AND action_type = "stage_update" THEN new_quantity ELSE 0 END) as ready')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $productionChart = [];
            foreach ($monthlyProduction as $prod) {
                $productionChart[] = [
                    'month' => date('M', mktime(0, 0, 0, $prod->month, 1)),
                    'sown' => (float) $prod->sown,
                    'ready' => (float) $prod->ready,
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
                        'total_seedlings' => [
                            'value' => (float) $totalSeedlings,
                            'trend' => $totalSeedlingsTrend,
                        ],
                        'available_stock' => [
                            'value' => (float) $availableStock,
                            'trend' => null, // No trend for stock
                        ],
                        'pending_requests' => [
                            'value' => $pendingRequests,
                            'trend' => null, // No trend for pending
                        ],
                        'distributed' => [
                            'value' => (float) $distributed,
                            'trend' => $distributedTrend,
                        ],
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
    
    /**
     * Calculate trend comparison
     */
    private function calculateTrend($current, $previous)
    {
        if ($previous == 0) {
            if ($current > 0) {
                return [
                    'direction' => 'up',
                    'percentage' => 100,
                    'label' => 'from last month'
                ];
            }
            return null;
        }
        
        $percentageChange = round((($current - $previous) / $previous) * 100, 1);
        
        return [
            'direction' => $percentageChange >= 0 ? 'up' : 'down',
            'percentage' => abs($percentageChange),
            'label' => 'from last month'
        ];
    }
}
