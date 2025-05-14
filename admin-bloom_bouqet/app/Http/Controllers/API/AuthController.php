<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    const MAX_OTP_ATTEMPTS = 3;

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|unique:users',
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'phone' => 'required|string',
                'password' => 'required|min:6|confirmed',
                'address' => 'required|string',
                'birth_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate 6-digit OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpires = now()->addMinutes(5); // Extend to 5 minutes for better user experience

            $userData = [
                'username' => $request->username,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'address' => $request->address,
                'birth_date' => $request->birth_date,
                'otp' => $otp,
                'otp_expires_at' => $otpExpires,
            ];

            // Store registration data in cache for 15 minutes
            Cache::put('user_registration_' . $request->email, $userData, now()->addMinutes(15));

            // Send OTP via email
            try {
                // Log configuration for debugging
                \Log::info('Mail configuration before sending:', [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'username' => config('mail.mailers.smtp.username'),
                    // Don't log password for security reasons
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                ]);
                
                Mail::to($request->email)->send(new OtpMail($otp));
                
                // Log successful email sending
                \Log::info('OTP email sent successfully to: ' . $request->email);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Registration initiated successfully. OTP has been sent to your email.',
                    'data' => [
                        'email' => $request->email,
                        'otp_expires_in' => '5 minutes'
                    ]
                ], 201);
            } catch (\Exception $emailError) {
                // Log email failure with detailed error message
                \Log::error('Email sending failed during registration: ' . $emailError->getMessage());
                \Log::error('Detailed exception:', ['exception' => $emailError]);
                
                // Return error response with more detailed information
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to send verification email. Please try again later or contact support.',
                    'error_code' => 'email_service_unavailable',
                    'debug' => config('app.debug') ? $emailError->getMessage() : null
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Registration exception: ' . $e->getMessage());
            \Log::error('Detailed exception:', ['exception' => $e]);
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again later.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('username', $request->username)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please ensure you have completed the registration and OTP verification process.'
                ], 404);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password. Please try again.'
                ], 401);
            }

            if (!$user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not verified. Please verify your email before logging in.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => ['user' => $user, 'token' => $token]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'debug_message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|digits:6',
            ]);

            $userData = Cache::get('user_registration_' . $request->email);

            if (!$userData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP. Please try again.',
                ], 400);
            }

            if ($userData['otp'] !== $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Please try again.',
                ], 400);
            }

            if (now()->greaterThan($userData['otp_expires_at'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expired OTP. Please request a new one.',
                ], 400);
            }

            // Save user data to the database
            $user = User::create([
                'username' => $userData['username'],
                'full_name' => $userData['full_name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'password' => $userData['password'],
                'address' => $userData['address'],
                'birth_date' => $userData['birth_date'],
                'email_verified_at' => now(),
            ]);

            Cache::forget('user_registration_' . $request->email);

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully. Please log in to your account.',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during verification. Please try again.',
            ], 500);
        }
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
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

            // Check if this is for new registration (data in cache)
            $userData = Cache::get('user_registration_' . $request->email);
            
            if ($userData) {
                // This is a pending registration - update OTP in cache
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpExpires = now()->addMinutes(5);
                
                $userData['otp'] = $otp;
                $userData['otp_expires_at'] = $otpExpires;
                
                // Update the cached registration data
                Cache::put('user_registration_' . $request->email, $userData, now()->addMinutes(15));
                
                try {
                    // Send new OTP via email
                    Mail::to($request->email)->send(new OtpMail($otp));
                    
                    // Log successful email sending
                    \Log::info('Resent OTP email sent successfully to: ' . $request->email);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'New OTP has been sent to your email.',
                        'data' => [
                            'email' => $request->email,
                            'otp_expires_in' => '5 minutes'
                        ]
                    ]);
                } catch (\Exception $emailError) {
                    // Log email failure
                    \Log::error('Email sending failed during OTP resend: ' . $emailError->getMessage());
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to send verification email. Please try again later or contact support.',
                        'error_code' => 'email_service_unavailable'
                    ], 500);
                }
            }
            
            // If not in cache, check if user exists in database
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found. Please register first.',
                ], 404);
            }
            
            // For existing users who need OTP resend (password reset, etc.)
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpires = now()->addMinutes(5);

            // Store OTP in database for existing user
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => $otpExpires,
                'otp_attempts' => 0,
            ]);

            try {
                Mail::to($user->email)->send(new OtpMail($otp));
                
                // Log successful email sending
                \Log::info('Resent OTP email to registered user: ' . $request->email);
                
                return response()->json([
                    'success' => true,
                    'message' => 'OTP has been sent to your email.',
                    'data' => [
                        'email' => $user->email,
                        'otp_expires_in' => '5 minutes'
                    ]
                ]);
            } catch (\Exception $emailError) {
                // Log email failure
                \Log::error('Email sending failed during OTP resend to registered user: ' . $emailError->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to send verification email. Please try again later or contact support.',
                    'error_code' => 'email_service_unavailable'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Resend OTP exception: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP. Please try again.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => ['user' => $request->user()]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|min:10|max:15',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->update($request->only(['name', 'email', 'phone', 'address', 'birth_date']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => ['user' => $user]
        ]);
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email)
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching user data.',
            ], 500);
        }
    }

    /**
     * Truncate the users table
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function truncateUsers()
    {
        try {
            DB::table('users')->truncate(); // Truncate the users table

            return response()->json([
                'success' => true,
                'message' => 'Users table has been truncated. Auto-increment ID has been reset.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to truncate users table.',
                'debug_message' => $e->getMessage()
            ], 500);
        }
    }
}