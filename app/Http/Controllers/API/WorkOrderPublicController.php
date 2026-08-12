<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;

class WorkOrderPublicController extends Controller
{

    public function detailWorkPage($id)
    {
        $workOrder = WorkOrder::where('id', $id)->first();
        return view('public.workorder-detail-only', compact('workOrder'));
    }
}
