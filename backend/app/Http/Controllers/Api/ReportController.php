<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request;
use App\Models\Production;
use App\Models\Inventory;
use App\Models\Client;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get distribution report (requests/releases)
     */
    public function getDistributionReport(HttpRequest $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $status = $request->input('status', 'Released'); // Default to Released

            $query = Request::with('client')
                ->where('status', $status);

            if ($startDate && $endDate) {
                $query->whereBetween('requested_date', [$startDate, $endDate]);
            }

            $distributions = $query->orderBy('requested_date', 'desc')->get();

            $data = $distributions->map(function ($req) {
                // Get client info
                $clientName = '';
                $organization = 'N/A';
                $contactNumber = 'N/A';
                
                if ($req->client_id) {
                    $client = Client::find($req->client_id);
                    if ($client) {
                        // Combine first_name, middle_name, last_name
                        $nameParts = array_filter([
                            $client->first_name ?? '',
                            $client->middle_name ?? '',
                            $client->last_name ?? ''
                        ]);
                        $clientName = implode(' ', $nameParts) ?: 'N/A';
                        
                        $organization = $client->organization ?? 'N/A';
                        $contactNumber = $client->contact_number ?? 'N/A';
                    }
                } else {
                    $clientName = 'N/A';
                }
                
                // Format date properly
                $formattedDate = 'N/A';
                if ($req->requested_date) {
                    try {
                        $formattedDate = date('M d, Y', strtotime($req->requested_date));
                    } catch (\Exception $e) {
                        $formattedDate = $req->requested_date;
                    }
                }
                
                return [
                    'id' => $req->id,
                    'date' => $formattedDate,
                    'client_name' => $clientName,
                    'organization' => $organization,
                    'contact' => $contactNumber,
                    'seedling_type' => $req->seedling_type,
                    'quantity' => $req->quantity,
                    'price_per_unit' => $req->price_per_unit,
                    'total_price' => $req->total_price,
                    'purpose' => $req->purpose ?? 'N/A',
                    'status' => $req->status,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching distribution report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get production report (from production_history)
     */
    public function getProductionReport(HttpRequest $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = DB::table('production_history')
                ->join('productions', 'production_history.production_id', '=', 'productions.id')
                ->select(
                    'production_history.id',
                    'production_history.batch_id',
                    'production_history.seedling_type',
                    'production_history.action_type',
                    'production_history.previous_stage',
                    'production_history.new_stage',
                    'production_history.previous_quantity',
                    'production_history.new_quantity',
                    'production_history.changed_at',
                    'production_history.notes',
                    'productions.scientific_name',
                    'productions.date_sown',
                    'productions.location',
                    'productions.assigned_staff'
                );

            if ($startDate && $endDate) {
                $query->whereBetween('production_history.changed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }

            $histories = $query->orderBy('production_history.changed_at', 'desc')->get();

            $data = $histories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'batch_id' => $history->batch_id,
                    'seedling_type' => $history->seedling_type,
                    'scientific_name' => $history->scientific_name,
                    'date_sown' => $history->date_sown,
                    'action_type' => $history->action_type,
                    'previous_stage' => $history->previous_stage,
                    'new_stage' => $history->new_stage,
                    'previous_quantity' => $history->previous_quantity,
                    'new_quantity' => $history->new_quantity,
                    'quantity_change' => $history->new_quantity - $history->previous_quantity,
                    'location' => $history->location,
                    'assigned_staff' => $history->assigned_staff,
                    'changed_at' => $history->changed_at,
                    'notes' => $history->notes,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching production report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inventory report
     */
    public function getInventoryReport()
    {
        try {
            $inventories = Inventory::orderBy('seedling_type', 'asc')->get();

            $data = $inventories->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'seedling_type' => $inv->seedling_type,
                    'classification' => $inv->classification,
                    'total_quantity' => $inv->total_quantity,
                    'status' => $inv->status,
                    'price_per_unit' => $inv->price_per_unit,
                    'location' => $inv->location,
                    'min_stock_level' => $inv->min_stock_level,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching inventory report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary report
     */
    public function getSummaryReport(HttpRequest $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Total Productions
            $productionQuery = Production::query();
            if ($startDate && $endDate) {
                $productionQuery->whereBetween('date_sown', [$startDate, $endDate]);
            }
            $totalProduced = $productionQuery->sum('quantity_sown');
            $totalReady = Production::where('stage', 'Ready')->sum('current_quantity');

            // Total Distributions
            $distributionQuery = Request::where('status', 'Released');
            if ($startDate && $endDate) {
                $distributionQuery->whereBetween('requested_date', [$startDate, $endDate]);
            }
            $totalDistributed = $distributionQuery->sum('quantity');
            $totalRevenue = $distributionQuery->sum('total_price');

            // Total Inventory
            $totalInventory = Inventory::sum('total_quantity');
            $lowStockItems = Inventory::whereRaw('total_quantity <= min_stock_level')->count();

            // Seedling type breakdown
            $byType = Request::where('status', 'Released')
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('requested_date', [$startDate, $endDate]);
                })
                ->select('seedling_type', DB::raw('SUM(quantity) as total'))
                ->groupBy('seedling_type')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'production' => [
                        'total_produced' => (int) $totalProduced,
                        'total_ready' => (int) $totalReady,
                    ],
                    'distribution' => [
                        'total_distributed' => (int) $totalDistributed,
                        'total_revenue' => (float) $totalRevenue,
                    ],
                    'inventory' => [
                        'total_stock' => (int) $totalInventory,
                        'low_stock_items' => (int) $lowStockItems,
                    ],
                    'by_seedling_type' => $byType,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching summary report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
