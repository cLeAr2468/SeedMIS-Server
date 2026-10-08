<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Client;
use App\Models\Staff;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Generate next customer ID
     */
    public function getNextCustomerId()
    {
        try {
            $lastCustomer = Customer::orderBy('id', 'desc')->first();
            
            if ($lastCustomer && preg_match('/CUST-(\d+)/', $lastCustomer->customer_id, $matches)) {
                $lastNumber = intval($matches[1]);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            $customerId = 'CUST-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            return response()->json([
                'success' => true,
                'data' => ['customer_id' => $customerId]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate customer ID',
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
            // Only show active customers (not upgraded)
            $customers = Customer::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $customers,
                'message' => 'Customers retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customers',
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
            // Check if email exists in staff, clients, customers, or admins table
            $emailExistsInStaff = Staff::where('email', $request->email)->exists();
            $emailExistsInClient = Client::where('email', $request->email)->exists();
            $emailExistsInCustomer = Customer::where('email', $request->email)->exists();
            $emailExistsInAdmin = Admin::where('email', $request->email)->exists();

            if ($emailExistsInStaff || $emailExistsInClient || $emailExistsInCustomer || $emailExistsInAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'email' => ['This email is already registered in the system.']
                    ]
                ], 422);
            }

            // Check if contact number exists in staff, clients, or customers table
            $contactExistsInStaff = Staff::where('contact_number', $request->contact_number)->exists();
            $contactExistsInClient = Client::where('contact_number', $request->contact_number)->exists();
            $contactExistsInCustomer = Customer::where('contact_number', $request->contact_number)->exists();

            if ($contactExistsInStaff || $contactExistsInClient || $contactExistsInCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'contact_number' => ['This contact number is already registered in the system.']
                    ]
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|string|unique:customers,customer_id|max:255',
                'organization' => 'nullable|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'contact_number' => 'required|string|max:20',
                'barangay' => 'required|string|max:255',
                'municipality' => 'required|string|max:255',
                'province' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = Customer::create([
                'customer_id' => $request->customer_id,
                'organization' => $request->organization,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'barangay' => $request->barangay,
                'municipality' => $request->municipality,
                'province' => $request->province,
            ]);

            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Customer created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
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
            $customer = Customer::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Customer retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
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
            $customer = Customer::findOrFail($id);

            // Check if email exists in other tables (excluding current customer record)
            if ($request->has('email') && $request->email !== $customer->email) {
                $emailExistsInStaff = Staff::where('email', $request->email)->exists();
                $emailExistsInClient = Client::where('email', $request->email)->exists();
                $emailExistsInCustomer = Customer::where('email', $request->email)
                    ->where('id', '!=', $id)
                    ->exists();
                $emailExistsInAdmin = Admin::where('email', $request->email)->exists();

                if ($emailExistsInStaff || $emailExistsInClient || $emailExistsInCustomer || $emailExistsInAdmin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'email' => ['This email is already registered in the system.']
                        ]
                    ], 422);
                }
            }

            // Check if contact number exists in other tables (excluding current customer record)
            if ($request->has('contact_number') && $request->contact_number !== $customer->contact_number) {
                $contactExistsInStaff = Staff::where('contact_number', $request->contact_number)->exists();
                $contactExistsInClient = Client::where('contact_number', $request->contact_number)->exists();
                $contactExistsInCustomer = Customer::where('contact_number', $request->contact_number)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($contactExistsInStaff || $contactExistsInClient || $contactExistsInCustomer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'contact_number' => ['This contact number is already registered in the system.']
                        ]
                    ], 422);
                }
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'sometimes|string|unique:customers,customer_id,' . $id . '|max:255',
                'organization' => 'nullable|string|max:255',
                'first_name' => 'sometimes|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'contact_number' => 'sometimes|string|max:20',
                'barangay' => 'sometimes|string|max:255',
                'municipality' => 'sometimes|string|max:255',
                'province' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer->update($request->only([
                'customer_id',
                'organization',
                'first_name',
                'middle_name',
                'last_name',
                'email',
                'contact_number',
                'barangay',
                'municipality',
                'province',
            ]));

            return response()->json([
                'success' => true,
                'data' => $customer->fresh(),
                'message' => 'Customer updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
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
            $customer = Customer::findOrFail($id);
            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upgrade customer to client account (add password and login capability)
     */
    public function upgradeToClient(string $id)
    {
        try {
            $customer = Customer::findOrFail($id);

            // Check if already upgraded
            if ($customer->upgraded_to_client_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer has already been upgraded to a client account'
                ], 422);
            }

            // Generate next client ID
            $lastClient = Client::orderBy('id', 'desc')->first();
            if ($lastClient && preg_match('/CLT-(\d+)/', $lastClient->client_id, $matches)) {
                $lastNumber = intval($matches[1]);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $clientId = 'CLT-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Generate temporary password
            $tempPassword = 'Temp' . rand(1000, 9999) . '!';

            // Create client from customer data
            $client = Client::create([
                'client_id' => $clientId,
                'first_name' => $customer->first_name,
                'middle_name' => $customer->middle_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'contact_number' => $customer->contact_number,
                'organization' => $customer->organization,
                'barangay' => $customer->barangay,
                'municipality' => $customer->municipality,
                'province' => $customer->province,
                'password' => \Hash::make($tempPassword),
            ]);

            // Mark customer as upgraded
            $customer->update([
                'upgraded_to_client_id' => $client->id,
                'is_active' => false,
                'upgraded_at' => now(),
            ]);

            // Transfer all customer requests to the new client account
            \App\Models\Request::where('customer_id', $customer->id)
                ->where('requester_type', 'customer')
                ->update([
                    'client_id' => $client->id,
                    'customer_id' => null,
                    'requester_type' => 'client'
                ]);

            // Send email with temporary password
            try {
                \Illuminate\Support\Facades\Mail::to($customer->email)
                    ->send(new \App\Mail\AccountUpgraded($client, $tempPassword));
                
                \Illuminate\Support\Facades\Log::info('Account upgrade email sent to: ' . $customer->email);
            } catch (\Exception $e) {
                // Log error but don't fail the upgrade
                \Illuminate\Support\Facades\Log::error('Failed to send upgrade email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer successfully upgraded to client account',
                'data' => [
                    'client' => $client,
                    'temporary_password' => $tempPassword,
                    'email_sent' => true,
                    'note' => 'An email with login credentials has been sent to ' . $customer->email
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upgrade customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
