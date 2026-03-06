<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Providers\EmailProvider;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPasswordOtp;

class ForgotPasswordController extends Controller
{
    public function sendOtp(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->email;

        // Check rate limit: max 3 OTPs in the last 24 hours
        $count = PasswordResetOtp::where('email', $email)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        if ($count >= 3) {
            return response()->json(['message' => 'Maximum OTP limit reached for today. Please try again later.'], 429);
        }

        $otp = (string) rand(100000, 999999);

        PasswordResetOtp::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        Mail::to($email)->send(new ForgotPasswordOtp($otp));
        
        return response()->json(['message' => 'OTP sent successfully.']);
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

        $record = PasswordResetOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 400);
        }

        return response()->json(['message' => 'OTP verified successfully.']);
    }

    public function resetPassword(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $record = PasswordResetOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        $record->expires_at = Carbon::now();
        $record->save();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}