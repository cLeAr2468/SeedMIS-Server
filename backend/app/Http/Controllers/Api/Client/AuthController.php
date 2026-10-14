<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Client registration
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'organization' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'required|string|max:255',
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

            // Check if contact number exists in other tables
            $contactExistsInStaff = \App\Models\Staff::where('contact_number', $request->contact_number)->exists();
            $contactExistsInClient = Client::where('contact_number', $request->contact_number)->exists();
            $contactExistsInCustomer = \App\Models\Customer::where('contact_number', $request->contact_number)->exists();

            if ($contactExistsInStaff || $contactExistsInClient || $contactExistsInCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'contact_number' => ['This contact number is already registered in the system.']
                    ]
                ], 422);
            }

            // Generate client ID
            $lastClient = Client::orderBy('id', 'desc')->first();
            if ($lastClient && preg_match('/CLT-(\d+)/', $lastClient->client_id, $matches)) {
                $lastNumber = intval($matches[1]);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $clientId = 'CLT-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Create client
            $client = Client::create([
                'client_id' => $clientId,
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
                'status' => 'Active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'client' => $client
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Client login
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $client = Client::where('email', $request->email)->first();

            if (!$client || !Hash::check($request->password, $client->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if account is active
            if ($client->status !== 'Active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive. Please contact administrator.'
                ], 403);
            }

            // Generate simple token (without Sanctum)
            $token = base64_encode($client->id . '|' . $client->email . '|' . time());

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'client' => $client,
                    'token' => $token
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Client logout
     */
    public function logout(Request $request)
    {
        try {
            // Simple logout - just return success
            // Frontend will clear the token from localStorage

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated client
     */
    public function me(Request $request)
    {
        try {
            // Get token from Authorization header
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Decode simple token
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            
            if (count($parts) !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            $clientId = $parts[0];
            $client = Client::find($clientId);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $client
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forgot password - send OTP
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $client = Client::where('email', $request->email)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email address.'
                ], 404);
            }

            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Delete old OTP
            \DB::table('password_resets')->where('email', $request->email)->delete();

            // Store OTP
            \DB::table('password_resets')->insert([
                'email' => $request->email,
                'otp' => $otp,
                'user_type' => 'client',
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send OTP via email
            try {
                $userName = trim($client->first_name . ' ' . $client->last_name);
                \Mail::to($request->email)->send(new \App\Mail\PasswordResetOTP($otp, $userName));
                
                \Log::info('OTP email sent successfully to: ' . $request->email);
            } catch (\Exception $e) {
                // Log email error
                \Log::error('Failed to send OTP email: ' . $e->getMessage());
                \Log::error('Email config - Host: ' . config('mail.host') . ', Port: ' . config('mail.port'));
                
                // Still return success with OTP for development (remove in production)
                return response()->json([
                    'success' => true,
                    'message' => 'OTP generated (email failed)',
                    'otp' => $otp, // For development only
                    'email_error' => $e->getMessage()
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email',
                'otp' => $otp // Remove in production
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reset = \DB::table('password_resets')
                ->where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('user_type', 'client')
                ->where('expires_at', '>', now())
                ->first();

            if (!$reset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
                'data' => [
                    'email' => $reset->email
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
                'password' => [
                    'required',
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

            $reset = \DB::table('password_resets')
                ->where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('user_type', 'client')
                ->where('expires_at', '>', now())
                ->first();

            if (!$reset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 401);
            }

            // Update password
            Client::where('email', $request->email)->update([
                'password' => Hash::make($request->password)
            ]);

            // Delete OTP
            \DB::table('password_resets')->where('email', $request->email)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update client profile
     */
    public function updateProfile(Request $request)
    {
        try {
            // Get token from Authorization header
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Decode simple token
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            
            if (count($parts) !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            $clientId = $parts[0];
            $client = Client::find($clientId);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'organization' => 'sometimes|string|max:255',
                'first_name' => 'sometimes|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'suffix' => 'nullable|string|max:10',
                'email' => 'sometimes|email|unique:clients,email,' . $clientId,
                'contact_number' => 'sometimes|string|max:20',
                'purok' => 'nullable|string|max:255',
                'barangay' => 'sometimes|string|max:255',
                'municipality' => 'sometimes|string|max:255',
                'province' => 'sometimes|string|max:255',
                'region' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update client profile
            $client->update($request->only([
                'organization', 'first_name', 'middle_name', 'last_name', 'suffix',
                'email', 'contact_number', 'purok', 'barangay', 
                'municipality', 'province', 'region'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $client
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
