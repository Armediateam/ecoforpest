<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLocations;
use App\Models\WorkOrder;
use App\Models\WorkOrderProgress;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = Auth::id();

        $query = WorkOrder::with(['service', 'assigned', 'progress', 'customer', 'lead', 'helpers'])
            ->where(function ($q) use ($employeeId) {
                $q->where('assigned_id', $employeeId)
                    ->orWhereHas('helpers', function ($q2) use ($employeeId) {
                        $q2->where('employees.id', $employeeId);
                    });
            });

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->start_date) {
            $query->whereDate('work_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('work_date', '<=', $request->end_date);
        }
        if ($request->month) {
            $query->whereMonth('work_date', $request->month);
        }
        if ($request->year) {
            $query->whereYear('work_date', $request->year);
        }

        $workOrders = $query->orderBy('work_date', 'desc')->paginate(10);

        return response()->json($workOrders);
    }
    public function show($workOrderId)
    {
        $workOrderModel = WorkOrder::where('id', $workOrderId)->first();
        if (!$workOrderModel) {
            return response()->json(['message' => 'WorkOrder not found'], 404);
        }

        // Only allow assigned worker or admin to view
        $user = Auth::user();
        $isAssigned = $workOrderModel->assigned_id === $user->id;
        $isHelper = $workOrderModel->helpers->contains('id', $user->id);
        if (!$isAssigned && !$isHelper && !$user->can('view', $workOrderModel)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workOrderModel->load(['service', 'assigned', 'helpers', 'progress', 'serviceReport', 'customer', 'lead']);
        return response()->json($workOrderModel);
    }

    public function updateStatus(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(WorkOrder::$statuses)],
        ]);

        DB::beginTransaction();

        try {
            $workOrder->update($validated);

            // Log activity
            if (function_exists('activity')) {
                activity()
                    ->performedOn($workOrder)
                    ->causedBy(Auth::user())
                    ->log('status_changed');
            }

            // Send notification if status changed to Closed and to user role super_admin
            if ($validated['status'] === 'Closed') {
                $adminUsers = \App\Models\User::with('roles')
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'Admin Reguler');
                    })->get();
                foreach ($adminUsers as $admin) {
                    $lead_or_costumer = $workOrder->lead->name ?? $workOrder->customer->name;
                    Notification::make()
                        ->title('Work Order Closed')
                        ->body('Work order from ' . $lead_or_costumer . ' has been closed by ' . Auth::user()->name)
                        ->actions([
                            Action::make('view')
                                ->url(route('filament.secret.work-orders.resources.work-orders.view', $workOrder->id))
                                ->label('View Work Order')
                                ->icon('heroicon-o-eye'),
                        ])
                        ->success()
                        ->sendToDatabase($admin);
                }
            }

            DB::commit();

            return response()->json($workOrder->fresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan saat mengupdate status'], 500);
        }
    }

    public function addProgress(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->assigned_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progressOrder = [
            'Take Order',
            'Ketemu Client',
            'Survey',
            'Mulai Kerja',
            'Tindakan',
            'Selesai Kerja',
            'Collect Money',
        ];

        $validated = $request->validate([
            'progress_status' => ['required', Rule::in($progressOrder)],
            'notes' => 'nullable|string',
            'photos.*' => 'nullable|image|max:5120', // 5MB max per image
            'location' => 'nullable|array',
            'location.latitude' => 'required_with:location|numeric',
            'location.longitude' => 'required_with:location|numeric',
        ]);

        DB::beginTransaction();

        try {
            // Cek urutan progress
            $lastProgress = $workOrder->progress()->orderBy('completed_at', 'desc')->first();
            $lastStatus = $lastProgress ? $lastProgress->progress_status : null;
            $expectedNext = $lastStatus === null ? $progressOrder[0] : ($progressOrder[array_search($lastStatus, $progressOrder) + 1] ?? null);
            if ($validated['progress_status'] !== $expectedNext) {
                return response()->json(['message' => 'Progress harus berurutan. Progress terakhir: ' . ($lastStatus ?? '-') . ', progress berikutnya: ' . ($expectedNext ?? '-')], 422);
            }

            // Tambahan validasi survey wajib
            if ($validated['progress_status'] === 'Survey') {
                $identificationSurvey = $workOrder->surveys()
                    ->whereHas('surveyForm', function ($q) use ($workOrder) {
                        $q->where('type', 'identification')
                            ->where('service_id', $workOrder->service_id);
                    })->exists();
                $initialCheckSurvey = $workOrder->surveys()
                    ->whereHas('surveyForm', function ($q) use ($workOrder) {
                        $q->where('type', 'initial_check')
                            ->where('service_id', $workOrder->service_id);
                    })->exists();
                if (!$identificationSurvey || !$initialCheckSurvey) {
                    return response()->json([
                        'message' => 'Survey Identifikasi dan Pemeriksaan Awal wajib diisi sebelum melanjutkan progress Survey.'
                    ], 422);
                }
            }
            if ($validated['progress_status'] === 'Selesai Kerja') {
                $finalCheckSurvey = $workOrder->surveys()
                    ->whereHas('surveyForm', function ($q) use ($workOrder) {
                        $q->where('type', 'final_check')
                            ->where('service_id', $workOrder->service_id);
                    })->exists();
                if (!$finalCheckSurvey) {
                    return response()->json([
                        'message' => 'Survey Pemeriksaan Akhir wajib diisi sebelum menyelesaikan pekerjaan.'
                    ], 422);
                }
            }

            // Handle photo uploads
            $photos = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('work-order-progress', 'public');
                    $photos[] = $path;
                }
            }

            $progress = $workOrder->progress()->create([
                'progress_status' => $validated['progress_status'],
                'notes' => $validated['notes'] ?? null,
                'photos' => $photos,
                'location' => $validated['location'] ?? null,
                'completed_at' => now(),
                'completed_by' => Auth::id(),
            ]);
            // Update work order status based on progress
            switch ($validated['progress_status']) {
                case 'Selesai Kerja':
                    $workOrder->update(['status' => 'Closed']);
                    break;
                case 'Collect Money':
                    $workOrder->update(['status' => 'Closed']);
                    break;
            }

            // Log activity
            if (function_exists('activity')) {
                activity()
                    ->performedOn($workOrder)
                    ->causedBy(Auth::user())
                    ->withProperties(['progress_status' => $validated['progress_status']])
                    ->log('progress_added');
            }

            DB::commit();

            return response()->json($progress->load('completedBy'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan saat menambahkan progress'], 500);
        }
    }

    public function getProgress($workOrderId)
    {
        $userId = Auth::id();
        $workOrder = WorkOrder::where('id', $workOrderId)->first();
        $isAssigned = $workOrder->assigned_id === $userId;
        $isHelper = $workOrder->helpers->contains('id', $userId);
        if (!$isAssigned && !$isHelper) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progress = $workOrder->progress()
            ->with('completedBy')
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json($progress);
    }

    public function getLatestProgress($workOrderId)
    {
        $userId = Auth::id();
        $workOrder = WorkOrder::where('id', $workOrderId)->first();
        $isAssigned = $workOrder->assigned_id === $userId;
        $isHelper = $workOrder->helpers->contains('id', $userId);
        if (!$isAssigned && !$isHelper) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progress = $workOrder->progress()
            ->with('completedBy')
            ->latest('completed_at')
            ->first();

        return response()->json($progress);
    }
    public function accept(Request $request, WorkOrder $workOrder)
    {
        $userId = Auth::id();
        // Pastikan hanya worker yang di-assign yang bisa accept
        if ($workOrder->assigned_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        // Hanya bisa accept jika status masih Assigned
        if ($workOrder->status !== WorkOrder::STATUS_ASSIGNED) {
            return response()->json(['message' => 'Order cannot be accepted in current status'], 422);
        }

        // Update status ke On Progress
        $workOrder->update(['status' => 'On Progress']);

        // Tambahkan progress otomatis: Take Order
        $progress = $workOrder->progress()->create([
            'progress_status' => 'Take Order',
            'notes' => 'Order diambil oleh worker',
            'photos' => [],
            'location' => null,
            'completed_at' => now(),
            'completed_by' => $userId,
        ]);

        // Log activity
        if (function_exists('activity')) {
            activity()
                ->performedOn($workOrder)
                ->causedBy(Auth::user())
                ->log('accepted');
        }

        // Kirim notifikasi ke admin
        $adminUsers = \App\Models\User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Admin Reguler');
            })->get();
        foreach ($adminUsers as $admin) {
            $lead_or_costumer = $workOrder->lead->name ?? $workOrder->customer->name;
            Notification::make()
                ->title('Work Order Accepted')
                ->body('Work order from ' . $lead_or_costumer . ' has been accepted by ' . Auth::user()->name)
                ->actions([
                    Action::make('view')
                        ->url(route('filament.secret.work-orders.resources.work-orders.view', $workOrder->id))
                        ->label('View Work Order')
                        ->icon('heroicon-o-eye'),
                ])
                ->success()
                ->sendToDatabase($admin);
        }

        return response()->json([
            'work_order' => $workOrder->fresh()->load(['progress.completedBy']),
            'progress' => $progress->load('completedBy'),
        ]);
    }
    public function checkProgress($workOrderId)
    {
        $user = Auth::user();
        $workOrder = WorkOrder::where('id', $workOrderId)->first();
        $isAssigned = $workOrder->assigned_id === $user->id;
        $isHelper = $workOrder->helpers->contains('id', $user->id);
        if (!$isAssigned && !$isHelper && !$user->can('view', $workOrder)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $progressOrder = [
            'Take Order',
            'Ketemu Client',
            'Survey',
            'Mulai Kerja',
            'Tindakan',
            'Selesai Kerja',
            'Collect Money',
        ];
        $progress = $workOrder->progress()->orderBy('completed_at')->get();
        $done = collect($progress)->pluck('progress_status')->toArray();
        $result = [];
        $current = null;
        foreach ($progressOrder as $step) {
            $found = in_array($step, $done);
            $progressData = $found ? $progress->firstWhere('progress_status', $step) : null;
            $survey_status = null;
            if ($step === 'Survey') {
                $identificationSurvey = $workOrder->surveys()
                    ->whereHas('surveyForm', function ($q) use ($workOrder) {
                        $q->where('type', 'identification')
                            ->where('service_id', $workOrder->service_id);
                    })->exists();
                $initialCheckSurvey = $workOrder->surveys()
                    ->whereHas('surveyForm', function ($q) use ($workOrder) {
                        $q->where('type', 'initial_check')
                            ->where('service_id', $workOrder->service_id);
                    })->exists();
                $survey_status = [
                    'identification' => $identificationSurvey,
                    'initial_check' => $initialCheckSurvey,
                ];
            }
            if ($step === 'Selesai Kerja') {
                $finalCheckSurvey = $workOrder->surveys()
                    ->whereHas('surveyForm', function ($q) use ($workOrder) {
                        $q->where('type', 'final_check')
                            ->where('service_id', $workOrder->service_id);
                    })->exists();
                $survey_status = [
                    'final_check' => $finalCheckSurvey,
                ];
            }
            $result[] = [
                'step' => $step,
                'done' => $found,
                'progress_data' => $progressData,
                'survey_status' => $survey_status,
            ];
            if (!$found && !$current) {
                $current = $step;
            }
        }
        $percentage = count($done) > 0 ? round((count($done) / count($progressOrder)) * 100, 2) : 0;
        return response()->json([
            'work_order_id' => $workOrder->id,
            'progress_steps' => $result,
            'current_step' => $current,
            'percentage' => $percentage,
        ]);
    }

    public function listNearbyWorkers(Request $request, WorkOrder $workOrder)
    {
        // $latitude = $workOrder->latitude ?? null;
        // $longitude = $workOrder->longitude ?? null;
        // if ((!$latitude || !$longitude) && isset($workOrder->position[0]['latitude'], $workOrder->position[0]['longitude'])) {
        //     $latitude = $workOrder->position[0]['latitude'];
        //     $longitude = $workOrder->position[0]['longitude'];
        // }
        // if (!$latitude || !$longitude) {
        //     return response()->json(['message' => 'Lokasi work order tidak tersedia'], 422);
        // }

        // $busyWorkerIds = WorkOrder::whereIn('status', ['Assigned', 'On Progress'])
        //     ->pluck('assigned_id')->unique()->toArray();

        // $locations = \App\Models\EmployeeLocations::whereNotIn('employee_id', $busyWorkerIds)
        //     ->whereNotNull('latitude')
        //     ->whereNotNull('longitude')
        //     ->get();

        // $workers = $locations->map(function ($loc) use ($latitude, $longitude) {
        //     $earthRadius = 6371;
        //     $latFrom = deg2rad($latitude);
        //     $lonFrom = deg2rad($longitude);
        //     $latTo = deg2rad($loc->latitude);
        //     $lonTo = deg2rad($loc->longitude);
        //     $latDelta = $latTo - $latFrom;
        //     $lonDelta = $lonTo - $lonFrom;
        //     $a = sin($latDelta / 2) * sin($latDelta / 2) +
        //         cos($latFrom) * cos($latTo) *
        //         sin($lonDelta / 2) * sin($lonDelta / 2);
        //     $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        //     $distance = $earthRadius * $c;
        //     $employee = \App\Models\Employee::find($loc->employee_id);
        //     return [
        //         'id' => $loc->employee_id,
        //         'distance_km' => round($distance, 2),
        //         'latitude' => $loc->latitude,
        //         'longitude' => $loc->longitude,
        //         'updated_at' => $loc->updated_at,
        //         'employee' => $employee // tampilkan seluruh data user/employee
        //     ];
        // })->sortBy('distance_km')->values();

        $latitude = $workOrder->latitude ?? null;
        $longitude = $workOrder->longitude ?? null;
        if ((!$latitude || !$longitude) && isset($workOrder->position[0]['latitude'], $workOrder->position[0]['longitude'])) {
            $latitude = $workOrder->position[0]['latitude'];
            $longitude = $workOrder->position[0]['longitude'];
        }
        if (!$latitude || !$longitude) {
            $latitude = -8.6213397;
            $longitude = 115.1851647;
        }

        // show all worker
        $availableWorkerIds = Employee::whereHas('position', function ($q) {
            $q->where('is_management', false);
            $q->where('is_leader', false);
        })->pluck('id');

        $locations = EmployeeLocations::whereIn('employee_id', $availableWorkerIds)->get();

        $workers = $availableWorkerIds->map(function ($id) use ($locations, $latitude, $longitude) {
            $loc = $locations->firstWhere('employee_id', $id);
            $distance = null;
            if ($loc && $latitude && $longitude) {
                $earthRadius = 6371;
                $latFrom = deg2rad($latitude);
                $lonFrom = deg2rad($longitude);
                $latTo = deg2rad($loc->latitude);
                $lonTo = deg2rad($loc->longitude);
                $latDelta = $latTo - $latFrom;
                $lonDelta = $lonTo - $lonFrom;
                $a = sin($latDelta / 2) * sin($latDelta / 2) +
                    cos($latFrom) * cos($latTo) *
                    sin($lonDelta / 2) * sin($lonDelta / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $distance = round($earthRadius * $c, 2);
            }
            $employee = Employee::find($id);
            return [
                'id' => $id,
                'distance_km' => $distance ?? 0,
                'latitude' => $loc->latitude ?? 0,
                'longitude' => $loc->longitude ?? 0,
                'updated_at' => $loc->updated_at ?? null,
                'employee' => $employee // tampilkan seluruh data user/employee
            ];
        })->sortBy('distance_km')->values();

        return response()->json($workers);
    }

    public function reassignWorker(Request $request, WorkOrder $workOrder)
    {
        // Tidak bisa reassign jika status sudah Closed/Selesai
        if ($workOrder->status === 'Closed') {
            return response()->json(['message' => 'Tugas sudah selesai dan tidak dapat dipindahkan'], 422);
        }
        $validated = $request->validate([
            'worker_id' => 'required|exists:employees,id',
        ]);
        // $busy = WorkOrder::whereIn('status', ['Assigned', 'On Progress'])
        //     ->where('assigned_id', $validated['worker_id'])
        //     ->exists();
        // if ($busy) {
        //     return response()->json(['message' => 'Worker sedang ada pekerjaan aktif'], 422);
        // }
        $workOrder->assigned_id = $validated['worker_id'];
        $workOrder->save();
        // Log activity
        if (function_exists('activity')) {
            activity()
                ->performedOn($workOrder)
                ->causedBy(Auth::user())
                ->withProperties(['assigned_id' => $validated['worker_id']])
                ->log('assigned');
        }

        // Kirim notifikasi ke admin
        $adminUsers = \App\Models\User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Admin Reguler');
            })->get();
        foreach ($adminUsers as $admin) {
            $lead_or_costumer = $workOrder->lead->name ?? $workOrder->customer->name;
            Notification::make()
                ->title('Work Order Reassigned')
                ->body('Work order from ' . $lead_or_costumer . ' has been reassigned to a new worker by ' . Auth::user()->name . '. New worker: ' . $workOrder->assigned?->name)
                ->actions([
                    Action::make('view')
                        ->url(route('filament.secret.work-orders.resources.work-orders.view', $workOrder->id))
                        ->label('View Work Order')
                        ->icon('heroicon-o-eye'),
                ])
                ->warning()
                ->sendToDatabase($admin);
        }

        return response()->json(['message' => 'Tugas berhasil dipindahkan', 'work_order' => $workOrder->fresh()]);
    }
}
