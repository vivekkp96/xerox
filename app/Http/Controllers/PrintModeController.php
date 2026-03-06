<?php

namespace App\Http\Controllers;

use App\Models\PrintMode;
use Illuminate\Http\JsonResponse;

class PrintModeController extends Controller
{
    /**
     * Returns a list of available print modes and their prices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $modes = PrintMode::all();

        return response()->json($modes);
    }

    /**
     * Store a new print mode.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(): JsonResponse
    {
        $validated = request()->validate([
            'name' => 'required|string|unique:print_modes,name',
            'value' => 'required|string|unique:print_modes,value',
            'status' => 'required|in:active,inactive',
        ]);

        $mode = PrintMode::create($validated);

        return response()->json($mode, 201);
    }

    /**
     * Update a print mode.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id): JsonResponse
    {
        $validated = request()->validate([
            'name' => 'sometimes|string|unique:print_modes,name,' . $id,
            'value' => 'sometimes|string|unique:print_modes,value,' . $id,
            'status' => 'sometimes|in:active,inactive',
        ]);

        $mode = PrintMode::findOrFail($id);
        $mode->update($validated);

        return response()->json([
            'message' => 'Print mode updated successfully',
            'data' => $mode,
        ]);
    }
}