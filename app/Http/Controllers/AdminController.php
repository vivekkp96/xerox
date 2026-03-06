<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $this->generateToken($admin);

        return response()->json([
            'message' => 'Admin login successful',
            'admin' => $admin,
            'token' => $token
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->admin);
    }

    private function generateToken(Admin $admin): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'sub' => $admin->id,
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + (AppConstants::ADMIN_TOKEN_VALIDITY_DAYS)
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, env('APP_KEY', 'secret'), true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}