<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmpresaConfiguracaoRequest;
use App\Http\Requests\UpdatePreferenciasConfiguracaoRequest;
use App\Http\Requests\UpdateTipografiaConfiguracaoRequest;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\ImageService;
use App\Support\SaPalettes;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ConfiguracaoController extends Controller
{
    /** @var array<int, string> */
    public const SEGMENTS = [
        'Barbearia', 'Salão de Beleza', 'Clínica Estética', 'Tatuagem',
        'Personal Trainer', 'Nail Designer', 'Cabeleireiro', 'Manicure', 'Outra',
    ];

    /**
     * Tela de preferências do sistema (tema, tipografia, etc.).
     */
    public function show(): View
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('view', $company);

        $settings = $company->resolvedSettings();
        $palettes = SaPalettes::all();
        $activePalette = SaPalettes::get($settings['theme_palette'] ?? 'A');

        return view('configuracoes.index', compact('company', 'settings', 'palettes', 'activePalette'));
    }

    /**
     * Salva preferências visuais e de notificação.
     */
    public function updatePreferencias(UpdatePreferenciasConfiguracaoRequest $request): RedirectResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('update', $company);

        $settings = $company->settings ?? [];
        $defaults = SaPalettes::defaultCompanySettings();

        $settings['theme_palette'] = $request->validated('theme_palette');
        $settings['dark_mode'] = $request->boolean('dark_mode');

        if ($request->has('notifications')) {
            $settings['notifications'] = array_replace_recursive(
                $settings['notifications'] ?? $defaults['notifications'],
                $request->input('notifications', []),
            );
        }

        if ($request->has('security')) {
            $settings['security'] = array_replace_recursive(
                $settings['security'] ?? $defaults['security'],
                $request->input('security', []),
            );
        }

        if ($request->has('contacts')) {
            $settings['contacts'] = array_replace_recursive(
                $settings['contacts'] ?? $defaults['contacts'],
                $request->input('contacts', []),
            );
        }

        $company->settings = $settings;
        $company->save();

        return redirect()
            ->route('configuracoes', ['tab' => $request->input('tab', 'tema')])
            ->with('success', 'Configurações salvas com sucesso!');
    }

    /**
     * Salva apenas as fontes (aba Tipografia).
     */
    public function updateTipografia(UpdateTipografiaConfiguracaoRequest $request): RedirectResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('update', $company);

        $heading = $request->validated('heading_font');
        $body = $request->validated('body_font');

        $settings = $company->settings ?? [];
        $settings['heading_font'] = $heading;
        $settings['body_font'] = $body;

        $company->forceFill(['settings' => $settings])->save();

        return redirect()
            ->route('configuracoes', ['tab' => 'tipografia'])
            ->with('success', "Tipografia salva: {$heading} + {$body}");
    }

    /**
     * Restaura tipografia para o padrão do projeto (Poppins + Inter).
     */
    public function resetTipografia(): RedirectResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('update', $company);

        $settings = $company->settings ?? [];
        unset($settings['heading_font'], $settings['body_font']);

        $company->settings = $settings;
        $company->save();

        return redirect()
            ->route('configuracoes', ['tab' => 'tipografia'])
            ->with('success', 'Tipografia restaurada para o padrão (Poppins + Inter).');
    }

    /**
     * Tela de configurações da empresa (dados, horários, link público).
     */
    public function empresa(): View
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('view', $company);

        $settings = $company->resolvedSettings();
        $segments = self::SEGMENTS;

        return view('configuracoes.empresa', compact('company', 'settings', 'segments'));
    }

    /**
     * Atualiza dados e regras da empresa.
     */
    public function updateEmpresa(UpdateEmpresaConfiguracaoRequest $request): RedirectResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $current = $company->resolvedSettings();

        $hours = $request->input('hours', $current['hours']);
        $advanced = array_replace_recursive($current['advanced'], [
            'min_advance_mins' => (int) $request->input('min_advance_mins', $current['advanced']['min_advance_mins']),
            'max_advance_days' => (int) $request->input('max_advance_days', $current['advanced']['max_advance_days']),
            'confirm_required' => $request->boolean('confirm_required'),
            'auto_reminder' => $request->boolean('auto_reminder'),
            'reminder_hours' => (int) $request->input('reminder_hours', $current['advanced']['reminder_hours']),
            'cancel_policy' => $request->input('cancel_policy', ''),
        ]);

        $company->update([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'segment' => $request->input('segment'),
            'phone' => $request->input('phone'),
            'whatsapp' => $request->input('whatsapp'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'description' => $request->input('description'),
            'instagram' => $request->input('instagram'),
            'facebook' => $request->input('facebook'),
            'tiktok' => $request->input('tiktok'),
            'youtube' => $request->input('youtube'),
            'lgpd_consent' => $request->boolean('lgpd_consent'),
            'settings' => array_replace_recursive($current, [
                'hours' => $hours,
                'advanced' => $advanced,
            ]),
        ]);

        return redirect()
            ->route('configuracoes.empresa', ['tab' => $request->input('tab', 'dados')])
            ->with('success', 'Configurações da empresa salvas com sucesso!');
    }

    public function qrCode(): Response
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('view', $company);

        $bookingUrl = route('vitrine.show', $company->slug);

        $svg = QrCode::format('svg')->size(300)->margin(1)->generate($bookingUrl);

        return response((string) $svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qrcode-'.$company->slug.'.svg"',
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $this->authorize('update', auth()->user()->company);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $company = Company::findOrFail(auth()->user()->empresa_id);

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $path = app(ImageService::class)->store($request->file('logo'), "logos/{$company->id}");
        $company->update(['logo_path' => $path]);

        return response()->json(['logo_url' => Storage::disk('public')->url($path)]);
    }

    public function deleteLogo(): Response
    {
        $this->authorize('update', auth()->user()->company);

        $company = Company::findOrFail(auth()->user()->empresa_id);

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $company->update(['logo_path' => null]);
        }

        return response()->noContent();
    }

    public function empresaStats(): JsonResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('view', $company);

        $empresaId = $company->id;
        $mesInicio = Carbon::today()->startOfMonth();
        $mesFim = Carbon::today()->endOfMonth();

        return response()->json([
            'clientes_total' => Cliente::where('company_id', $empresaId)->count(),
            'clientes_ativos' => Cliente::where('company_id', $empresaId)->where('ativo', true)->count(),
            'profissionais_total' => Profissional::where('company_id', $empresaId)->count(),
            'profissionais_ativos' => Profissional::where('company_id', $empresaId)->where('ativo', true)->count(),
            'servicos_total' => Servico::where('company_id', $empresaId)->count(),
            'servicos_ativos' => Servico::where('company_id', $empresaId)->where('ativo', true)->count(),
            'agendamentos_mes' => Agendamento::where('company_id', $empresaId)
                ->whereBetween('data_hora', [$mesInicio, $mesFim])
                ->count(),
            'agendamentos_mes_finalizados' => Agendamento::where('company_id', $empresaId)
                ->whereBetween('data_hora', [$mesInicio, $mesFim])
                ->where('status', Agendamento::STATUS_FINALIZADO)
                ->count(),
            'receita_mes' => (float) Agendamento::where('company_id', $empresaId)
                ->whereBetween('data_hora', [$mesInicio, $mesFim])
                ->where('status', Agendamento::STATUS_FINALIZADO)
                ->sum('valor'),
        ]);
    }
}
