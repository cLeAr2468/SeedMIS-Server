<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user_type' => 'staff',
                        'user' => [
                            'id' => $staff->id,
                            'email' => $staff->email,
                            'name' => $staff->first_name . ' ' . $staff->last_name,
                            'position' => $staff->position,
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
                Mail::raw("Password Reset Request\n\nYour OTP Code: $otp\n\nThis OTP will expire in 10 minutes.\n\n- SeedMIS System", function($message) use ($email) {
                    $message->to($email)
                            ->subject('SeedMIS - Password Reset OTP');
                });
            } catch (\Exception $e) {
                // Log email error but continue (for development)
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
}
