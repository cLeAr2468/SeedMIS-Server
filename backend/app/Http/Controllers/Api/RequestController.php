<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request;
use App\Models\Client;
use App\Mail\RequestStatusChanged;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\ActivityLogService;

class RequestController extends Controller
{
    /**
     * Get available seedlings for request form.
     */
    public function getAvailableSeedlings()
    {
        try {
            $seedlings = \App\Models\Inventory::where('status', 'Available')
                ->where('total_quantity', '>', 0)
                ->select('id', 'seedling_type', 'price_per_unit', 'total_quantity')
                ->orderBy('seedling_type', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $seedlings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching available seedlings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users (clients) by name, email, or organization.
     */
    public function searchUsers(HttpRequest $request)
    {
        try {
            $query = $request->input('query');

            if (!$query || strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $users = Client::where(function($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('middle_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('organization', 'LIKE', "%{$query}%")
                  ->orWhere('client_id', 'LIKE', "%{$query}%");
            })
            ->select([
                'id',
                'client_id',
                'first_name',
                'middle_name',
                'last_name',
                'email',
                'organization',
                'contact_number',
                'barangay',
                'municipality',
                'province'
            ])
            ->limit(10)
            ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of requests.
     */
    public function index()
    {
        try {
            $requests = Request::with('client:id,first_name,middle_name,last_name,email,organization')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($request) {
                    return [
                        'id' => $request->id,
                        'client_id' => $request->client_id,
                        'requester' => $request->requester_name,
                        'organization' => $request->client->organization ?? null,
                        'seedling_type' => $request->seedling_type,
                        'seedlingType' => $request->seedling_type, // Alias for frontend
                        'quantity' => $request->quantity,
                        'purpose' => $request->purpose,
                        'contact_number' => $request->contact_number,
                        'requested_date' => $request->requested_date ? $request->requested_date->format('Y-m-d') : null,
                        'requestedDate' => $request->requested_date ? $request->requested_date->format('F j, Y') : null, // Formatted for display
                        'price_per_unit' => $request->price_per_unit,
                        'pricePerUnit' => $request->price_per_unit, // Alias for frontend
                        'total_price' => $request->total_price,
                        'totalPrice' => $request->total_price, // Alias for frontend
                        'status' => $request->status,
                        'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $request->updated_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created request.
     */
    public function store(HttpRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'client_id' => 'required|exists:clients,id',
                'seedling_type' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'purpose' => 'required|string',
                'contact_number' => 'nullable|string|max:20',
                'requested_date' => 'required|date',
                'created_by_user_type' => 'nullable|string|in:admin,staff',
                'created_by_user_id' => 'nullable|string', // Staff ID or Admin ID
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if staff account is inactive
            $createdByUserType = $request->input('created_by_user_type');
            $createdByUserId = $request->input('created_by_user_id');
            
            if ($createdByUserType === 'staff' && $createdByUserId) {
                $staff = \App\Models\Staff::where('staff_id', $createdByUserId)->first();
                if ($staff && $staff->status === 'Inactive') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account is inactive. You cannot process transactions. Please contact administrator.'
                    ], 403);
                }
            }

            // Get inventory item to check availability and get price
            $inventory = \App\Models\Inventory::where('seedling_type', $request->seedling_type)
                ->where('status', 'Available')
                ->first();

            if (!$inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seedling type not available in inventory'
                ], 422);
            }

            $quantity = $request->input('quantity');

            // Check if enough stock is available
            if ($inventory->total_quantity < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock. Only {$inventory->total_quantity} available."
                ], 422);
            }

            // Get client to check if LGU
            $client = \App\Models\Client::find($request->client_id);
            
            // Check if organization contains "LGU" (case insensitive)
            $isLGU = stripos($client->organization, 'LGU') !== false;

            // Calculate total price (0 if LGU, otherwise normal calculation)
            $pricePerUnit = $isLGU ? 0 : $inventory->price_per_unit;
            $totalPrice = $pricePerUnit * $quantity;

            // Deduct from inventory (reserve the quantity)
            $inventory->total_quantity -= $quantity;
            $inventory->reserved_quantity += $quantity;
            $inventory->save();

            // Determine initial status: if created by admin/staff, auto-approve
            $createdByUserType = $request->input('created_by_user_type');
            $initialStatus = ($createdByUserType === 'admin' || $createdByUserType === 'staff') 
                ? 'Approved' 
                : 'Pending';

            // Create the request
            $requestData = Request::create([
                'client_id' => $request->client_id,
                'seedling_type' => $request->seedling_type,
                'quantity' => $quantity,
                'purpose' => $request->purpose,
                'contact_number' => $request->contact_number,
                'requested_date' => $request->requested_date,
                'price_per_unit' => $pricePerUnit,
                'total_price' => $totalPrice,
                'status' => $initialStatus,
            ]);

            // Load the client relationship
            $requestData->load('client:id,first_name,middle_name,last_name,email,organization');

            $message = $initialStatus === 'Approved' 
                ? 'Request created and automatically approved. Quantity reserved from inventory.'
                : 'Request created successfully. Quantity reserved from inventory.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $requestData->id,
                    'client_id' => $requestData->client_id,
                    'requester' => $requestData->requester_name,
                    'organization' => $requestData->client->organization ?? null,
                    'seedling_type' => $requestData->seedling_type,
                    'quantity' => $requestData->quantity,
                    'purpose' => $requestData->purpose,
                    'contact_number' => $requestData->contact_number,
                    'requested_date' => $requestData->requested_date->format('Y-m-d'),
                    'price_per_unit' => $requestData->price_per_unit,
                    'total_price' => $requestData->total_price,
                    'status' => $requestData->status,
                    'created_at' => $requestData->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified request.
     */
    public function show($id)
    {
        try {
            $request = Request::with('client')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $request->id,
                    'client_id' => $request->client_id,
                    'requester' => $request->requester_name,
                    'organization' => $request->client->organization ?? null,
                    'seedling_type' => $request->seedling_type,
                    'quantity' => $request->quantity,
                    'purpose' => $request->purpose,
                    'contact_number' => $request->contact_number,
                    'requested_date' => $request->requested_date->format('Y-m-d'),
                    'price_per_unit' => $request->price_per_unit,
                    'total_price' => $request->total_price,
                    'status' => $request->status,
                    'client' => $request->client,
                    'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $request->updated_at->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified request (including rejection handling).
     */
    public function update(HttpRequest $httpRequest, $id)
    {
        try {
            // Check if staff account is inactive before allowing update
            $userId = $httpRequest->input('user_id');
            $userType = $httpRequest->input('user_type');
            
            if ($userType === 'staff' && $userId) {
                $staff = \App\Models\Staff::where('staff_id', $userId)->first();
                if ($staff && $staff->status === 'Inactive') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account is inactive. You cannot process transactions. Please contact administrator.'
                    ], 403);
                }
            }
            
            $request = Request::findOrFail($id);
            $oldStatus = $request->status;

            $validator = Validator::make($httpRequest->all(), [
                'seedling_type' => 'sometimes|string|max:255',
                'quantity' => 'sometimes|integer|min:1',
                'purpose' => 'sometimes|string',
                'contact_number' => 'nullable|string|max:20',
                'requested_date' => 'sometimes|date',
                'status' => 'sometimes|in:Pending,Approved,Rejected,Released',
                'price_per_unit' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $httpRequest->only([
                'seedling_type',
                'quantity',
                'purpose',
                'contact_number',
                'requested_date',
                'status',
                'price_per_unit'
            ]);

            // Handle status change to Rejected - return quantity to inventory
            if (isset($updateData['status']) && $updateData['status'] === 'Rejected' && $oldStatus === 'Pending') {
                $inventory = \App\Models\Inventory::where('seedling_type', $request->seedling_type)->first();
                
                if ($inventory) {
                    // Return reserved quantity back to total quantity
                    $inventory->total_quantity += $request->quantity;
                    $inventory->reserved_quantity -= $request->quantity;
                    $inventory->save();
                }

                // Send rejection email
                $this->sendStatusEmail($request, 'Rejected');
            }

            // Handle status change to Approved - keep quantity reserved
            if (isset($updateData['status']) && $updateData['status'] === 'Approved' && $oldStatus === 'Pending') {
                // Quantity remains reserved in inventory
                // No inventory changes needed, just status update
                // The reserved quantity will be deducted when status changes to 'Released'
                
                // Send approval email
                $this->sendStatusEmail($request, 'Approved');
            }

            // Handle status change to Released - deduct from reserved quantity
            if (isset($updateData['status']) && $updateData['status'] === 'Released' && $oldStatus === 'Approved') {
                $inventory = \App\Models\Inventory::where('seedling_type', $request->seedling_type)->first();
                
                if ($inventory) {
                    // Deduct from reserved quantity (already removed from total when request was created)
                    $inventory->reserved_quantity -= $request->quantity;
                    $inventory->save();
                }

                // Send release email
                $this->sendStatusEmail($request, 'Released');
            }

            // Recalculate total price if quantity or price_per_unit changed
            if (isset($updateData['quantity']) || isset($updateData['price_per_unit'])) {
                $quantity = $updateData['quantity'] ?? $request->quantity;
                $pricePerUnit = $updateData['price_per_unit'] ?? $request->price_per_unit;
                $updateData['total_price'] = $quantity * $pricePerUnit;
            }

            $request->update($updateData);
            $request->load('client');

            // Log activity if status changed
            if (isset($updateData['status']) && $oldStatus !== $updateData['status']) {
                $userId = $httpRequest->input('user_id');
                $userType = $httpRequest->input('user_type');
                
                if ($userId && $userType) {
                    $clientName = $request->client ? 
                        trim($request->client->first_name . ' ' . $request->client->last_name) : 
                        'Unknown Client';
                    
                    ActivityLogService::logRequestStatusChange(
                        $userId,
                        $userType,
                        $request->id,
                        $oldStatus,
                        $updateData['status'],
                        $clientName
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Request updated successfully',
                'data' => $request
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified request.
     */
    public function destroy($id)
    {
        try {
            $request = Request::findOrFail($id);
            $request->delete();

            return response()->json([
                'success' => true,
                'message' => 'Request deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get default price for seedling type.
     */
    private function getSeedlingPrice($seedlingType)
    {
        $prices = [
            'Mahogany' => 5.00,
            'Narra' => 8.00,
            'Gmelina' => 4.00,
            'Mangium' => 6.00,
            'Tindalo' => 10.00,
            'Acacia' => 5.00,
            'Ipil-ipil' => 4.00,
        ];

        return $prices[$seedlingType] ?? 5.00; // Default price
    }

    /**
     * Get request metrics/statistics.
     */
    public function metrics()
    {
        try {
            $totalRequests = Request::count();
            $pendingRequests = Request::where('status', 'Pending')->count();
            $approvedRequests = Request::where('status', 'Approved')->count();
            $rejectedRequests = Request::where('status', 'Rejected')->count();
            $releasedRequests = Request::where('status', 'Released')->count();
            $totalRevenue = Request::where('status', 'Released')->sum('total_price');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_requests' => $totalRequests,
                    'pending' => $pendingRequests,
                    'approved' => $approvedRequests,
                    'rejected' => $rejectedRequests,
                    'released' => $releasedRequests,
                    'total_revenue' => $totalRevenue,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly sales (total price of Released requests for current month).
     */
    public function getMonthlySales(HttpRequest $httpRequest)
    {
        try {
            $validator = Validator::make($httpRequest->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = $httpRequest->input('start_date');
            $endDate = $httpRequest->input('end_date');
            
            $currentYear = date('Y');
            $currentMonth = date('m');
            $currentYearMonth = date('Y-m');

            $query = Request::where('status', 'Released');

            if ($startDate && $endDate) {
                // Custom date range - use whereBetween
                $query->where(function($q) use ($startDate, $endDate) {
                    $q->where(function($subQ) use ($startDate, $endDate) {
                        $subQ->whereBetween('requested_date', [$startDate, $endDate]);
                    })->orWhere(function($subQ) use ($startDate, $endDate) {
                        $subQ->whereNull('requested_date')
                             ->whereBetween('updated_at', [$startDate, $endDate]);
                    });
                });
            } else {
                // Default: current month
                $query->where(function($q) use ($currentYear, $currentMonth) {
                    $q->where(function($subQ) use ($currentYear, $currentMonth) {
                        $subQ->whereYear('requested_date', $currentYear)
                             ->whereMonth('requested_date', $currentMonth);
                    })->orWhere(function($subQ) use ($currentYear, $currentMonth) {
                        $subQ->whereNull('requested_date')
                             ->whereYear('updated_at', $currentYear)
                             ->whereMonth('updated_at', $currentMonth);
                    });
                });
            }

            $monthlySales = $query->sum('total_price');
            $count = $query->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'monthly_sales' => (float) $monthlySales,
                    'month' => $currentYearMonth,
                    'count' => $count,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching monthly sales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send email notification when request status changes.
     */
    private function sendStatusEmail($request, $status)
    {
        try {
            // Load client relationship if not already loaded
            if (!$request->relationLoaded('client')) {
                $request->load('client');
            }

            // Check if client has email
            if ($request->client && $request->client->email) {
                // Send email using queue for better performance
                Mail::to($request->client->email)->send(
                    new RequestStatusChanged($request, $status)
                );

                \Log::info("Email notification sent for request #{$request->id} - Status: {$status} - To: {$request->client->email}");
            } else {
                \Log::warning("Cannot send email for request #{$request->id} - Client email not found");
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request update
            \Log::error("Failed to send email for request #{$request->id}: " . $e->getMessage());
        }
    }
}
