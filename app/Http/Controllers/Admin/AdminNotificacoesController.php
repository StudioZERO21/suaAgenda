<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwilioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminNotificacoesController extends Controller
{
    public function index(): View
    {
        $twilio = TwilioService::fromConfig();

        return view('admin.notificacoes.index', [
            'twilioConfigured' => $twilio->isConfigured(),
            'twilioWhatsapp' => $twilio->whatsappConfigured(),
            'twilioSms' => $twilio->smsConfigured(),
            'twilioSid' => substr((string) config('services.twilio.sid', ''), 0, 8),
            'twilioWhatsappNumber' => (string) config('services.twilio.whatsapp_number', ''),
            'twilioSmsNumber' => (string) config('services.twilio.sms_number', ''),
        ]);
    }

    public function testarConexao(): JsonResponse
    {
        $result = TwilioService::fromConfig()->testarConexao();

        return response()->json($result);
    }

    public function testarWhatsApp(Request $request): JsonResponse
    {
        $request->validate([
            'numero' => ['required', 'string', 'min:8', 'max:20'],
        ]);

        $result = TwilioService::fromConfig()->testarWhatsApp($request->input('numero'));

        return response()->json($result);
    }

    public function testarSms(Request $request): JsonResponse
    {
        $request->validate([
            'numero' => ['required', 'string', 'min:8', 'max:20'],
        ]);

        $result = TwilioService::fromConfig()->testarSms($request->input('numero'));

        return response()->json($result);
    }
}
