<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UPIController extends Controller
{
    /**
     * Upload or update UPI admin image.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAdminImage(Request $request): JsonResponse
    {
        $user = $request->admin;

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        try {
            // Create directory if it doesn't exist
            if (!file_exists(storage_path('app/public/upi'))) {
                mkdir(storage_path('app/public/upi'), 0755, true);
            }

            // Delete existing image if it exists
            $filePath = storage_path('app/public/upi/upi_admin.jpeg');
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Store the new image
            $image = $request->file('image');
            $image->move(storage_path('app/public/upi'), 'upi_admin.jpeg');

            return response()->json([
                'message' => 'UPI image uploaded successfully',
                'path' => asset('storage/upi/upi_admin.jpeg'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload UPI image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get UPI admin image.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminImage(): JsonResponse
    {
        try {
            $filePath = storage_path('app/public/upi/upi_admin.jpeg');
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'message' => 'UPI image not found',
                    'path' => null,
                ], 404);
            }

            return response()->json([
                'path' => asset('storage/upi/upi_admin.jpeg'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve UPI image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete UPI admin image.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAdminImage(): JsonResponse
    {
        try {
            $filePath = storage_path('app/public/upi/upi_admin.jpeg');
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'message' => 'UPI image not found',
                ], 404);
            }

            unlink($filePath);

            return response()->json([
                'message' => 'UPI image deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete UPI image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
