<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                ['n' => '8+', 'l' => 'Anos de experiência'],
                ['n' => '2.400', 'l' => 'Clientes atendidos'],
                ['n' => '4.9★', 'l' => 'Avaliação média'],
                ['n' => '98%', 'l' => 'Satisfação'],
            ],
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

        $publicUrl = $company?->slug
            ? route('vitrine.show', $company->slug)
            : null;

        return view('site.index', compact('company', 'site', 'publicUrl'));
    }

    public function save(Request $request): JsonResponse
    {
        $company = auth()->user()->company;

        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_secondary' => ['nullable', 'string', 'max:100'],
            'show_stats' => ['nullable', 'boolean'],
            'stats_items' => ['nullable', 'array', 'max:6'],
            'stats_items.*.n' => ['string', 'max:20'],
            'stats_items.*.l' => ['string', 'max:60'],
            'show_services' => ['nullable', 'boolean'],
            'show_portfolio' => ['nullable', 'boolean'],
            'show_team' => ['nullable', 'boolean'],
            'show_testimonials' => ['nullable', 'boolean'],
            'show_store' => ['nullable', 'boolean'],
            'show_booking_cta' => ['nullable', 'boolean'],
            'show_map' => ['nullable', 'boolean'],
            'confirmation_msg' => ['nullable', 'string', 'max:500'],
            'reminder_msg' => ['nullable', 'string', 'max:500'],
            'cancellation_msg' => ['nullable', 'string', 'max:500'],
            'lgpd_msg' => ['nullable', 'string', 'max:500'],
            'welcome_popup' => ['nullable', 'string', 'max:1000'],
            'footer_text' => ['nullable', 'string', 'max:200'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_desc' => ['nullable', 'string', 'max:160'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'google_analytics' => ['nullable', 'string', 'max:50'],
        ]);

        $settings = $company->settings ?? [];
        $settings['site'] = $data;
        $company->update(['settings' => $settings]);

        return response()->json(['success' => true]);
    }
}
