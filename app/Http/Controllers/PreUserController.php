<?php

namespace App\Http\Controllers;

use App\Models\PreUser;
use App\Models\User;
use App\Models\PreUserOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Providers\EmailProvider;
use Illuminate\Support\Carbon;
use App\Providers\PhoneNumberProvider;

class PreUserController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validator = app('validator')->make($request->all(), [
                'fullname' => 'required|string|max:255',
                'email' => 'required|max:255',
                'password' => 'required|string|max:255',
                'phonenumber' => [
                    'nullable',
                    'string',
                    'max:20',
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $error = PhoneNumberProvider::validate($value);
                            if ($error) {
                                $fail($error);
                            }
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            if (PreUser::where('email', $validated['email'])->exists()) {
                return response()->json(['message' => 'Email already exists'], 400);
            }
               if (User::where('email', $validated['email'])->exists()) {
                return response()->json(['message' => 'Email already exists'], 400);
            }

            $validated['password'] = Hash::make($validated['password']);

            if (!EmailProvider::validate($validated['email'])) {
                return response()->json(['message' => 'Invalid email address'], 400);
            }

            $preUser = PreUser::create($validated);
            EmailProvider::handleVerifyEmail($validated['email']);

            return response()->json($preUser, 201);
        } catch (\Exception $e) {
            error_log("Unable to create user: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create pre-user'], 400);
        }
    }
      public function verifyOtp(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $preUser = PreUser::where('email', $request->email)->first();

        if (!$preUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validOtp = PreUserOtp::where('pre_user_id', $preUser->id)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$validOtp) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }

        $user = User::create($preUser->toArray() + ['status' => 'active']);

        // Delete the pre-user record (this should cascade delete the OTP record)
        $preUser->delete();

        return response()->json(['message' => 'User verified and created successfully', 'user' => $user], 201);
    }

    public function resendOtp(Request $request)
    {
        try {
            $validator = app('validator')->make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $email = $request->email;
            $preUser = PreUser::where('email', $email)->first();

            if (!$preUser) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Check OTP send limit (max 3 per day)
            $today = Carbon::now()->startOfDay();
            $otpCount = PreUserOtp::where('pre_user_id', $preUser->id)
                ->where('created_at', '>=', $today)
                ->count();

            if ($otpCount >= 3) {
                return response()->json(['error' => 'Maximum OTP sending limit reached. Please try again tomorrow.'], 429);
            }

            // Delete existing OTPs for this user
            // PreUserOtp::where('pre_user_id', $preUser->id)->delete();

            // Send new OTP
            EmailProvider::handleVerifyEmail($email);

            return response()->json(['message' => 'OTP sent successfully'], 200);
        } catch (\Exception $e) {
            error_log("Unable to resend OTP: " . $e->getMessage());
            return response()->json(['message' => 'Failed to resend OTP'], 400);
        }
    }
}