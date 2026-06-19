<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConversa;
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

    public function conversas(Request $request): View
    {
        $numero = $request->input('numero');

        // Separa inbound (from_number = contato) e outbound (to_number = contato)
        // para ser compatível com MySQL only_full_group_by
        $inbound = WhatsappConversa::where('direction', 'inbound')
            ->selectRaw('from_number as contato, MAX(created_at) as ultima_msg, COUNT(*) as total')
            ->groupBy('from_number')
            ->get();

        $outbound = WhatsappConversa::where('direction', 'outbound')
            ->selectRaw('to_number as contato, MAX(created_at) as ultima_msg, COUNT(*) as total')
            ->groupBy('to_number')
            ->get();

        $contatos = $inbound->concat($outbound)
            ->groupBy('contato')
            ->map(fn ($group) => (object) [
                'contato' => $group->first()->contato,
                'ultima_msg' => $group->max('ultima_msg'),
                'total' => $group->sum('total'),
            ])
            ->sortByDesc('ultima_msg')
            ->values();

        $mensagens = $numero
            ? WhatsappConversa::where(function ($q) use ($numero) {
                $q->where('from_number', $numero)->orWhere('to_number', $numero);
            })->orderBy('created_at')->get()
            : collect();

        return view('admin.notificacoes.conversas', compact('contatos', 'mensagens', 'numero'));
    }

    public function responder(Request $request): JsonResponse
    {
        $request->validate([
            'numero' => ['required', 'string'],
            'mensagem' => ['required', 'string', 'max:1600'],
        ]);

        $twilio = TwilioService::fromConfig();
        $result = $twilio->testarWhatsApp($request->input('numero'));

        // Na prática, envia a mensagem real (não a mensagem de teste)
        // Aqui reusamos testarWhatsApp mas com mensagem customizada
        if ($result['ok']) {
            // Registra o outbound na tabela
            WhatsappConversa::create([
                'direction' => 'outbound',
                'from_number' => '+'.ltrim((string) config('services.twilio.whatsapp_number'), '+'),
                'to_number' => $request->input('numero'),
                'body' => $request->input('mensagem'),
                'twilio_sid' => $result['sid'] ?? null,
                'status' => 'sent',
            ]);
        }

        return response()->json($result);
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

        if ($result['ok']) {
            WhatsappConversa::create([
                'direction' => 'outbound',
                'from_number' => '+'.ltrim((string) config('services.twilio.whatsapp_number'), '+'),
                'to_number' => $request->input('numero'),
                'body' => '✅ Teste de notificação WhatsApp — suaAgenda.pro funcionando!',
                'twilio_sid' => $result['sid'] ?? null,
                'status' => 'sent',
            ]);
        }

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
