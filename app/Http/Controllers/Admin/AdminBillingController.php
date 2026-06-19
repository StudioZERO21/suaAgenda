<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingConfig;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Billing\BillingGatewayService;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminBillingController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', '');
        $busca = trim((string) $request->query('q', ''));

        $subscriptions = Subscription::with(['company', 'plan'])
            ->withCount('invoices')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($busca !== '', fn ($q) => $q->whereHas('company', function ($c) use ($busca): void {
                $c->where('name', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])->count(),
            'grace' => Subscription::where('status', Subscription::STATUS_GRACE)->count(),
            'suspended' => Subscription::where('status', Subscription::STATUS_SUSPENDED)->count(),
            'cancelled' => Subscription::where('status', Subscription::STATUS_CANCELLED)->count(),
            'mrr' => (float) Subscription::where('status', Subscription::STATUS_ACTIVE)->sum('monthly_amount'),
            'overdue_invoices' => Invoice::where('status', Invoice::STATUS_OVERDUE)->count(),
            'pending_invoices' => Invoice::where('status', Invoice::STATUS_PENDING)->count(),
            'receita_mes' => (float) Invoice::where('status', Invoice::STATUS_PAID)
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
        ];

        $billingConfig = BillingConfig::current();

        return view('admin.billing.index', compact('subscriptions', 'stats', 'status', 'busca', 'billingConfig'));
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['company', 'plan']);

        $invoices = Invoice::where('subscription_id', $subscription->id)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.billing.show', compact('subscription', 'invoices'));
    }

    public function gerarFatura(Subscription $subscription): RedirectResponse
    {
        $config = BillingConfig::current();
        $service = new InvoiceService(new BillingGatewayService($config));
        $service->generate($subscription);

        return redirect()
            ->route('admin.billing.show', $subscription)
            ->with('success', 'Fatura gerada e enviada ao gateway!');
    }

    public function suspender(Subscription $subscription): RedirectResponse
    {
        $subscription->update([
            'status' => Subscription::STATUS_SUSPENDED,
            'suspended_at' => now(),
        ]);

        return redirect()
            ->route('admin.billing.show', $subscription)
            ->with('success', 'Assinatura suspensa manualmente.');
    }

    public function reativar(Subscription $subscription): RedirectResponse
    {
        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'suspended_at' => null,
            'cancelled_at' => null,
        ]);

        return redirect()
            ->route('admin.billing.show', $subscription)
            ->with('success', 'Assinatura reativada!');
    }

    public function cancelar(Subscription $subscription): RedirectResponse
    {
        $subscription->update([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        Invoice::where('subscription_id', $subscription->id)
            ->where('status', Invoice::STATUS_PENDING)
            ->update(['status' => Invoice::STATUS_CANCELLED]);

        return redirect()
            ->route('admin.billing.show', $subscription)
            ->with('success', 'Assinatura cancelada.');
    }

    public function marcarPaga(Invoice $invoice): RedirectResponse
    {
        $config = BillingConfig::current();
        $service = new InvoiceService(new BillingGatewayService($config));
        $service->markPaid($invoice, 'manual');

        return redirect()
            ->route('admin.billing.show', $invoice->subscription)
            ->with('success', "Fatura {$invoice->number} marcada como paga.");
    }

    public function configGateway(): View
    {
        $billingConfig = BillingConfig::current();

        return view('admin.billing.gateway', compact('billingConfig'));
    }

    public function saveConfigGateway(Request $request): RedirectResponse
    {
        $config = BillingConfig::current();

        $creds = $config->credentials ?? [];
        $creds['asaas_api_key'] = trim((string) $request->input('asaas_api_key', $creds['asaas_api_key'] ?? ''));
        $creds['asaas_ambiente'] = $request->input('asaas_ambiente', 'sandbox');
        $creds['webhook_token'] = trim((string) $request->input('webhook_token', $creds['webhook_token'] ?? ''));

        $config->update([
            'gateway' => $request->input('gateway', 'asaas'),
            'credentials' => $creds,
            'grace_warning_days' => (int) $request->input('grace_warning_days', 3),
            'grace_suspend_days' => (int) $request->input('grace_suspend_days', 7),
            'grace_cancel_days' => (int) $request->input('grace_cancel_days', 30),
            'notification_channel_billing' => $request->input('notification_channel_billing', 'email'),
            'notification_channel_cancel' => $request->input('notification_channel_cancel', 'whatsapp'),
        ]);

        return redirect()
            ->route('admin.billing.gateway')
            ->with('success', 'Configurações de cobrança salvas!');
    }

    public function testGateway(): JsonResponse
    {
        $result = BillingGatewayService::fromConfig()->testar();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Alterna o ambiente do Mercado Pago entre sandbox e produção.
     */
    public function toggleMpAmbiente(Request $request): RedirectResponse
    {
        $novo = $request->input('mp_ambiente', 'sandbox');

        if (! in_array($novo, ['sandbox', 'producao'])) {
            return back()->with('error', 'Ambiente inválido.');
        }

        $config = BillingConfig::current();
        $creds = $config->credentials ?? [];
        $creds['mp_ambiente'] = $novo;
        $config->update(['credentials' => $creds]);

        Cache::forget('mp_ambiente');

        $label = $novo === 'sandbox' ? 'Sandbox (testes)' : 'Produção';

        return redirect()
            ->route('billing.gateway')
            ->with('success', "Mercado Pago alterado para: {$label}");
    }
}
