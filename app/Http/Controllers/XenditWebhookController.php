<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\XenditPaymentService;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    protected $xenditService;

    public function __construct(XenditPaymentService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    public function handle(Request $request)
    {
        // Verify webhook signature
        $callbackToken = $request->header('X-CALLBACK-TOKEN');
        if ($callbackToken !== config('services.xendit.callback_token')) {
            Log::error('Invalid Xendit callback token');
            return response()->json(['error' => 'Invalid callback token'], 401);
        }

        try {
            // Handle the webhook payload
            $this->xenditService->handleCallback($request->all());
            
            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (\Exception $e) {
            Log::error('Xendit webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
