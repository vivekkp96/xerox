<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class LaminationController extends Controller
{
    public function getLaminationAmount()
    {
        $setting = Setting::where('name', 'lamination_amount')->first();
        return response()->json([
            'value' => $setting ? $setting->value : null
        ]);
    }

    public function updateLaminationAmount(Request $request)
    {
        $request->validate([
            'value' => 'required|numeric',
        ]);

        $setting = Setting::updateOrCreate(
            ['name' => 'lamination_amount'],
            ['value' => $request->value]
        );

        return response()->json([
            'message' => 'Lamination amount updated successfully',
            'data' => $setting
        ]);
    }

    public function deleteLaminationAmount()
    {
        Setting::where('name', 'lamination_amount')->update(['value' => null]);
        return response()->json(['message' => 'Lamination amount deleted successfully']);
    }
}