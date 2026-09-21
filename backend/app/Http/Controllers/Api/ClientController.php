<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
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
            $validator = Validator::make($request->all(), [
                'client_id' => 'required|string|unique:clients,client_id|max:255',
                'organization' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:clients,email|max:255',
                'contact_number' => 'required|string|max:20',
                'barangay' => 'required|string|max:255',
                'municipality' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $client = Client::create([
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
                'password' => Hash::make($request->password),
            ]);

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

            $validator = Validator::make($request->all(), [
                'client_id' => 'sometimes|string|unique:clients,client_id,' . $id . '|max:255',
                'organization' => 'sometimes|string|max:255',
                'first_name' => 'sometimes|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:clients,email,' . $id . '|max:255',
                'contact_number' => 'sometimes|string|max:20',
                'barangay' => 'sometimes|string|max:255',
                'municipality' => 'sometimes|string|max:255',
                'province' => 'sometimes|string|max:255',
                'password' => 'sometimes|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->except(['password', 'password_confirmation']);
            
            if ($request->has('password')) {
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
