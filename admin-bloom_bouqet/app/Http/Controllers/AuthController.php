<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OtpVerificationMail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate request data
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|unique:users,username',
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|min:10|max:13',
                'address' => 'required|string',
                'birth_date' => 'required|date',
                'password' => 'required|min:6|confirmed',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            // Create new user
            $user = User::create([
                'username' => $request->username,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'birth_date' => $request->birth_date,
                'password' => bcrypt($request->password),
            ]);
            
            // Generate OTP
            $otp = rand(100000, 999999);
            
            // Save OTP
            DB::table('email_verifications')->updateOrInsert(
                ['email' => $user->email],
                [
                    'otp' => $otp,
                    'expired_at' => now()->addMinutes(3), // 3 minutes expiration
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            try {
                // Send OTP Email
                Mail::to($user->email)->send(new OtpVerificationMail($otp));
            } catch (\Exception $e) {
                // Log the email error but don't fail the registration
                Log::error('Failed to send OTP email: ' . $e->getMessage());
                
                // Return successful registration but with warning
                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful, but verification email could not be sent',
                    'data' => [
                        'user' => $user,
                        'email_error' => true,
                        'requires_verification' => false, // Skip verification due to email error
                    ],
                ], 201);
            }
            
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Please check your email for OTP verification.',
                'data' => [
                    'user' => $user,
                    'requires_verification' => true,
                ],
            ], 201);
            
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Registration error: ' . $e->getMessage());
            
            // Check if this is a database error
            if (str_contains($e->getMessage(), 'SQLSTATE')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed. Please try again later.',
                    'debug' => $e->getMessage(),
                ], 500);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again later.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
} 