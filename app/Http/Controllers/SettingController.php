<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Get the UPI ID.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpiId(): JsonResponse
    {
        $setting = Setting::where('name', 'upi_id')->first();

        return response()->json([
            'upi_id' => $setting ? $setting->value : null,
        ]);
    }

    /**
     * Update the UPI ID.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUpiId(Request $request): JsonResponse
    {
       $user = $request->admin;

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $validated = $request->validate([
            'upi_id' => ['required', 'string', 'max:255', 'regex:/^[\w.-]+@[\w.-]+$/'],
        ]);

        Setting::updateOrCreate(
            ['name' => 'upi_id'],
            ['value' => $validated['upi_id']]
        );

        return response()->json([
            'message' => 'UPI ID updated successfully',
            'upi_id' => $validated['upi_id'],
        ]);
    }

    /**
     * Delete the UPI ID.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUpiId(): JsonResponse
    {
        Setting::where('name', 'upi_id')->update(['value' => null]);

        return response()->json([
            'message' => 'UPI ID deleted successfully',
        ]);
    }
}