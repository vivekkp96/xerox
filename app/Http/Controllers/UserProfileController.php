<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Providers\PhoneNumberProvider;

class UserProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validator = app('validator')->make($request->all(), [
            'fullname' => 'sometimes|string|max:255',
            'phonenumber' => [
                'sometimes',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (is_string($value)) {
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

        $user->update($validator->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }
}