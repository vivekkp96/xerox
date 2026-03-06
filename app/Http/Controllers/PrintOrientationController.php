<?php

namespace App\Http\Controllers;

use App\Models\PrintOrientation;
use Illuminate\Http\JsonResponse;

class PrintOrientationController extends Controller
{
    public function index(): JsonResponse
    {
        $orientations = PrintOrientation::getOrientations();
        return response()->json($orientations);
    }
}
