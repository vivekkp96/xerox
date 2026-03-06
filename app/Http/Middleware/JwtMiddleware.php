<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\Admin;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return response()->json(['error' => 'Invalid token format'], 401);
        }

        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $parts;

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, env('APP_KEY', 'secret'), true);
        $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        if ($base64UrlSignature !== $validSignature) {
            return response()->json(['error' => 'Invalid token signature'], 401);
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)));

        if (!$payload || !isset($payload->exp) || $payload->exp < time()) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        if (isset($payload->role) && $payload->role === 'admin') {
            $admin = Admin::find($payload->sub);
            if (!$admin) {
                return response()->json(['error' => 'Admin not found'], 401);
            }
            $request->merge(['admin' => $admin]);
        } else {
            $user = User::find($payload->sub);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }
            $request->merge(['user' => $user]);
        }

        return $next($request);
    }
}