<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\NotificationUsage;
use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

final class AdminGastosController extends Controller
{
    public function index(): View
    {
        $ano = (int) now()->format('Y');
        $mes = (int) now()->format('n');

        // Uso por empresa no mês
        $uso = NotificationUsage::where('ano', $ano)
            ->where('mes', $mes)
            ->get()
            ->groupBy('company_id')
            ->map(fn ($items) => $items->pluck('total', 'canal')->toArray());

        $empresas = Company::whereIn('id', $uso->keys())
            ->orWhere(fn ($q) => $q->where('ativo', true))
            ->get()
            ->keyBy('id');

        // Custo estimado Twilio por canal
        $custos = config('notification_limits.custo_por_mensagem', []);

        // Custo Evolution (fixo mensal configurado)
        $evolutionCusto = (float) (PlatformSetting::get('evolution', 'server_monthly_cost') ?? 0);

        // Totais globais do mês
        $totais = [
            'whatsapp' => NotificationUsage::where('ano', $ano)->where('mes', $mes)->where('canal', 'whatsapp')->sum('total'),
            'sms' => NotificationUsage::where('ano', $ano)->where('mes', $mes)->where('canal', 'sms')->sum('total'),
            'email' => NotificationUsage::where('ano', $ano)->where('mes', $mes)->where('canal', 'email')->sum('total'),
        ];

        $custoTwilio = round(
            ($totais['whatsapp'] * ($custos['twilio_whatsapp'] ?? 0.005))
            + ($totais['sms'] * ($custos['twilio_sms'] ?? 0.0079)),
            2
        );

        // Histórico dos últimos 6 meses (para gráfico)
        $historico = $this->historico6Meses();

        // Empresas com alerta (>= 80% do limite)
        $alertas = $this->empresasEmAlerta($empresas);

        return view('admin.gastos.index', compact(
            'uso', 'empresas', 'totais', 'custoTwilio',
            'evolutionCusto', 'historico', 'alertas', 'ano', 'mes'
        ));
    }

    public function custosTwilioApi(): JsonResponse
    {
        $cached = Cache::remember('twilio_usage_api', 3600, function () {
            $sid = PlatformSetting::get('twilio', 'sid') ?? config('services.twilio.sid', '');
            $token = PlatformSetting::get('twilio', 'token') ?? config('services.twilio.token', '');

            if (! $sid || ! $token) {
                return null;
            }

            try {
                $resp = Http::timeout(10)
                    ->withBasicAuth($sid, $token)
                    ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Usage/Records/ThisMonth.json", [
                        'Category' => ['sms', 'whatsapp-outbound-messages'],
                    ]);

                if ($resp->successful()) {
                    return $resp->json('usage_records');
                }
            } catch (\Throwable) {
            }

            return null;
        });

        return response()->json(['records' => $cached]);
    }

    /** @return array<int, array{label: string, whatsapp: int, sms: int, email: int}> */
    private function historico6Meses(): array
    {
        $meses = [];
        $result = [];

        for ($i = 5; $i >= 0; $i--) {
            $dt = now()->subMonths($i);
            $meses[] = ['ano' => (int) $dt->format('Y'), 'mes' => (int) $dt->format('n'), 'label' => $dt->translatedFormat('M/y')];
        }

        $registros = NotificationUsage::where(function ($q) use ($meses): void {
            foreach ($meses as $m) {
                $q->orWhere(fn ($q2) => $q2->where('ano', $m['ano'])->where('mes', $m['mes']));
            }
        })->get();

        foreach ($meses as $m) {
            $sub = $registros->where('ano', $m['ano'])->where('mes', $m['mes']);
            $result[] = [
                'label' => $m['label'],
                'whatsapp' => (int) $sub->where('canal', 'whatsapp')->sum('total'),
                'sms' => (int) $sub->where('canal', 'sms')->sum('total'),
                'email' => (int) $sub->where('canal', 'email')->sum('total'),
            ];
        }

        return $result;
    }

    /** @return array<int, array{company: Company, canal: string, percentual: float}> */
    private function empresasEmAlerta(Collection $empresas): array
    {
        $alertas = [];
        $alerta_pct = (int) config('notification_limits.alerta_percentual', 80);
        $ano = (int) now()->format('Y');
        $mes = (int) now()->format('n');

        foreach ($empresas as $company) {
            foreach (['whatsapp', 'sms', 'email'] as $canal) {
                $usado = NotificationUsage::totalMes($company->id, $canal, $ano, $mes);
                $plano = $company->plano ?? 'default';
                $limite = $company->{"notif_limit_{$canal}"} ?? (config("notification_limits.planos.{$plano}.{$canal}") ?? 100);

                if ($limite > 0 && round(($usado / $limite) * 100) >= $alerta_pct) {
                    $alertas[] = [
                        'company' => $company,
                        'canal' => $canal,
                        'usado' => $usado,
                        'limite' => $limite,
                        'percentual' => round(($usado / $limite) * 100, 1),
                    ];
                }
            }
        }

        usort($alertas, fn ($a, $b) => $b['percentual'] <=> $a['percentual']);

        return $alertas;
    }
}
