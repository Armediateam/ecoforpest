<?php

namespace App\Http\Controllers;

use App\Exports\WorkOrdersExport;
use App\Filament\Resources\WorkOrderResource;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WorkOrderExportController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(WorkOrderResource::canViewAny(), 403);

        return Excel::download(
            new WorkOrdersExport($request->input('tableFilters', [])),
            'Data Work Order - ' . now()->format('d-m-Y') . '.xlsx'
        );
    }
}
