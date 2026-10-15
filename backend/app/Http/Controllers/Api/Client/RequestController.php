<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Request;
use Illuminate\Http\Request as HttpRequest;

class RequestController extends Controller
{
    /**
     * Get authenticated client's requests
     */
    public function index(HttpRequest $request)
    {
        try {
            $client = $request->user();
            
            $requests = Request::where('client_id', $client->id)
                ->where('requester_type', 'client')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($req) {
                    return [
                        'id' => $req->id,
                        'seedling_type' => $req->seedling_type,
                        'quantity' => $req->quantity,
                        'purpose' => $req->purpose,
                        'contact_number' => $req->contact_number,
                        'requested_date' => $req->requested_date ? $req->requested_date->format('Y-m-d') : null,
                        'price_per_unit' => $req->price_per_unit,
                        'total_price' => $req->total_price,
                        'status' => $req->status,
                        'request_source' => $req->request_source,
                        'cancellation_reason' => $req->cancellation_reason,
                        'rejection_reason' => $req->rejection_reason,
                        'created_at' => $req->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $req->updated_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $requests
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
