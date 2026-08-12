<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\UpdateEmployeeLocationJob;

class LocationController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'info' => 'nullable|array',
        ]);
        $employeeId = Auth::id();
        // Dispatch job ke queue
        UpdateEmployeeLocationJob::dispatch([
            'employee_id' => $employeeId,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'info' => $validated['info'] ?? null,
        ]);
        return response()->json(['message' => 'Location update queued.'], 202);
    }
}
