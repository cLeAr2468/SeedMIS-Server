<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Staff;
use App\Models\Client;
use App\Mail\PasswordResetOTP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\ActivityLogService;

class AuthController extends Controller
{
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

            $email = $request->email;
            $password = $request->password;

            // Check admin first
            $admin = Admin::where('email', $email)->first();
            if ($admin && Hash::check($password, $admin->password)) {
                // Log login activity
                ActivityLogService::logLogin($admin->id, 'admin', $admin->name);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user_type' => 'admin',
                        'user' => [
                            'id' => $admin->id,
                            'email' => $admin->email,
                            'name' => $admin->name,
                            'role' => $admin->role,
                        ]
                    ]
                ], 200);
            }

            // Check staff
            $staff = Staff::where('email', $email)->first();
            if ($staff && Hash::check($password, $staff->password)) {
                $staffName = trim($staff->first_name . ' ' . $staff->last_name);
                
                // Log login activity - use staff_id instead of numeric id
                ActivityLogService::logLogin($staff->staff_id, 'staff', $staffName);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user_type' => 'staff',
                        'user' => [
                            'id' => $staff->staff_id, // Use staff_id for consistency
                            'email' => $staff->email,
                            'name' => $staffName,
                            'position' => $staff->position,
                            'status' => $staff->status,
                        ]
                    ]
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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

            $email = $request->email;
            $userType = null;

            // Check if admin
            $admin = Admin::where('email', $email)->first();
            if ($admin) {
                $userType = 'admin';
            } else {
                // Check if staff
                $staff = Staff::where('email', $email)->first();
                if ($staff) {
                    $userType = 'staff';
                }
            }

            if (!$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found'
                ], 404);
            }

            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Delete old OTP
            DB::table('password_resets')->where('email', $email)->delete();

            // Store OTP
            DB::table('password_resets')->insert([
                'email' => $email,
                'otp' => $otp,
                'user_type' => $userType,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send OTP via email to user's actual email
            try {
                $userName = $admin ? $admin->name : ($staff->first_name . ' ' . $staff->last_name);
                Mail::to($email)->send(new PasswordResetOTP($otp, $userName));
                
                \Log::info('OTP email sent successfully to: ' . $email);
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

            $reset = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('otp', $request->otp)
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
                    'email' => $reset->email,
                    'user_type' => $reset->user_type
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

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reset = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('expires_at', '>', now())
                ->first();

            if (!$reset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 401);
            }

            // Update password
            if ($reset->user_type === 'admin') {
                Admin::where('email', $request->email)->update([
                    'password' => Hash::make($request->password)
                ]);
            } else {
                Staff::where('email', $request->email)->update([
                    'password' => Hash::make($request->password)
                ]);
            }

            // Delete OTP
            DB::table('password_resets')->where('email', $request->email)->delete();

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

    public function getProfile(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $userType = $request->input('user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID and user type are required'
                ], 400);
            }

            if ($userType === 'admin') {
                $user = Admin::find($userId);
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Admin not found'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'created_at' => $user->created_at,
                        'user_type' => 'admin'
                    ]
                ], 200);
            } else {
                // Staff - user_id might be staff_id (STF-0001) or numeric id
                $user = Staff::where('staff_id', $userId)->first();
                if (!$user) {
                    // Fallback: try numeric id
                    $user = Staff::find($userId);
                }
                
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Staff not found'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $user->staff_id, // Always return staff_id
                        'staff_id' => $user->staff_id,
                        'first_name' => $user->first_name,
                        'middle_name' => $user->middle_name,
                        'last_name' => $user->last_name,
                        'name' => trim($user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name),
                        'email' => $user->email,
                        'position' => $user->position,
                        'contact_number' => $user->contact_number,
                        'barangay' => $user->barangay,
                        'municipality' => $user->municipality,
                        'province' => $user->province,
                        'status' => $user->status,
                        'created_at' => $user->created_at,
                        'user_type' => 'staff'
                    ]
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $userType = $request->input('user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID and user type are required'
                ], 400);
            }

            if ($userType === 'admin') {
                $user = Admin::find($userId);
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Admin not found'
                    ], 404);
                }

                $validator = Validator::make($request->all(), [
                    'name' => 'sometimes|string|max:255',
                    'email' => 'sometimes|email',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Check email uniqueness across all user types (excluding current user)
                if ($request->has('email') && $request->email !== $user->email) {
                    // Check in admins table
                    if (Admin::where('email', $request->email)->where('id', '!=', $userId)->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This email is already registered in the system'
                        ], 422);
                    }
                    // Check in staff table
                    if (Staff::where('email', $request->email)->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This email is already registered in the system'
                        ], 422);
                    }
                    // Check in clients table
                    if (Client::where('email', $request->email)->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This email is already registered in the system'
                        ], 422);
                    }
                }

                $user->update($request->only(['name', 'email']));

                // Log activity - pass only the fields that were actually sent
                $changedFields = [];
                if ($request->has('name')) {
                    $changedFields['name'] = $user->name;
                }
                if ($request->has('email')) {
                    $changedFields['email'] = $user->email;
                }

                ActivityLogService::logProfile($userId, $userType, 'updated', $changedFields);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'user_type' => 'admin'
                    ]
                ], 200);
            } else {
                // Staff - user_id might be staff_id (STF-0001) or numeric id
                $user = Staff::where('staff_id', $userId)->first();
                if (!$user) {
                    // Fallback: try numeric id
                    $user = Staff::find($userId);
                }
                
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Staff not found'
                    ], 404);
                }

                $validator = Validator::make($request->all(), [
                    'first_name' => 'sometimes|string|max:255',
                    'middle_name' => 'nullable|string|max:255',
                    'last_name' => 'sometimes|string|max:255',
                    'email' => 'sometimes|email',
                    'position' => 'sometimes|string|max:255',
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

                // Check email uniqueness across all user types (excluding current user)
                if ($request->has('email') && $request->email !== $user->email) {
                    // Check in admins table
                    if (Admin::where('email', $request->email)->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This email is already registered in the system'
                        ], 422);
                    }
                    // Check in staff table
                    if (Staff::where('email', $request->email)->where('id', '!=', $user->id)->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This email is already registered in the system'
                        ], 422);
                    }
                    // Check in clients table
                    if (Client::where('email', $request->email)->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This email is already registered in the system'
                        ], 422);
                    }
                }

                $user->update($request->only([
                    'first_name', 'middle_name', 'last_name', 
                    'email', 'position', 'contact_number',
                    'barangay', 'municipality', 'province'
                ]));

                // Log activity - use staff_id and pass only fields that were sent
                $changedFields = $request->only([
                    'first_name', 'middle_name', 'last_name', 
                    'email', 'position', 'contact_number',
                    'barangay', 'municipality', 'province'
                ]);

                ActivityLogService::logProfile($user->staff_id, $userType, 'updated', $changedFields);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => [
                        'id' => $user->staff_id, // Return staff_id
                        'staff_id' => $user->staff_id,
                        'first_name' => $user->first_name,
                        'middle_name' => $user->middle_name,
                        'last_name' => $user->last_name,
                        'name' => trim($user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name),
                        'email' => $user->email,
                        'position' => $user->position,
                        'contact_number' => $user->contact_number,
                        'barangay' => $user->barangay,
                        'municipality' => $user->municipality,
                        'province' => $user->province,
                        'user_type' => 'staff'
                    ]
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $userType = $request->input('user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID and user type are required'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]+$/'
                ],
            ], [
                'new_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#)',
                'new_password.min' => 'Password must be at least 8 characters long',
                'new_password.confirmed' => 'Password confirmation does not match',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($userType === 'admin') {
                $user = Admin::find($userId);
            } else {
                // Staff - user_id might be staff_id (STF-0001) or numeric id
                $user = Staff::where('staff_id', $userId)->first();
                if (!$user) {
                    // Fallback: try numeric id
                    $user = Staff::find($userId);
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 401);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Log activity - use staff_id for staff users
            $logUserId = ($userType === 'staff' && isset($user->staff_id)) ? $user->staff_id : $userId;
            ActivityLogService::logProfile($logUserId, $userType, 'password_changed');

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
