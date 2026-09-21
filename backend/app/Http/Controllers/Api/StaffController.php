<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $staff = Staff::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $staff,
                'message' => 'Staff retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve staff',
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
                'staff_id' => 'required|string|unique:staff,staff_id|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:staff,email|max:255',
                'position' => 'required|string|max:255',
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

            $staff = Staff::create([
                'staff_id' => $request->staff_id,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'position' => $request->position,
                'contact_number' => $request->contact_number,
                'barangay' => $request->barangay,
                'municipality' => $request->municipality,
                'province' => $request->province,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'success' => true,
                'data' => $staff,
                'message' => 'Staff created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create staff',
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
            $staff = Staff::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $staff,
                'message' => 'Staff retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found',
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
            $staff = Staff::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'staff_id' => 'sometimes|string|unique:staff,staff_id,' . $id . '|max:255',
                'first_name' => 'sometimes|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:staff,email,' . $id . '|max:255',
                'position' => 'sometimes|string|max:255',
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

            $staff->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $staff->fresh(),
                'message' => 'Staff updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff',
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
            $staff = Staff::findOrFail($id);
            $staff->delete();

            return response()->json([
                'success' => true,
                'message' => 'Staff deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete staff',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
