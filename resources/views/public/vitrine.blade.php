@extends('layouts.public')

@section('title', $company->name)
@section('fullBleed', true)

@php
    $palette = ['#1a1a1a', '#d4a574', '#6366f1', '#10b981', '#f59e0b', '#ec4899'];
    $colorFor = fn (string $key) => $palette[crc32($key) % count($palette)];
    $bookUrl = route('agendar.show', $company->slug);
    $cfg = $siteCfg ?? [];

    $vitHeadline  = $cfg['headline']    ?? 'Arte em cada detalhe.';
    $vitSub       = $cfg['subheadline'] ?? ($company->description ?? 'Atendimento premium com os melhores profissionais. Uma experiência única para você.');
    $vitCtaText   = $cfg['cta_text']    ?? 'Agendar Horário';
    $vitCtaSec    = $cfg['cta_secondary'] ?? ($company->phone ?? $company->whatsapp ?? null);
    $vitBanner    = !empty($cfg['banner_path']) ? \Illuminate\Support\Facades\Storage::url($cfg['banner_path']) : null;
    $vitFooter    = $cfg['footer_text'] ?? null;
    $showStats    = ($cfg['show_stats']        ?? true)  !== false;
    $showServices = ($cfg['show_services']     ?? true)  !== false;
    $showTeam     = ($cfg['show_team']         ?? true)  !== false;
    $showTestimon = ($cfg['show_testimonials'] ?? true)  !== false;
    $showBookCta  = ($cfg['show_booking_cta']  ?? true)  !== false;
    $dispUrl      = route('vitrine.disponibilidade', $company->slug);
    $bookBase     = route('agendar.show', $company->slug);

    $descricoesServico = [
        'Corte personalizado com técnicas modernas e acabamento impecável.',
        'Modelagem e acabamento com produtos premium.',
        'Combinação perfeita para o visual completo e sofisticado.',
        'Serviço de alta qualidade e resultados duradouros.',
        'Tratamento intensivo com cuidado artesanal.',
        'Atendimento modelado com precisão e atenção aos detalhes.',
    ];

    $realNotaStr = $notaMediaReal !== null
        ? number_format((float) $notaMediaReal, 1, ',', '').'★'
        : '4.9★';
    $defaultStats = [
        ['n' => '8+', 'l' => 'Anos de experiência'],
        ['n' => number_format($company->clientes()->count() ?: 2400, 0, ',', '.'), 'l' => 'Clientes atendidos'],
        ['n' => $realNotaStr, 'l' => 'Avaliação média'],
        ['n' => $notaMediaReal !== null ? (int) round((float) $notaMediaReal / 5 * 100).'%' : '98%', 'l' => 'Satisfação'],
    ];
    $stats = !empty($cfg['stats_items']) ? $cfg['stats_items'] : $defaultStats;

    $depoimentos = $avaliacoesPublicas->isNotEmpty()
        ? $avaliacoesPublicas->toArray()
        : [
            ['name' => 'Miguel Santos', 'svc' => 'Corte + Barba', 'nota' => 5, 'text' => 'Melhor atendimento da cidade, sem dúvidas. Saí completamente transformado.'],
            ['name' => 'Bruno Lima',    'svc' => 'Barba completa',  'nota' => 5, 'text' => 'Talento incrível e atendimento impecável. Resultado perfeito.'],
            ['name' => 'Rodrigo Alves', 'svc' => 'Coloração',       'nota' => 5, 'text' => 'Ambiente sofisticado e atendimento excelente. Super recomendo!'],
        ];

    $telefone = $company->phone ?? $company->whatsapp ?? '(11) 99999-0000';
@endphp

@push('styles')
<style>
    .vit-section { max-width: 1140px; margin: 0 auto; padding: 0 48px; }
    .vit-kicker { font-size: 12px; font-weight: 600; color: var(--sa-secondary); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 12px; }
    .vit-h2 { font-family: var(--sa-font-heading); font-size: clamp(28px, 4vw, 36px); font-weight: 700; color: var(--sa-text1); margin: 0 0 14px; }
    .vit-card { background: var(--sa-surface); border: 1px solid var(--sa-border); border-radius: 16px; transition: box-shadow 220ms ease, transform 220ms ease; }
    .vit-card:hover { box-shadow: 0 12px 32px rgba(0,0,0,.1); transform: translateY(-3px); }
    .vit-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer; font-family: var(--sa-font-body); font-weight: 600; border-radius: 9px; text-decoration: none; transition: filter 180ms; }
    .vit-btn--primary { background: var(--sa-secondary); color: #fff; }
    .vit-btn--primary:hover { filter: brightness(1.06); }
    .vit-btn--sm { padding: 9px 16px; font-size: 13px; }
    .vit-btn--lg { padding: 14px 28px; font-size: 15px; }
    .vit-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media (max-width: 920px) { .vit-grid-3 { grid-template-columns: 1fr; } .vit-section { padding: 0 24px; } }
</style>
@endpush

@section('content')
<div style="min-height:100vh;background:var(--sa-bg)">

    {{-- ── NAVBAR ───────────────────────────────────────────── --}}
    <nav style="background:rgba(10,10,10,.96);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,.06);padding:0 clamp(20px,4vw,48px);display:flex;align-items:center;height:68px;position:sticky;top:0;z-index:100">
        <div style="display:flex;align-items:center;gap:12px;flex:1">
            <div style="width:38px;height:38px;border-radius:10px;background:var(--sa-secondary);display:flex;align-items:center;justify-content:center">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
            </div>
            <div>
                <div style="font-family:var(--sa-font-heading);font-size:17px;font-weight:700;color:#fff;letter-spacing:-.3px">{{ $company->name }}</div>
                <div style="font-size:10px;color:var(--sa-secondary);font-weight:600;letter-spacing:1.5px;text-transform:uppercase;margin-top:-1px">{{ $company->segment ?? 'Agendamento online' }}</div>
            </div>
        </div>
        <div style="display:flex;gap:28px;align-items:center">
            <a href="#servicos" class="vit-nav-link" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none;font-weight:500">Serviços</a>
            <a href="#equipe" class="vit-nav-link" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none;font-weight:500">Equipe</a>
            <a href="#contato" class="vit-nav-link" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none;font-weight:500">Contato</a>
            <a href="{{ route('portal.entrar', $company->slug) }}" class="vit-nav-link" style="font-size:13px;color:rgba(255,255,255,.55);text-decoration:none;font-weight:500">Minha Área</a>
            <a href="{{ $bookUrl }}" class="vit-btn vit-btn--primary vit-btn--sm" style="margin-left:8px">Agendar Agora</a>
        </div>
    </nav>

    {{-- ── HERO ─────────────────────────────────────────────── --}}
    <div style="position:relative;min-height:560px;overflow:hidden;background:#111">
        @if($vitBanner)
        <div style="position:absolute;inset:0;background-image:url('{{ $vitBanner }}');background-size:cover;background-position:center;background-repeat:no-repeat"></div>
        @else
        <div style="position:absolute;inset:0;opacity:.4;background-image:repeating-linear-gradient(115deg,#1a1a1a 0 20px,rgba(255,255,255,.03) 20px 40px)"></div>
        @endif
        <div style="position:absolute;inset:0;background:linear-gradient(to right,rgba(0,0,0,.85) 40%,rgba(0,0,0,.3) 100%)"></div>
        <div style="position:relative;min-height:560px;display:flex;flex-direction:column;justify-content:center;padding:0 clamp(24px,7vw,80px);max-width:720px">
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.1);border-radius:20px;padding:5px 14px;margin-bottom:24px;width:fit-content;border:1px solid rgba(255,255,255,.1)">
                <span style="font-size:11px;font-weight:600;color:var(--sa-secondary);letter-spacing:1.5px;text-transform:uppercase">{{ $company->address ?? 'Atendimento com hora marcada' }}</span>
            </div>
            <h1 style="font-family:var(--sa-font-heading);font-size:clamp(38px,6vw,56px);font-weight:800;color:#fff;line-height:1.05;margin:0 0 20px;letter-spacing:-1px">
                {{ $vitHeadline }}
            </h1>
            <p style="font-size:17px;color:rgba(255,255,255,.65);margin:0 0 36px;line-height:1.7;max-width:440px">
                {{ $vitSub }}
            </p>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <a href="{{ $bookUrl }}" class="vit-btn vit-btn--primary vit-btn--lg">{{ $vitCtaText }}</a>
                @if($vitCtaSec)
                <a href="{{ preg_match('/^\(?\d/', $vitCtaSec) ? 'tel:'.preg_replace('/\D/', '', $vitCtaSec) : $vitCtaSec }}"
                   style="display:flex;align-items:center;gap:8px;background:transparent;border:1.5px solid rgba(255,255,255,.25);border-radius:9px;padding:12px 24px;color:#fff;font-size:15px;font-weight:600;text-decoration:none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    {{ $vitCtaSec }}
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── STATS BAR ────────────────────────────────────────── --}}
    @if($showStats && count($stats) > 0)
    <div style="background:var(--sa-primary);padding:0 clamp(24px,7vw,80px)">
        @php $statCount = count($stats); @endphp
        <div style="display:grid;grid-template-columns:repeat({{ $statCount }},1fr);max-width:960px;margin:0 auto">
            @foreach($stats as $i => $s)
            <div style="padding:28px 0;text-align:center;{{ $i < $statCount - 1 ? 'border-right:1px solid rgba(255,255,255,.1)' : '' }}">
                <div style="font-family:var(--sa-font-heading);font-size:clamp(24px,3vw,32px);font-weight:800;color:var(--sa-secondary);line-height:1">{{ $s['n'] }}</div>
                <div style="font-size:12px;color:rgba(255,255,255,.5);margin-top:6px;font-weight:500;letter-spacing:.3px">{{ $s['l'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div style="padding:72px 0">

        {{-- ── SERVIÇOS ─────────────────────────────────────── --}}
        @if($showServices)
        <div id="servicos" class="vit-section" style="margin-bottom:80px;scroll-margin-top:80px">
            <div style="text-align:center;margin-bottom:48px">
                <p class="vit-kicker">O que oferecemos</p>
                <h2 class="vit-h2">Nossos Serviços</h2>
                <p style="font-size:16px;color:var(--sa-text3);max-width:480px;margin:0 auto">Cada atendimento é pensado com atenção e precisão para superar suas expectativas.</p>
            </div>
            @if($servicos->isEmpty())
                <p style="text-align:center;color:var(--sa-text3)">Em breve, novos serviços disponíveis.</p>
            @else
            <div class="vit-grid-3">
                @foreach($servicos as $i => $svc)
                <div class="vit-card" style="padding:28px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px">
                        <div style="width:52px;height:52px;border-radius:14px;background:color-mix(in srgb,var(--sa-secondary) 15%,transparent);display:flex;align-items:center;justify-content:center">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                        </div>
                        <span style="font-size:11px;font-weight:600;color:var(--sa-text3);background:var(--sa-surface2);border:1px solid var(--sa-border);border-radius:20px;padding:3px 10px">{{ $svc->duracaoFormatada() }}</span>
                    </div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0 0 8px">{{ $svc->nome }}</h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 20px;line-height:1.6">{{ $svc->descricao ?? $descricoesServico[$i % count($descricoesServico)] }}</p>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <span style="font-family:var(--sa-font-heading);font-size:24px;font-weight:800;color:var(--sa-secondary)">{{ $svc->precoFormatado() }}</span>
                        <a href="{{ $bookUrl }}" class="vit-btn vit-btn--primary vit-btn--sm">Agendar</a>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- ── EQUIPE ───────────────────────────────────────── --}}
        @if($showTeam && $profissionais->isNotEmpty())
        <div id="equipe" class="vit-section" style="margin-bottom:80px;scroll-margin-top:80px" x-data="equipeApp()">
            <div style="text-align:center;margin-bottom:48px">
                <p class="vit-kicker">Quem faz acontecer</p>
                <h2 class="vit-h2" style="margin:0">Nossa Equipe</h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">
                @foreach($profissionais as $prof)
                @php $cor = $colorFor($prof->name); @endphp
                <div class="vit-card" style="overflow:hidden">
                    <div style="position:relative;height:220px;background:{{ $cor }}18;display:flex;align-items:center;justify-content:center">
                        <div style="width:96px;height:96px;border-radius:50%;background:{{ $cor }};color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--sa-font-heading);font-size:34px;font-weight:700">
                            {{ strtoupper(mb_substr($prof->name, 0, 1)) }}
                        </div>
                        <div style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,.7);border-radius:20px;padding:4px 10px;display:flex;align-items:center;gap:4px">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="#fbbf24" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <span style="font-size:12px;font-weight:700;color:#fff">4.9</span>
                        </div>
                    </div>
                    <div style="padding:24px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">{{ $prof->name }}</h3>
                        <p style="font-size:12px;color:var(--sa-secondary);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin:0 0 12px">{{ $prof->especialidade ?? 'Profissional' }}</p>
                        <p style="font-size:13px;color:var(--sa-text3);line-height:1.7;margin:0 0 20px">
                            {{ $prof->agendamentos_count }} atendimento{{ $prof->agendamentos_count === 1 ? '' : 's' }} realizados. Profissional dedicado a entregar o melhor resultado.
                        </p>
                        @if($prof->especialidade)
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px">
                            <span style="font-size:11px;font-weight:600;color:var(--sa-secondary);background:color-mix(in srgb,var(--sa-secondary) 12%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 30%,transparent);border-radius:20px;padding:3px 10px">{{ $prof->especialidade }}</span>
                        </div>
                        @endif
                        <button type="button" class="vit-btn vit-btn--primary vit-btn--sm" style="width:100%"
                                @click="abrir('{{ $prof->id }}', @js($prof->name), '{{ $cor }}')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Ver Horários
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ── MODAL: Horários do profissional ───────────────── --}}
            <div x-show="aberto" x-cloak @keydown.escape.window="fechar()"
                 style="position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;z-index:1000;padding:16px"
                 @click.self="fechar()">
                <div style="background:var(--sa-surface);border-radius:16px;width:min(560px, calc(100vw - 24px));max-height:88vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.25);padding:24px 26px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;gap:12px">
                        <div style="display:flex;align-items:center;gap:12px;min-width:0">
                            <div :style="`width:44px;height:44px;border-radius:50%;background:${cor};color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:700;flex-shrink:0;font-family:var(--sa-font-heading)`"
                                 x-text="profNome.charAt(0).toUpperCase()"></div>
                            <div style="min-width:0">
                                <div style="font-family:var(--sa-font-heading);font-size:17px;font-weight:700;color:var(--sa-text1)" x-text="profNome"></div>
                                <div style="font-size:12px;color:var(--sa-text3)">Escolha o serviço e o horário</div>
                            </div>
                        </div>
                        <button type="button" @click="fechar()" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;flex-shrink:0">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    {{-- Serviço --}}
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:6px">Serviço</label>
                    <select x-model="servicoSel" @change="buscar()"
                            style="width:100%;padding:11px 14px;border:1.5px solid var(--sa-border);border-radius:9px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;margin-bottom:18px">
                        <template x-for="s in servicosDoProf" :key="s.id">
                            <option :value="s.id" x-text="s.nome + ' — ' + s.preco"></option>
                        </template>
                    </select>

                    {{-- Abas de dia --}}
                    <div style="display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;margin-bottom:18px">
                        <template x-for="(d, i) in dias" :key="d.iso">
                            <button type="button" @click="diaSel = d.iso; buscar()"
                                    :style="diaSel === d.iso ? 'border-color:var(--sa-secondary);background:var(--sa-secondary);color:#fff' : 'border-color:var(--sa-border);background:var(--sa-surface);color:var(--sa-text1)'"
                                    style="padding:9px 14px;border-radius:10px;border:1.5px solid;min-width:62px;flex-shrink:0;cursor:pointer;text-align:center;transition:all 180ms">
                                <div style="font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;opacity:.7" x-text="i === 0 ? 'Hoje' : d.weekday"></div>
                                <div style="font-family:var(--sa-font-heading);font-size:18px;font-weight:700;line-height:1.1" x-text="d.day"></div>
                            </button>
                        </template>
                    </div>

                    {{-- Estado / contador --}}
                    <div x-show="!carregando" x-cloak style="margin-bottom:14px">
                        <div :style="livres > 0 ? 'background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.2);color:#059669' : 'background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.15);color:#dc2626'"
                             style="display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;border:1px solid">
                            <span style="width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                            <span style="font-size:13px;font-weight:600" x-text="livres > 0 ? (livres + ' horários disponíveis') : 'Sem horários neste dia'"></span>
                        </div>
                    </div>

                    {{-- Slots --}}
                    <div x-show="!carregando" x-cloak style="display:flex;flex-wrap:wrap;gap:10px">
                        <template x-for="slot in slots" :key="slot.hora">
                            <button type="button" x-show="slot.disponivel" @click="ir(slot.hora)"
                                    :style="`background:color-mix(in srgb,${cor} 10%,transparent);color:${cor};border:1.5px solid color-mix(in srgb,${cor} 35%,transparent)`"
                                    style="padding:9px 16px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;font-family:var(--sa-font-body);transition:filter 150ms"
                                    @mouseenter="$el.style.filter='brightness(1.12)'" @mouseleave="$el.style.filter='none'"
                                    x-text="slot.hora"></button>
                        </template>
                    </div>

                    <div x-show="carregando" x-cloak style="text-align:center;padding:24px;color:var(--sa-text3);font-size:14px">Buscando horários…</div>
                </div>
            </div>
        </div>
        <script>
        function equipeApp() {
            const SERVICOS = @json($servicosMap);
            const semana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
            return {
                aberto: false,
                profId: null, profNome: '', cor: 'var(--sa-primary)',
                servicosDoProf: [], servicoSel: '',
                dias: [], diaSel: '',
                slots: [], carregando: false,
                get livres() { return this.slots.filter(s => s.disponivel).length; },

                gerarDias() {
                    const dias = [];
                    for (let i = 0; i < 14; i++) {
                        const d = new Date(); d.setHours(12,0,0,0); d.setDate(d.getDate() + i);
                        dias.push({
                            iso: d.toISOString().split('T')[0],
                            weekday: semana[d.getDay()],
                            day: String(d.getDate()).padStart(2,'0'),
                        });
                    }
                    this.dias = dias;
                },

                abrir(profId, nome, cor) {
                    this.profId = profId; this.profNome = nome; this.cor = cor;
                    this.servicosDoProf = Object.entries(SERVICOS)
                        .filter(([, s]) => s.profissionais.includes(profId))
                        .map(([id, s]) => ({ id, nome: s.nome, preco: s.preco }));
                    this.servicoSel = this.servicosDoProf[0]?.id ?? '';
                    if (!this.dias.length) this.gerarDias();
                    this.diaSel = this.dias[0].iso;
                    this.slots = [];
                    this.aberto = true;
                    if (this.servicoSel) this.buscar();
                },

                fechar() { this.aberto = false; },

                async buscar() {
                    if (!this.servicoSel || !this.diaSel) { this.slots = []; return; }
                    this.carregando = true; this.slots = [];
                    try {
                        const r = await fetch(`{{ $dispUrl }}?servico_id=${this.servicoSel}&data=${this.diaSel}`);
                        if (r.ok) {
                            const rows = await r.json();
                            const row = rows.find(x => x.profissional.id === this.profId);
                            this.slots = row ? row.slots : [];
                        }
                    } catch (e) { this.slots = []; }
                    this.carregando = false;
                },

                ir(hora) {
                    window.location = `{{ $bookBase }}?servico_id=${this.servicoSel}&profissional_id=${this.profId}&data=${this.diaSel}&hora=${hora}`;
                },
            };
        }
        </script>
        @endif

        {{-- ── DEPOIMENTOS ──────────────────────────────────── --}}
        @if($showTestimon)
        <div class="vit-section" style="margin-bottom:20px">
            <div style="text-align:center;margin-bottom:40px">
                <p class="vit-kicker">Depoimentos</p>
                <h2 class="vit-h2" style="margin:0">O que dizem nossos clientes</h2>
            </div>
            <div class="vit-grid-3">
                @foreach($depoimentos as $t)
                @php $cor = $colorFor($t['name']); @endphp
                <div class="vit-card" style="padding:28px">
                    <div style="display:flex;gap:2px;margin-bottom:16px">
                        @for($s = 1; $s <= 5; $s++)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="{{ $s <= ($t['nota'] ?? 5) ? 'var(--sa-secondary)' : 'var(--sa-border2)' }}" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        @endfor
                    </div>
                    <p style="font-size:15px;color:var(--sa-text1);line-height:1.7;margin:0 0 20px;font-style:italic">"{{ $t['text'] }}"</p>
                    <div style="display:flex;align-items:center;gap:12px;padding-top:16px;border-top:1px solid var(--sa-border)">
                        <div style="width:36px;height:36px;border-radius:50%;background:{{ $cor }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700">{{ strtoupper(mb_substr($t['name'], 0, 1)) }}</div>
                        <div>
                            <div style="font-size:14px;font-weight:700;color:var(--sa-text1)">{{ $t['name'] }}</div>
                            <div style="font-size:12px;color:var(--sa-text3)">{{ $t['svc'] }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ── CTA ──────────────────────────────────────────────── --}}
    @if($showBookCta)
    <div style="background:var(--sa-primary);position:relative;overflow:hidden">
        <div style="position:absolute;inset:0;background-image:repeating-linear-gradient(45deg,transparent 0 16px,rgba(255,255,255,.02) 16px 32px);pointer-events:none"></div>
        <div style="position:relative;max-width:700px;margin:0 auto;padding:80px 48px;text-align:center">
            <p class="vit-kicker">Pronto para uma nova experiência?</p>
            <h2 style="font-family:var(--sa-font-heading);font-size:clamp(30px,5vw,42px);font-weight:800;color:#fff;margin:0 0 18px;line-height:1.1">Agende seu horário hoje mesmo</h2>
            <p style="font-size:16px;color:rgba(255,255,255,.55);margin:0 0 36px;line-height:1.7">Confirmação imediata. Sem filas, sem espera.</p>
            <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
                <a href="{{ $bookUrl }}" class="vit-btn vit-btn--primary vit-btn--lg">Agendar Agora</a>
                <a href="tel:{{ preg_replace('/\D/', '', $telefone) }}" style="display:flex;align-items:center;gap:8px;background:transparent;border:1.5px solid rgba(255,255,255,.25);border-radius:9px;padding:14px 28px;color:#fff;font-size:15px;font-weight:600;text-decoration:none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    Ligar Agora
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- ── FOOTER ───────────────────────────────────────────── --}}
    <footer id="contato" style="background:#0a0a0a;padding:48px clamp(24px,7vw,80px) 28px;scroll-margin-top:80px">
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:48px;margin-bottom:40px;max-width:1140px;margin-left:auto;margin-right:auto" class="vit-footer-grid">
            <div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                    <div style="width:36px;height:36px;border-radius:10px;background:var(--sa-secondary);display:flex;align-items:center;justify-content:center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                    </div>
                    <div style="font-family:var(--sa-font-heading);font-size:18px;font-weight:700;color:#fff">{{ $company->name }}</div>
                </div>
                <p style="font-size:13px;color:rgba(255,255,255,.4);line-height:1.8;max-width:280px;margin:0 0 20px">
                    {{ $company->description ?? 'Atendimento personalizado, onde estilo encontra qualidade.' }}
                </p>
                <div style="display:flex;gap:10px">
                    @foreach(['instagram' => $company->instagram, 'facebook' => $company->facebook] as $rede => $valor)
                        @if($valor)
                        <a href="{{ $valor }}" target="_blank" rel="noopener" style="width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center" title="{{ ucfirst($rede) }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.3);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:16px">Links</div>
                <a href="#servicos" style="display:block;font-size:13px;color:rgba(255,255,255,.45);margin-bottom:10px;text-decoration:none">Serviços</a>
                <a href="#equipe" style="display:block;font-size:13px;color:rgba(255,255,255,.45);margin-bottom:10px;text-decoration:none">Nossa Equipe</a>
                <a href="{{ $bookUrl }}" style="display:block;font-size:13px;color:rgba(255,255,255,.45);margin-bottom:10px;text-decoration:none">Agendamento</a>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.3);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:16px">Contato</div>
                @if($company->address)
                <div style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-start">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" style="margin-top:1px;flex-shrink:0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span style="font-size:12px;color:rgba(255,255,255,.45);line-height:1.6">{{ $company->address }}</span>
                </div>
                @endif
                <div style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-start">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" style="margin-top:1px;flex-shrink:0"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span style="font-size:12px;color:rgba(255,255,255,.45);line-height:1.6">{{ $telefone }}</span>
                </div>
            </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,.06);padding-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;max-width:1140px;margin:0 auto">
            <span style="font-size:12px;color:rgba(255,255,255,.2)">&copy; {{ date('Y') }} {{ $company->name }}. Todos os direitos reservados.</span>
            <span style="font-size:11px;color:rgba(255,255,255,.15)">{{ $vitFooter ?? 'Powered by suaAgenda.pro' }}</span>
        </div>
    </footer>
</div>

@push('styles')
<style>
    .vit-nav-link:hover { color: #fff !important; }
    html { scroll-behavior: smooth; }
    @media (max-width: 760px) { .vit-footer-grid { grid-template-columns: 1fr !important; gap: 28px !important; } }
</style>
@endpush
@endsection
