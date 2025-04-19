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
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Maksimum percobaan OTP yang diizinkan
    const MAX_OTP_ATTEMPTS = 3;

    /**
     * Register a new user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            \Log::info('Registration attempt:', $request->all());
            
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|unique:users',
                'email' => 'required|email|unique:users',
                'phone' => 'required|string',
                'password' => 'required|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                \Log::warning('Validation failed:', ['errors' => $validator->errors()->toArray()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate OTP yang lebih aman menggunakan random_bytes
            $otp = strval(hexdec(bin2hex(random_bytes(3))) % 1000000);
            $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
            $otpExpires = now()->addMinutes(5);

            \Log::info('Creating user with data:', [
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone
            ]);

            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'otp' => $otp,
                'otp_expires_at' => $otpExpires,
                'otp_attempts' => 0
            ]);

            try {
                Mail::to($user->email)->send(new OtpMail($otp));
                \Log::info('OTP email sent successfully to: ' . $user->email);
                
                return response()->json([
                    'success' => true,
                    'message' => 'OTP has been sent to your email',
                    'data' => [
                        'email' => $user->email
                    ]
                ], 201);
            } catch (\Exception $e) {
                \Log::error('Failed to send OTP email:', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Account created but failed to send OTP. Please contact support.',
                    'data' => [
                        'email' => $user->email
                    ]
                ], 201);
            }

        } catch (\Exception $e) {
            \Log::error('Registration error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again later.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Login user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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

            $credentials = $request->only('username', 'password');
            $user = User::where('username', $credentials['username'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'debug_message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP code
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Cek jumlah percobaan
            if ($user->otp_attempts >= self::MAX_OTP_ATTEMPTS) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many attempts. Please request a new OTP.'
                ], 429);
            }

            // Increment percobaan
            $user->increment('otp_attempts');

            // Cek OTP
            if ($user->otp !== $request->otp) {
                $remainingAttempts = self::MAX_OTP_ATTEMPTS - $user->otp_attempts;
                return response()->json([
                    'success' => false,
                    'message' => "Invalid OTP. {$remainingAttempts} attempts remaining."
                ], 400);
            }

            // Cek expired
            if ($user->otp_expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new one.'
                ], 400);
            }

            // Verifikasi berhasil
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->otp_attempts = 0;
            $user->email_verified_at = now();
            $user->save();

            // Generate token dengan expiry
            $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('OTP verification error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Please try again.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Resend OTP
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Generate new OTP
            $otp = strval(hexdec(bin2hex(random_bytes(3))) % 1000000);
            $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
            $otpExpires = now()->addMinutes(5);

            // Update user with new OTP
            $user->otp = $otp;
            $user->otp_expires_at = $otpExpires;
            $user->otp_attempts = 0;
            $user->save();

            try {
                // Send new OTP email
                Mail::to($user->email)->send(new OtpMail($otp));
                \Log::info('New OTP email sent successfully to: ' . $user->email);

                return response()->json([
                    'success' => true,
                    'message' => 'OTP baru telah dikirim ke email Anda',
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to send new OTP email:', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim OTP baru. Silakan coba lagi.',
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Resend OTP error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses permintaan. Silakan coba lagi.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
    }

    /**
     * Logout user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        // Validate input data
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

        // Update user
        $user = $request->user();
        
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        
        if ($request->has('address')) {
            $user->address = $request->address;
        }
        
        if ($request->has('birth_date')) {
            $user->birth_date = $request->birth_date;
        }
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user
            ]
        ]);
    }
}