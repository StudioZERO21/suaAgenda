@extends('layouts.app')
@section('title', $empresa->name)
@section('page-title', 'Empresa')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">{{ $empresa->name }}</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $empresa->slug }} · cadastrada em {{ $empresa->created_at->format('d/m/Y') }}</p>
    </div>
    <a href="{{ route('admin.empresas.index') }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
        ← Voltar
    </a>
</div>

<div style="max-width:1100px">
    <div class="sa-grid-4" style="margin-bottom:20px">
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">USUÁRIOS</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $empresa->users_count }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">AGENDAMENTOS (TOTAL)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $empresa->agendamentos_count }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">AGENDAMENTOS (30D)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $agendamentos30 }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.9">RECEITA (30D)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">R$ {{ number_format($receita30, 2, ',', '.') }}</div>
        </div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:20px">
        <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:14px">Dados da empresa</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
            @foreach([
                'Plano' => $empresa->plan_slug ?? '—',
                'E-mail' => $empresa->email ?? '—',
                'Telefone' => $empresa->phone ?? '—',
                'WhatsApp' => $empresa->whatsapp ?? '—',
                'Segmento' => $empresa->segment ?? '—',
                'Trial até' => $empresa->trial_ends_at?->format('d/m/Y') ?? '—',
                'LGPD consent' => $empresa->lgpd_consent ? 'Sim' : 'Não',
                'Status' => $empresa->ativo ? 'Ativa' : 'Inativa',
            ] as $label => $valor)
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">{{ $label }}</div>
                <div style="font-size:14px;color:var(--sa-text1)">{{ $valor }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Limites de notificação ────────────────────────────────── --}}
    @php
        $uso = \App\Services\NotificationUsageService::statusMes($empresa);
        $planoLimites = config('notification_limits.planos.'.$empresa->plano, config('notification_limits.planos.default'));
    @endphp
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        {{-- Uso atual --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:4px">Uso de notificações</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-bottom:16px">{{ now()->translatedFormat('F \d\e Y') }}</div>

            @foreach(['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'E-mail'] as $canal => $label)
            @php $u = $uso[$canal]; @endphp
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
                    <span style="font-size:13px;font-weight:600;color:var(--sa-text2)">{{ $label }}</span>
                    <span style="font-size:13px;font-weight:700;color:{{ $u['esgotado'] ? '#dc2626' : ($u['alerta'] ? '#d97706' : 'var(--sa-text1)') }}">
                        {{ $u['usado'] }} <span style="font-weight:400;color:var(--sa-text3)">/ {{ $u['limite'] === -1 ? '∞' : number_format($u['limite']) }}</span>
                    </span>
                </div>
                <div style="background:var(--sa-border);border-radius:4px;height:7px;overflow:hidden">
                    @php
                        $pct = $u['limite'] === -1 ? 0 : min(100, $u['percentual']);
                        $cor = $u['esgotado'] ? '#ef4444' : ($u['alerta'] ? '#f59e0b' : '#059669');
                    @endphp
                    <div style="width:{{ $pct }}%;height:7px;background:{{ $cor }};border-radius:4px;transition:width 400ms"></div>
                </div>
                @if($u['esgotado'])
                <p style="font-size:11px;color:#dc2626;margin:3px 0 0">Limite atingido este mês.</p>
                @elseif($u['alerta'])
                <p style="font-size:11px;color:#d97706;margin:3px 0 0">{{ $u['percentual'] }}% do limite usado.</p>
                @endif
            </div>
            @endforeach

            {{-- WhatsApp Evolution status --}}
            <div style="margin-top:4px;padding-top:14px;border-top:1px solid var(--sa-border);display:flex;align-items:center;gap:8px">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $empresa->evolution_connected ? '#059669' : 'var(--sa-border)' }};flex-shrink:0"></span>
                <span style="font-size:13px;color:var(--sa-text2)">
                    Evolution: <strong style="color:{{ $empresa->evolution_connected ? '#059669' : 'var(--sa-text3)' }}">{{ $empresa->evolution_connected ? 'conectado' : 'não conectado' }}</strong>
                    @if($empresa->evolution_instance)
                    <span style="font-size:11px;color:var(--sa-text3);margin-left:6px">{{ $empresa->evolution_instance }}</span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Override de limites --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:4px">Override de limites</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-bottom:16px">
                Plano <strong>{{ ucfirst($empresa->plano ?? 'default') }}</strong>: WA {{ $planoLimites['whatsapp'] === -1 ? '∞' : $planoLimites['whatsapp'] }} · SMS {{ $planoLimites['sms'] === -1 ? '∞' : $planoLimites['sms'] }} · E-mail {{ $planoLimites['email'] === -1 ? '∞' : $planoLimites['email'] }}/mês
            </div>

            @if(session('success_limites'))
            <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#059669">
                ✓ {{ session('success_limites') }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.empresas.limites', $empresa) }}" style="display:flex;flex-direction:column;gap:12px">
                @csrf @method('PATCH')
                @foreach(['notif_limit_whatsapp' => 'WhatsApp (msgs/mês)', 'notif_limit_sms' => 'SMS (msgs/mês)', 'notif_limit_email' => 'E-mail (msgs/mês)'] as $field => $label)
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">{{ $label }}</label>
                    <input type="number" name="{{ $field }}" min="0"
                           value="{{ $empresa->$field ?? '' }}"
                           placeholder="Padrão do plano"
                           style="width:100%;padding:9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 150ms;box-sizing:border-box"
                           onfocus="this.style.borderColor='var(--sa-primary)'"
                           onblur="this.style.borderColor='var(--sa-border)'">
                    <p style="font-size:11px;color:var(--sa-text3);margin:3px 0 0">Deixe em branco para usar o padrão do plano. 0 = bloquear.</p>
                </div>
                @endforeach
                <button type="submit"
                        style="display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'"
                        onmouseout="this.style.filter='none'">
                    Salvar limites
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
