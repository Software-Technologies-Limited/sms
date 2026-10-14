<?php

namespace StlHorizon\Sms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use StlHorizon\Sms\Models\SmsLog;

class SmsWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        if (config('sms.logging.enabled') && Schema::hasTable(config('sms.logging.table', 'sms_logs'))) {
            SmsLog::query()->create([
                'driver' => 'africastalking',
                'recipient' => $request->input('from') ?? $request->input('phone') ?? 'unknown',
                'message' => $request->input('message') ?? 'Delivery report',
                'success' => true,
                'status' => (string) ($request->input('status') ?? 'webhook'),
                'response' => $request->all(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
