@extends('layouts.app')
@section('title', 'Planos & Assinatura')
@section('page-title', 'Planos & Assinatura')

@section('content')

    {{-- Cabeçalho --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Planos & Assinatura</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">Gerencie seu plano e uso de mensagens</p>
        </div>
    </div>

    {{-- Banner plano atual --}}
    @if($company && $currentPlan)
    <div style="background:color-mix(in srgb,var(--sa-primary) 6%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 15%,transparent);border-radius:12px;padding:24px;margin-bottom:24px">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:24px;align-items:center">
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px">Plano Atual</div>
                <div style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:800;color:var(--sa-text1)">{{ $currentPlan->nome }}</div>
                <div style="font-size:13px;color:var(--sa-text3)">{{ $currentPlan->precoFormatado() }}/mês</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px">Profissionais</div>
                <div style="font-size:18px;font-weight:700;color:var(--sa-text1)">
                    {{ $currentPlan->max_profissionais === -1 ? 'Ilimitado' : $currentPlan->max_profissionais }}
                </div>
                <div style="font-size:13px;color:var(--sa-text3)">incluso no plano</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px">WhatsApp / mês</div>
                <div style="font-size:18px;font-weight:700;color:var(--sa-text1)">
                    {{ $currentPlan->whatsapp_mensal === -1 ? 'Ilimitado' : $currentPlan->whatsapp_mensal.' msgs' }}
                </div>
                <div style="font-size:13px;color:var(--sa-text3)">notificações incluídas</div>
            </div>
            <div>
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(16,185,129,.12);color:#059669">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    Ativo
                </span>
            </div>
        </div>
    </div>
    @endif

    {{-- Grid: comparação de planos --}}
    <h2 style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:600;color:var(--sa-text1);margin:0 0 16px">Comparar Planos</h2>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:32px">
        @foreach($plans as $plan)
        @php
            $isCurrent = $currentPlan?->slug === $plan->slug;
            $planColor = $plan->color;
        @endphp
        <div style="background:{{ $isCurrent ? 'color-mix(in srgb,var(--sa-primary) 6%,transparent)' : 'var(--sa-surface)' }};border:2px solid {{ $isCurrent ? 'var(--sa-primary)' : ($plan->popular ? $planColor : 'var(--sa-border)') }};border-radius:16px;padding:24px;position:relative;overflow:hidden">

            @if($plan->popular && !$isCurrent)
            <div style="position:absolute;top:12px;right:-22px;background:{{ $planColor }};color:#fff;font-size:10px;font-weight:700;padding:3px 28px;transform:rotate(45deg);letter-spacing:1px;text-transform:uppercase">POPULAR</div>
            @endif

            @if($isCurrent)
            <div style="position:absolute;top:14px;right:14px;font-size:10px;font-weight:700;color:var(--sa-primary);background:color-mix(in srgb,var(--sa-primary) 10%,transparent);border-radius:20px;padding:3px 10px;border:1px solid color-mix(in srgb,var(--sa-primary) 20%,transparent)">ATUAL</div>
            @endif

            <div style="width:36px;height:36px;border-radius:9px;background:{{ $planColor }}18;display:flex;align-items:center;justify-content:center;margin-bottom:14px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $planColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>

            <div style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:700;color:var(--sa-text1);margin-bottom:4px">{{ $plan->nome }}</div>
            <div style="margin-bottom:16px">
                <span style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:{{ $planColor }}">{{ $plan->precoFormatado() }}</span>
                <span style="font-size:12px;color:var(--sa-text3)">/mês</span>
            </div>

            <div style="font-size:12px;color:var(--sa-text3);margin-bottom:16px;display:flex;flex-direction:column;gap:4px">
                <div>👤 {{ $plan->max_profissionais === -1 ? 'Ilimitado' : $plan->max_profissionais }} profissional{{ $plan->max_profissionais !== 1 ? 'is' : '' }}</div>
                <div>💬 {{ $plan->whatsapp_mensal === -1 ? 'WhatsApp ilimitado' : $plan->whatsapp_mensal.' msgs WhatsApp/mês' }}</div>
            </div>

            <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:20px">
                @foreach(array_slice($plan->features, 0, 5) as $feature)
                <div style="display:flex;align-items:flex-start;gap:7px;font-size:12px;color:var(--sa-text2)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ $feature }}
                </div>
                @endforeach
                @if(count($plan->features) > 5)
                <div style="font-size:11px;color:var(--sa-text3);padding-left:20px">+{{ count($plan->features) - 5 }} mais...</div>
                @endif
            </div>

            @if($isCurrent)
            <button disabled style="width:100%;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);font-size:13px;font-weight:600;cursor:not-allowed">
                Plano Atual
            </button>
            @elsecan('update', $company)
            <form method="POST" action="{{ route('planos.update') }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="plan_slug" value="{{ $plan->slug }}">
                <button type="submit"
                        style="width:100%;padding:9px 16px;border-radius:8px;border:none;background:{{ $planColor }};color:#fff;font-size:13px;font-weight:600;cursor:pointer;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    {{ $currentPlan && $plan->ordem > $currentPlan->ordem ? 'Fazer Upgrade' : 'Fazer Downgrade' }}
                </button>
            </form>
            @endcan
        </div>
        @endforeach
    </div>

    <p style="font-size:12px;color:var(--sa-text3);text-align:center;margin-bottom:32px">
        Cancelamento sem multa a qualquer momento. Cobrança mensal via cartão de crédito.
    </p>

    {{-- Relatório de comissões (se houver profissionais com comissão) --}}
    @php
        $profissionaisComComissao = \App\Models\Profissional::where('company_id', auth()->user()->empresa_id)
            ->whereNotNull('comissao_pct')
            ->where('comissao_pct', '>', 0)
            ->with(['agendamentos' => fn($q) => $q->where('status', 'finalizado')->whereMonth('data_hora', now()->month)])
            ->get();
    @endphp

    @if($profissionaisComComissao->isNotEmpty())
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="padding:18px 20px;border-bottom:1px solid var(--sa-border)">
            <h3 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:600;color:var(--sa-text1);margin:0">
                Comissões — {{ now()->translatedFormat('F Y') }}
            </h3>
            <p style="font-size:13px;color:var(--sa-text3);margin:3px 0 0">Baseado em agendamentos finalizados no mês</p>
        </div>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Profissional</th>
                    <th style="padding:11px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Comissão %</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Receita gerada</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Comissão a pagar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($profissionaisComComissao as $prof)
                @php
                    $receitaProf = $prof->agendamentos->sum('valor');
                    $comissaoValor = $receitaProf * ($prof->comissao_pct / 100);
                @endphp
                <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms" onmouseover="this.style.background='var(--sa-surface2)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 16px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">{{ strtoupper(substr($prof->name, 0, 1)) }}</div>
                            <div>
                                <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $prof->name }}</div>
                                @if($prof->especialidade)
                                <div style="font-size:12px;color:var(--sa-text3)">{{ $prof->especialidade }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;text-align:center">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(212,165,116,.12);color:var(--sa-secondary)">
                            {{ $prof->comissaoFormatada() }}
                        </span>
                    </td>
                    <td style="padding:14px 16px;text-align:right;font-size:14px;font-weight:600;color:var(--sa-text1)">
                        R$ {{ number_format((float)$receitaProf, 2, ',', '.') }}
                    </td>
                    <td style="padding:14px 16px;text-align:right">
                        <span style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:800;color:var(--sa-secondary)">
                            R$ {{ number_format((float)$comissaoValor, 2, ',', '.') }}
                        </span>
                    </td>
                </tr>
                @endforeach
                @php $totalComissoes = $profissionaisComComissao->sum(fn($p) => $p->agendamentos->sum('valor') * ($p->comissao_pct / 100)); @endphp
                <tr style="background:var(--sa-surface2)">
                    <td colspan="3" style="padding:12px 16px;font-size:13px;font-weight:700;color:var(--sa-text2);text-align:right">Total de comissões a pagar:</td>
                    <td style="padding:12px 16px;text-align:right">
                        <span style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:800;color:var(--sa-secondary)">
                            R$ {{ number_format((float)$totalComissoes, 2, ',', '.') }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

@endsection
