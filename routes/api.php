<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\XenditWebhookController;


// Xendit Webhook
Route::post('xendit/webhook', [XenditWebhookController::class, 'handle'])->name('xendit.webhook');

Route::name('api.')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user', [AuthController::class, 'user'])->name('user');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        // Attendance routes
        Route::prefix('attendance')->group(function () {
            Route::get('today', [\App\Http\Controllers\API\AttendanceController::class, 'today']);
            Route::get('history', [\App\Http\Controllers\API\AttendanceController::class, 'history']);
            Route::get('summary', [\App\Http\Controllers\API\AttendanceController::class, 'summary']);
            Route::post('/', [\App\Http\Controllers\API\AttendanceController::class, 'store']);
            Route::post('request-access', [\App\Http\Controllers\API\AttendanceController::class, 'request_access']);
        });

        // Leave routes
        Route::prefix('leaves')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\LeaveController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\API\LeaveController::class, 'store']);
            Route::get('/{leave}', [\App\Http\Controllers\API\LeaveController::class, 'show']);
            Route::put('/{leave}', [\App\Http\Controllers\API\LeaveController::class, 'update']);
            Route::delete('/{leave}', [\App\Http\Controllers\API\LeaveController::class, 'destroy']);
        });

        // Permit routes
        Route::prefix('permits')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\PermitController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\API\PermitController::class, 'store']);
            Route::get('/{permit}', [\App\Http\Controllers\API\PermitController::class, 'show']);
            Route::put('/{permit}', [\App\Http\Controllers\API\PermitController::class, 'update']);
            Route::delete('/{permit}', [\App\Http\Controllers\API\PermitController::class, 'destroy']);
        });

        // Overtime routes
        Route::prefix('overtimes')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\OvertimeController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\API\OvertimeController::class, 'store']);
            Route::get('/{overtime}', [\App\Http\Controllers\API\OvertimeController::class, 'show']);
            Route::put('/{overtime}', [\App\Http\Controllers\API\OvertimeController::class, 'update']);
            Route::delete('/{overtime}', [\App\Http\Controllers\API\OvertimeController::class, 'destroy']);
        });

        // Work Order routes
        Route::prefix('work-orders')->group(function () {
            Route::get('/by-month', [\App\Http\Controllers\API\WorkOrderController::class, 'byMonth']);
            Route::get('/', [\App\Http\Controllers\API\WorkOrderController::class, 'index']);
            Route::get('/{workOrder}', [\App\Http\Controllers\API\WorkOrderController::class, 'show']);
            Route::patch('/{workOrder}/status', [\App\Http\Controllers\API\WorkOrderController::class, 'updateStatus']);
            Route::post('/{workOrder}/progress', [\App\Http\Controllers\API\WorkOrderController::class, 'addProgress']);
            Route::get('/{workOrder}/progress', [\App\Http\Controllers\API\WorkOrderController::class, 'getProgress']);
            Route::get('/{workOrder}/progress/latest', [\App\Http\Controllers\API\WorkOrderController::class, 'getLatestProgress']);
            Route::post('/{workOrder}/accept', [\App\Http\Controllers\API\WorkOrderController::class, 'accept']);
            Route::get('/{workOrder}/check-progress', [\App\Http\Controllers\API\WorkOrderController::class, 'checkProgress']);
            Route::get('/{workOrder}/report-signature', [\App\Http\Controllers\API\WorkOrderController::class, 'reportSignature']);

            // Survey routes
            Route::get('{workOrder}/forms/{type}', [\App\Http\Controllers\API\WorkOrderSurveyController::class, 'getFormTemplate']);
            Route::post('{workOrder}/surveys', [\App\Http\Controllers\API\WorkOrderSurveyController::class, 'submitSurvey']);
            Route::get('{workOrder}/surveys/{type}', [\App\Http\Controllers\API\WorkOrderSurveyController::class, 'getSurvey']);

            Route::get('/{workOrder}/nearby-workers', [\App\Http\Controllers\API\WorkOrderController::class, 'listNearbyWorkers']);
            Route::post('/{workOrder}/reassign', [\App\Http\Controllers\API\WorkOrderController::class, 'reassignWorker']);
            Route::post('/{workOrder}/service-report', [\App\Http\Controllers\API\ServiceReportController::class, 'store']);
            Route::get('/{workOrder}/service-report', [\App\Http\Controllers\API\ServiceReportController::class, 'show']);
            Route::get('/work-orders/{workOrder}/service-report/pdf', [\App\Http\Controllers\API\ServiceReportController::class, 'exportPdf']);
            Route::delete('/{workOrder}/service-report', [\App\Http\Controllers\API\ServiceReportController::class, 'destroy']);
        });

        Route::prefix('cash-advance')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\CashAdvanceController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\API\CashAdvanceController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\API\CashAdvanceController::class, 'store']);
            Route::post('/{id}', [\App\Http\Controllers\API\CashAdvanceController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\API\CashAdvanceController::class, 'destroy']);
        });
    });

    // Public Survey Routes (no auth)
    Route::prefix('public-survey')->group(function () {
        Route::get('/form/{type}', [\App\Http\Controllers\API\WorkOrderSurveyController::class, 'getFormTemplate']);
        Route::post('/work-orders/{workOrder}/submit', [\App\Http\Controllers\API\WorkOrderSurveyController::class, 'submitSurvey']);
        Route::get('/work-orders/{workOrder}/survey/{type}', [\App\Http\Controllers\API\WorkOrderSurveyController::class, 'getSurvey']);
    });

    // Public Work Order Only Detail Work (no auth)
    Route::get('/public/work-order/{id}/detail-work/page', [\App\Http\Controllers\API\WorkOrderPublicController::class, 'detailWorkPage']);

    Route::middleware('auth:sanctum')->post('/location/update', [\App\Http\Controllers\API\LocationController::class, 'update']);

    // Leader Report routes
    Route::prefix('leader-reports')->middleware('auth:sanctum')->group(function () {
        Route::get('/options', [\App\Http\Controllers\API\LeaderReportController::class, 'options']);
        Route::get('/get-employees', [\App\Http\Controllers\API\LeaderReportController::class, 'getEmployees']);
        Route::get('/', [\App\Http\Controllers\API\LeaderReportController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\API\LeaderReportController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\API\LeaderReportController::class, 'show']);
        Route::post('/{id}', [\App\Http\Controllers\API\LeaderReportController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\API\LeaderReportController::class, 'destroy']);
        Route::post('/{id}/approve', [\App\Http\Controllers\API\LeaderReportController::class, 'approve']);
        Route::post('/{id}/reject', [\App\Http\Controllers\API\LeaderReportController::class, 'reject']);
        Route::post('/{id}/revoke-approval', [\App\Http\Controllers\API\LeaderReportController::class, 'revokeApproval']);
        Route::post('/{id}/approve', [\App\Http\Controllers\API\LeaderReportController::class, 'approve']);
        Route::post('/{id}/revoke-approval', [\App\Http\Controllers\API\LeaderReportController::class, 'revokeApproval']);
    });

    // Schedule Planning routes
    Route::prefix('schedule-plannings')->middleware('auth:sanctum')->group(function () {
        Route::get('/options', [\App\Http\Controllers\API\SchedulePlanningController::class, 'options']);
        Route::get('/get-employees', [\App\Http\Controllers\API\SchedulePlanningController::class, 'getEmployees']);
        Route::get('/', [\App\Http\Controllers\API\SchedulePlanningController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\API\SchedulePlanningController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\API\SchedulePlanningController::class, 'show']);
        Route::post('/{id}', [\App\Http\Controllers\API\SchedulePlanningController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\API\SchedulePlanningController::class, 'destroy']);
    });
});
Route::get('/work-orders/{workOrder}/service-report/pdf', [\App\Http\Controllers\ServiceReportController::class, 'previewPdf'])
    ->name('service-reports.preview');
Route::get('/work-orders/{id}/service-report-customer/pdf', [\App\Http\Controllers\ServiceReportController::class, 'previewPdfCustomer'])
    ->name('service-reports-customer.preview');
