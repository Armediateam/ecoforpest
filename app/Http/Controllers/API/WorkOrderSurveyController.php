<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\SurveyForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkOrderSurveyController extends Controller
{
    public function getFormTemplate($workOrderId = null, $type, Request $request)
    {
        $serviceId = null;
        if ($workOrderId) {
            $workOrder = WorkOrder::where('id', $workOrderId)->first();
            $serviceId = $workOrder?->service_id;
        }
        $form = SurveyForm::where('type', $type)
            ->where('is_active', true)
            ->when($serviceId, function ($q) use ($serviceId) {
                $q->where('service_id', $serviceId);
            })
            ->latest()
            ->first();

        if (!$form) {
            return response()->json(['message' => 'No form template found'], 404);
        }

        $fields = collect($form->fields)->map(function ($field) {
            if (isset($field['options']) && is_array($field['options'])) {
                $isAssoc = array_keys($field['options']) !== range(0, count($field['options']) - 1);
                if (!$isAssoc) {
                    $field['options'] = collect($field['options'])
                        ->mapWithKeys(function ($item) {
                            if (is_array($item) && isset($item['key']) && isset($item['label'])) {
                                return [$item['key'] => $item['label']];
                            }
                            $key = strtolower(preg_replace('/[^a-z0-9]+/', '_', $item));
                            return [$key => $item];
                        })
                        ->toArray();
                }
            }
            return $field;
        })->toArray();

        $form->fields = $fields;

        return response()->json($form);
    }

    public function submitSurvey(Request $request, $workOrderId)
    {
        $workOrder = WorkOrder::where('id', $workOrderId)->first();
        if ($workOrder->assigned_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'survey_form_id' => 'required|exists:survey_forms,id',
            'answers' => 'required|array'
        ]);

        $form = SurveyForm::where('id', $validated['survey_form_id'])->first();
        $answers = $validated['answers'];

        foreach ($answers as $key => $value) {
            $files = $request->file("answers.$key");

            if (is_array($files)) {
                $paths = [];
                foreach ($files as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                        $paths[] = $file->store('survey_answers', 'public');
                    }
                }
                $answers[$key] = $paths;
            } elseif ($files instanceof \Illuminate\Http\UploadedFile && $files->isValid()) {
                $answers[$key] = $files->store('survey_answers', 'public');
            } else {
                $answers[$key] = $value;
            }
        }

        $existingSurvey = $workOrder->surveys()
            ->where('survey_form_id', $validated['survey_form_id'])
            ->first();

        if ($existingSurvey) {
            $existingSurvey->update([
                'answers' => $answers,
                'filled_by' => Auth::id()
            ]);
            $survey = $existingSurvey;
        } else {
            $survey = $workOrder->surveys()->create([
                'survey_form_id' => $validated['survey_form_id'],
                'answers' => $answers,
                'filled_by' => Auth::id()
            ]);
        }

        if (in_array($form->type, [SurveyForm::TYPE_IDENTIFICATION, SurveyForm::TYPE_INITIAL_CHECK])) {
            $workOrder->update(['status' => WorkOrder::STATUS_ON_PROGRESS]);
        } elseif ($form->type === SurveyForm::TYPE_FINAL_CHECK) {
            $workOrder->update(['status' => WorkOrder::STATUS_CLOSED]);
        }

        return response()->json($survey->load(['surveyForm', 'filledBy']));
    }

    public function getSurvey($workOrderId, $type)
    {
        $userId = Auth::id();
        $workOrder = WorkOrder::where('id', $workOrderId)->with('helpers')->first();
        $isAssigned = $workOrder->assigned_id === $userId;
        $isHelper = $workOrder->helpers->contains('id', $userId);
        if (!$isAssigned && !$isHelper) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $survey = $workOrder->surveys()
            ->whereHas('surveyForm', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->with(['surveyForm', 'filledBy'])
            ->latest()
            ->first();

        if (!$survey) {
            return response()->json(['message' => 'Survey not found'], 404);
        }

        return response()->json($survey);
    }
}
