<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSiteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SitePublicoController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;
        $settings = $company?->resolvedSettings() ?? [];
        $site = $settings['site'] ?? [];

        $defaults = [
            'headline' => 'Arte em cada detalhe.',
            'subheadline' => $company?->description
                ?? 'Barbearia premium com os melhores profissionais. Experiência única desde 2018.',
            'cta_text' => 'Agendar Horário',
            'cta_secondary' => $company?->phone ?? '(11) 99999-0000',
            'show_stats' => true,
            'stats_items' => [
                ['n' => '8+',    'l' => 'Anos de experiência'],
                ['n' => '2.400', 'l' => 'Clientes atendidos'],
                ['n' => '4.9★',  'l' => 'Avaliação média'],
                ['n' => '98%',   'l' => 'Satisfação'],
            ],
            'banner_path' => null,
            'og_image' => null,
            'show_services' => true,
            'show_portfolio' => true,
            'show_team' => true,
            'show_testimonials' => true,
            'show_store' => true,
            'show_booking_cta' => true,
            'show_map' => true,
            'confirmation_msg' => 'Agendamento confirmado! Você receberá uma confirmação no WhatsApp em breve.',
            'reminder_msg' => 'Lembrete: você tem um agendamento amanhã às {hora}. Nos vemos em breve!',
            'cancellation_msg' => 'Seu agendamento foi cancelado. Sentimos muito, esperamos vê-lo em breve!',
            'lgpd_msg' => 'Ao agendar, você concorda com nossa Política de Privacidade e autoriza o contato via WhatsApp.',
            'welcome_popup' => '',
            'footer_text' => 'Powered by suaAgenda.pro',
            'meta_title' => ($company?->name ?? 'suaAgenda').' — Agendamento Online',
            'meta_desc' => 'Agende seu horário online de forma rápida e segura.',
            'keywords' => 'barbearia, corte, barba, agendamento online',
            'google_analytics' => '',
        ];

        $site = array_replace_recursive($defaults, $site);

        // Compute public URL for the banner thumbnail if a path is stored
        $bannerUrl = $site['banner_path']
            ? Storage::url($site['banner_path'])
            : null;

        $ogUrl = $site['og_image']
            ? Storage::url($site['og_image'])
            : null;

        $publicUrl = $company?->slug
            ? route('vitrine.show', $company->slug)
            : null;

        return view('site.index', compact('company', 'site', 'publicUrl', 'bannerUrl', 'ogUrl'));
    }

    public function save(UpdateSiteRequest $request): JsonResponse
    {
        $company = $request->user()->company;

        $data = $request->validated();

        $settings = $company->settings ?? [];
        // Preserve existing banner/og paths — they are managed by the upload endpoints
        $existing = $settings['site'] ?? [];
        $data['banner_path'] = $existing['banner_path'] ?? null;
        $data['og_image'] = $existing['og_image'] ?? null;

        $settings['site'] = $data;
        $company->update(['settings' => $settings]);

        return response()->json(['success' => true]);
    }

    public function uploadBanner(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $company = $request->user()->company;
        $companyId = $company->id;

        // Remove old banner if present
        $existing = $company->settings['site']['banner_path'] ?? null;
        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }

        $path = $request->file('image')
            ->store("site_banners/{$companyId}", 'public');

        $settings = $company->settings ?? [];
        $settings['site']['banner_path'] = $path;
        $company->update(['settings' => $settings]);

        return response()->json(['url' => Storage::url($path)]);
    }

    public function removeBanner(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $path = $company->settings['site']['banner_path'] ?? null;
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $settings = $company->settings ?? [];
        $settings['site']['banner_path'] = null;
        $company->update(['settings' => $settings]);

        return response()->json(['success' => true]);
    }

    public function uploadOg(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $company = $request->user()->company;
        $companyId = $company->id;

        // Remove old OG image if present
        $existing = $company->settings['site']['og_image'] ?? null;
        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }

        $path = $request->file('image')
            ->store("site_og/{$companyId}", 'public');

        $settings = $company->settings ?? [];
        $settings['site']['og_image'] = $path;
        $company->update(['settings' => $settings]);

        return response()->json(['url' => Storage::url($path)]);
    }
}
