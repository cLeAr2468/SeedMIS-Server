<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Staff;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    /**
     * Generate next client ID
     */
    public function getNextClientId()
    {
        try {
            $lastClient = Client::orderBy('id', 'desc')->first();
            
            if ($lastClient && preg_match('/CLT-(\d+)/', $lastClient->client_id, $matches)) {
                $lastNumber = intval($matches[1]);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            $clientId = 'CLT-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            return response()->json([
                'success' => true,
                'data' => ['client_id' => $clientId]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate client ID',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $clients = Client::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $clients,
                'message' => 'Clients retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Check if email exists in staff, clients, or admins table
            $emailExistsInStaff = Staff::where('email', $request->email)->exists();
            $emailExistsInClient = Client::where('email', $request->email)->exists();
            $emailExistsInAdmin = Admin::where('email', $request->email)->exists();

            if ($emailExistsInStaff || $emailExistsInClient || $emailExistsInAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'email' => ['This email is already registered in the system.']
                    ]
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'client_id' => 'required|string|unique:clients,client_id|max:255',
                'organization' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'contact_number' => [
                    'required',
                    'string',
                    'max:20',
                    function ($attribute, $value, $fail) {
                        // Check if contact number exists in clients, staff, or customers
                        $existsInClient = Client::where('contact_number', $value)->exists();
                        $existsInStaff = Staff::where('contact_number', $value)->exists();
                        $existsInCustomer = \App\Models\Customer::where('contact_number', $value)->exists();
                        
                        if ($existsInClient || $existsInStaff || $existsInCustomer) {
                            $fail('This contact number is already registered in the system.');
                        }
                    }
                ],
                'barangay' => 'required|string|max:255',
                'municipality' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'password' => [
                    'nullable',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]+$/'
                ],
            ], [
                'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#)',
                'password.min' => 'Password must be at least 8 characters long',
                'password.confirmed' => 'Password confirmation does not match',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clientData = [
                'client_id' => $request->client_id,
                'organization' => $request->organization,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'barangay' => $request->barangay,
                'municipality' => $request->municipality,
                'province' => $request->province,
            ];

            // Only hash and add password if provided
            if ($request->filled('password')) {
                $clientData['password'] = Hash::make($request->password);
            }

            $client = Client::create($clientData);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client',
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
            $client = Client::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
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
            $client = Client::findOrFail($id);

            // Check if email exists in other tables (excluding current client record)
            if ($request->has('email') && $request->email !== $client->email) {
                $emailExistsInStaff = Staff::where('email', $request->email)->exists();
                $emailExistsInClient = Client::where('email', $request->email)
                    ->where('id', '!=', $id)
                    ->exists();
                $emailExistsInAdmin = Admin::where('email', $request->email)->exists();

                if ($emailExistsInStaff || $emailExistsInClient || $emailExistsInAdmin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'email' => ['This email is already registered in the system.']
                        ]
                    ], 422);
                }
            }

            $validator = Validator::make($request->all(), [
                'client_id' => 'sometimes|string|unique:clients,client_id,' . $id . '|max:255',
                'organization' => 'sometimes|string|max:255',
                'first_name' => 'sometimes|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'contact_number' => [
                    'sometimes',
                    'string',
                    'max:20',
                    function ($attribute, $value, $fail) use ($id) {
                        // Check if contact number exists in other records
                        $existsInClient = Client::where('contact_number', $value)->where('id', '!=', $id)->exists();
                        $existsInStaff = Staff::where('contact_number', $value)->exists();
                        $existsInCustomer = \App\Models\Customer::where('contact_number', $value)->exists();
                        
                        if ($existsInClient || $existsInStaff || $existsInCustomer) {
                            $fail('This contact number is already registered in the system.');
                        }
                    }
                ],
                'barangay' => 'sometimes|string|max:255',
                'municipality' => 'sometimes|string|max:255',
                'province' => 'sometimes|string|max:255',
                'password' => 'nullable|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->except(['password', 'password_confirmation']);
            
            // Only hash and add password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $client->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $client->fresh(),
                'message' => 'Client updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client',
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
            $client = Client::findOrFail($id);
            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
