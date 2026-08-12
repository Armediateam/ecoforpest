<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SchedulePlanning;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SchedulePlanningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $schedules = SchedulePlanning::with(['leaders', 'teknisi'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $schedules
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'address' => 'required|string',
            'location_maps_url' => 'required|url',
            'leader_ids' => 'required|array',
            'leader_ids.*' => 'required|exists:employees,id',
            'teknisi_ids' => 'required|array',
            'teknisi_ids.*' => 'required|exists:employees,id',
            'treatment_start_date' => 'required|date',
            'schedule_days' => 'required|array',
            'schedule_days.*' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'visit_hours' => 'required|string',
            'night_treatment' => 'nullable|string',
            'target_pests' => 'required|array',
            'target_pests.*' => 'required|string',
            'visit_frequency' => 'required|string',
            'week_one_treatments' => 'required|array',
            'week_two_treatments' => 'required|array',
            'week_three_treatments' => 'required|array',
            'week_four_treatments' => 'required|array',
            'leader_notes' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule = SchedulePlanning::create($request->except(['leader_ids', 'teknisi_ids']));
        
        // Sync leaders and teknisi
        $schedule->leaders()->sync($request->leader_ids);
        $schedule->teknisi()->sync($request->teknisi_ids);

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule planning created successfully',
            'data' => $schedule
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $schedule = SchedulePlanning::with(['leaders', 'teknisi'])->find($id);

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schedule planning not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $schedule
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $schedule = SchedulePlanning::find($id);

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schedule planning not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'client_name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'location_maps_url' => 'sometimes|required|url',
            'leader_ids' => 'sometimes|required|array',
            'leader_ids.*' => 'required|exists:employees,id',
            'teknisi_ids' => 'sometimes|required|array',
            'teknisi_ids.*' => 'required|exists:employees,id',
            'treatment_start_date' => 'sometimes|required|date',
            'schedule_days' => 'sometimes|required|array',
            'schedule_days.*' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'visit_hours' => 'sometimes|required|string',
            'night_treatment' => 'nullable|string',
            'target_pests' => 'sometimes|required|array',
            'target_pests.*' => 'required|string',
            'visit_frequency' => 'sometimes|required|string',
            'week_one_treatments' => 'sometimes|required|array',
            'week_two_treatments' => 'sometimes|required|array',
            'week_three_treatments' => 'sometimes|required|array',
            'week_four_treatments' => 'sometimes|required|array',
            'leader_notes' => 'sometimes|required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule->update($request->except(['leader_ids', 'teknisi_ids']));

        // Sync leaders and teknisi if provided
        if ($request->has('leader_ids')) {
            $schedule->leaders()->sync($request->leader_ids);
        }
        if ($request->has('teknisi_ids')) {
            $schedule->teknisi()->sync($request->teknisi_ids);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule planning updated successfully',
            'data' => $schedule
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $schedule = SchedulePlanning::find($id);

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schedule planning not found'
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule planning deleted successfully'
        ]);
    }

    /**
     * Get options for schedule planning form
     */
    public function options(): JsonResponse
    {
        $leaders = Employee::where('position', 'leader')->get(['id', 'name']);
        $teknisi = Employee::where('position', 'teknisi')->get(['id', 'name']);
        
        $scheduleDays = [
            'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'
        ];
        
        $visitFrequency = [
            'Mingguan',
            'Dua Mingguan',
            'Bulanan',
            'Dua Bulanan',
            'Tiga Bulanan',
            'Enam Bulanan'
        ];

        $targetPests = [
            'Tikus',
            'Kecoa',
            'Lalat',
            'Semut',
            'Nyamuk',
            'Kutu',
            'Cicak',
            'Rayap',
            'Kumbang',
            'Laba-laba'
        ];

        $treatments = [
            'Spraying',
            'Fumigasi',
            'Gel Baiting',
            'Misting',
            'Fogging',
            'Rat Baiting',
            'Trapping',
            'ULV',
            'Termite Control'
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'leaders' => $leaders,
                'teknisi' => $teknisi,
                'schedule_days' => $scheduleDays,
                'visit_frequency' => $visitFrequency,
                'target_pests' => $targetPests,
                'treatments' => $treatments
            ]
        ]);
    }
    public function getEmployees(Request $request): JsonResponse
    {
        $query = Employee::query()
            ->with('position'); // Eager load position relationship

        if ($request->has('type')) {
            if ($request->input('type') === 'leader') {
                $query->whereHas('position', function ($q) {
                    $q->where('is_leader', true);
                });
            } elseif ($request->input('type') === 'teknisi') {
                $query->whereHas('position', function ($q) {
                    $q->where('is_leader', false);
                });
            }
        }

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where('name', 'ilike', '%' . $searchTerm . '%');
        }

        $employees = $query->select('id', 'name', 'position_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Employees retrieved successfully',
            'data' => $employees
        ]);
    }

}
