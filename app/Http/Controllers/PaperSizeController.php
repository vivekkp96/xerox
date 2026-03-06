<?php

namespace App\Http\Controllers;

use App\Models\PaperSize;
use Illuminate\Http\JsonResponse;

class PaperSizeController extends Controller
{
    /**
     * Retrieve a list of available paper sizes with their prices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $paperSizes = PaperSize::all()->map(fn (PaperSize $paperSize) => [
            'id' => $paperSize->id,
            'name' => $paperSize->name,
            'value' => $paperSize->code,
            'status' => $paperSize->status,
        ]);

        return response()->json($paperSizes);
    }

    /**
     * Store a new paper size.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(): JsonResponse
    {
        $validated = request()->validate([
            'name' => 'required|string|unique:paper_sizes,name',
            'code' => 'required|string|unique:paper_sizes,code',
            'status' => 'required|in:active,inactive',
        ]);

        $paperSize = PaperSize::create($validated);

        return response()->json($paperSize, 201);
    }

    /**
     * Update a paper size.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id): JsonResponse
    {
        $validated = request()->validate([
            'name' => 'sometimes|string|unique:paper_sizes,name,' . $id,
            'code' => 'sometimes|string|unique:paper_sizes,code,' . $id,
            'status' => 'sometimes|in:active,inactive',
        ]);

        $paperSize = PaperSize::findOrFail($id);
        $paperSize->update($validated);

        return response()->json([
            'message' => 'Paper size updated successfully',
            'data' => $paperSize,
        ]);
    }
}