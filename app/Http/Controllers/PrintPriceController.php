<?php

namespace App\Http\Controllers;

use App\Models\PrintPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintPriceController extends Controller
{
    /**
     * List all prices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $prices = PrintPrice::with(['paperSize', 'printMode'])->get();
        return response()->json($prices);
    }

    /**
     * Add or update a price for a specific paper size and print mode.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paper_size_id' => 'required|exists:paper_sizes,id',
            'print_mode_id' => 'required|exists:print_modes,id',
            'price' => 'required|numeric|min:0',
        ]);

        $price = PrintPrice::updateOrCreate(
            [
                'paper_size_id' => $validated['paper_size_id'],
                'print_mode_id' => $validated['print_mode_id']
            ],
            ['price' => $validated['price']]
        );

        return response()->json([
            'message' => 'Price saved successfully',
            'data' => $price
        ]);
    }

    /**
     * Delete a price entry.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $price = PrintPrice::findOrFail($id);
        $price->delete();

        return response()->json(['message' => 'Price deleted successfully']);
    }
}