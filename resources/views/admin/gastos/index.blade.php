@extends('layouts.app')
@section('title', 'Gastos & Uso de Notificações')

@section('content')
@php
    $mesLabel = \Carbon\Carbon::createFromDate($ano, $mes, 1)->translatedFormat('F \d\e Y');
    $canaisLabel = ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'E-mail'];
    $canaisCores = ['whatsapp' => '#25d366', 'sms' => '#f59e0b', 'email' => '#3b82f6'];
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Gastos & Uso</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Consumo de notificações — {{ $mesLabel }}</p>
    </div>
    <a href="{{ route('admin.configuracoes.index') }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:all 150ms"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2m0 16v2m7.07-3.07l-1.41-1.41M4.93 19.07l1.41-1.41M22 12h-2M2 12h2"/></svg>
        Configurações
    </a>
</div>

{{-- ── Cards de resumo ─────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px">

    {{-- WhatsApp --}}
    <div style="background:color-mix(in srgb,#25d366 8%,transparent);border:1px solid color-mix(in srgb,#25d366 20%,transparent);border-radius:16px;padding:22px;position:relative;overflow:hidden">
        <div style="font-size:11px;font-weight:700;color:#25d366;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.8">WhatsApp</div>
        <div style="font-family:'Poppins',sans-serif;font-size:34px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ number_format($totais['whatsapp']) }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">mensagens enviadas</div>
        @php $custoWa = round($totais['whatsapp'] * 0.005, 2); @endphp
        <div style="font-size:13px;color:var(--sa-text2);margin-top:8px;font-weight:600">≈ US$ {{ number_format($custoWa, 2) }} via Twilio</div>
        <div style="position:absolute;bottom:-20px;right:-16px;opacity:.06">
            <svg width="100" height="100" viewBox="0 0 24 24" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </div>
    </div>

    {{-- SMS --}}
    <div style="background:color-mix(in srgb,#f59e0b 8%,transparent);border:1px solid color-mix(in srgb,#f59e0b 20%,transparent);border-radius:16px;padding:22px;position:relative;overflow:hidden">
        <div style="font-size:11px;font-weight:700;color:#d97706;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.8">SMS</div>
        <div style="font-family:'Poppins',sans-serif;font-size:34px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ number_format($totais['sms']) }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">mensagens enviadas</div>
        @php $custoSms = round($totais['sms'] * 0.0079, 2); @endphp
        <div style="font-size:13px;color:var(--sa-text2);margin-top:8px;font-weight:600">≈ US$ {{ number_format($custoSms, 2) }} via Twilio</div>
        <div style="position:absolute;bottom:-20px;right:-16px;opacity:.06">
            <svg width="100" height="100" viewBox="0 0 24 24" fill="#f59e0b"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
        </div>
    </div>

    {{-- Email --}}
    <div style="background:color-mix(in srgb,#3b82f6 8%,transparent);border:1px solid color-mix(in srgb,#3b82f6 20%,transparent);border-radius:16px;padding:22px;position:relative;overflow:hidden">
        <div style="font-size:11px;font-weight:700;color:#3b82f6;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.8">E-mail</div>
        <div style="font-family:'Poppins',sans-serif;font-size:34px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ number_format($totais['email']) }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">e-mails enviados</div>
        <div style="font-size:13px;color:var(--sa-text2);margin-top:8px;font-weight:600">via SMTP da plataforma</div>
    </div>

    {{-- Custo total estimado --}}
    <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent);border-radius:16px;padding:22px;position:relative;overflow:hidden">
        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.8">Custo Est. Twilio</div>
        <div style="font-family:'Poppins',sans-serif;font-size:34px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">US$ {{ number_format($custoTwilio, 2) }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">estimativa mensal</div>
        @if($evolutionCusto > 0)
        <div style="font-size:13px;color:var(--sa-text2);margin-top:8px;font-weight:600">+ R$ {{ number_format($evolutionCusto, 2) }} servidor Evolution</div>
        @endif
        <button onclick="buscarCustoReal(this)" style="margin-top:10px;display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:6px;border:1px solid color-mix(in srgb,var(--sa-secondary) 30%,transparent);background:transparent;color:var(--sa-secondary);font-size:11px;font-weight:600;cursor:pointer">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
            Buscar custo real Twilio
        </button>
        <div id="custo-real" style="font-size:12px;color:var(--sa-text3);margin-top:6px"></div>
    </div>

</div>

{{-- ── Alertas de limite ──────────────────────────────────────────── --}}
@if($alertas)
<div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);border-radius:12px;padding:16px 20px;margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span style="font-size:13px;font-weight:700;color:#d97706">{{ count($alertas) }} empresa(s) próximas do limite</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
        @foreach($alertas as $a)
        <div style="display:flex;align-items:center;gap:12px;font-size:13px">
            <span style="font-weight:600;color:var(--sa-text1);min-width:160px">{{ $a['company']->name }}</span>
            <span style="color:var(--sa-text3)">{{ $canaisLabel[$a['canal']] ?? $a['canal'] }}</span>
            <div style="flex:1;max-width:200px;background:var(--sa-border);border-radius:4px;height:6px">
                <div style="width:{{ min($a['percentual'], 100) }}%;height:6px;border-radius:4px;background:{{ $a['percentual'] >= 100 ? '#ef4444' : '#f59e0b' }}"></div>
            </div>
            <span style="color:{{ $a['percentual'] >= 100 ? '#ef4444' : '#d97706' }};font-weight:700">{{ $a['percentual'] }}%</span>
            <span style="color:var(--sa-text3)">{{ $a['usado'] }}/{{ $a['limite'] }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Gráfico histórico ─────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <h3 style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 20px">Volume — últimos 6 meses</h3>
        <div style="display:flex;align-items:flex-end;gap:8px;height:120px">
            @foreach($historico as $h)
            @php $max = max(1, $h['whatsapp'] + $h['sms'] + $h['email']); @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;height:100%">
                <div style="flex:1;width:100%;display:flex;flex-direction:column;justify-content:flex-end;gap:1px">
                    @if($h['email'])
                    <div style="width:100%;background:#3b82f6;border-radius:3px 3px 0 0;height:{{ round(($h['email'] / max(1, $historico->max(fn($i) => $i['whatsapp']+$i['sms']+$i['email']))) * 100) }}px;min-height:2px" title="Email: {{ $h['email'] }}"></div>
                    @endif
                    @if($h['sms'])
                    <div style="width:100%;background:#f59e0b;height:{{ round(($h['sms'] / max(1, collect($historico)->max(fn($i) => $i['whatsapp']+$i['sms']+$i['email']))) * 100) }}px;min-height:2px" title="SMS: {{ $h['sms'] }}"></div>
                    @endif
                    @if($h['whatsapp'])
                    <div style="width:100%;background:#25d366;border-radius:0;height:{{ round(($h['whatsapp'] / max(1, collect($historico)->max(fn($i) => $i['whatsapp']+$i['sms']+$i['email']))) * 100) }}px;min-height:2px" title="WhatsApp: {{ $h['whatsapp'] }}"></div>
                    @endif
                    @if(!$h['whatsapp'] && !$h['sms'] && !$h['email'])
                    <div style="width:100%;background:var(--sa-border);height:4px;border-radius:2px"></div>
                    @endif
                </div>
                <span style="font-size:10px;color:var(--sa-text3);white-space:nowrap">{{ $h['label'] }}</span>
            </div>
            @endforeach
        </div>
        <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap">
            @foreach([['#25d366','WhatsApp'],['#f59e0b','SMS'],['#3b82f6','E-mail']] as [$cor,$lbl])
            <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--sa-text3)">
                <span style="width:8px;height:8px;border-radius:2px;background:{{ $cor }};display:inline-block"></span>{{ $lbl }}
            </div>
            @endforeach
        </div>
    </div>

    {{-- Evolution status --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <h3 style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 16px">WhatsApp Evolution — Empresas</h3>
        @php
            $total     = $empresas->count();
            $conectadas = $empresas->where('evolution_connected', true)->count();
        @endphp
        <div style="display:flex;gap:20px;margin-bottom:16px">
            <div style="text-align:center">
                <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:#059669">{{ $conectadas }}</div>
                <div style="font-size:11px;color:var(--sa-text3)">conectadas</div>
            </div>
            <div style="text-align:center">
                <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text3)">{{ $total - $conectadas }}</div>
                <div style="font-size:11px;color:var(--sa-text3)">não conectadas</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;max-height:180px;overflow-y:auto">
            @foreach($empresas->take(8) as $emp)
            <div style="display:flex;align-items:center;gap:10px;font-size:13px">
                <span style="width:7px;height:7px;border-radius:50%;background:{{ $emp->evolution_connected ? '#059669' : 'var(--sa-border)' }};flex-shrink:0"></span>
                <span style="color:var(--sa-text2);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $emp->name }}</span>
                <span style="font-size:11px;color:{{ $emp->evolution_connected ? '#059669' : 'var(--sa-text3)' }}">{{ $emp->evolution_connected ? 'online' : 'offline' }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Tabela por empresa ────────────────────────────────────────── --}}
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
    <div style="padding:16px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between">
        <h3 style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;color:var(--sa-text1);margin:0">Uso por Empresa — {{ $mesLabel }}</h3>
        <span style="font-size:12px;color:var(--sa-text3)">{{ $uso->count() }} empresa(s) com envios</span>
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Empresa</th>
                <th style="padding:11px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#25d366" style="vertical-align:middle;margin-right:3px"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </th>
                <th style="padding:11px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">SMS</th>
                <th style="padding:11px 16px;text-align:center;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">E-mail</th>
                <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Plano</th>
                <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Custo Est.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($uso as $companyId => $canais)
            @php
                $emp   = $empresas[$companyId] ?? null;
                $wa    = $canais['whatsapp'] ?? 0;
                $sms   = $canais['sms'] ?? 0;
                $email = $canais['email'] ?? 0;
                $custo = round($wa * 0.005 + $sms * 0.0079, 3);
            @endphp
            <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms"
                onmouseover="this.style.background='var(--sa-surface2)'"
                onmouseout="this.style.background='transparent'">
                <td style="padding:12px 16px">
                    <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $emp?->name ?? 'Empresa removida' }}</div>
                    @if($emp?->evolution_connected)
                    <div style="font-size:11px;color:#059669;display:flex;align-items:center;gap:4px;margin-top:2px">
                        <span style="width:5px;height:5px;border-radius:50%;background:#059669;display:inline-block"></span>
                        Evolution conectado
                    </div>
                    @endif
                </td>
                <td style="padding:12px 16px;text-align:center;font-size:14px;color:var(--sa-text1);font-weight:600">{{ number_format($wa) }}</td>
                <td style="padding:12px 16px;text-align:center;font-size:14px;color:var(--sa-text1)">{{ number_format($sms) }}</td>
                <td style="padding:12px 16px;text-align:center;font-size:14px;color:var(--sa-text1)">{{ number_format($email) }}</td>
                <td style="padding:12px 16px">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(107,114,128,.1);color:#6b7280">
                        {{ ucfirst($emp?->plano ?? '—') }}
                    </span>
                </td>
                <td style="padding:12px 16px;text-align:right;font-size:13px;color:var(--sa-secondary);font-weight:600">
                    US$ {{ number_format($custo, 3) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding:32px;text-align:center;color:var(--sa-text3);font-size:14px">Nenhum envio registrado este mês.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@push('scripts')
<script>
async function buscarCustoReal(btn) {
    btn.disabled = true;
    btn.textContent = 'Buscando...';
    const el = document.getElementById('custo-real');
    try {
        const r = await fetch('{{ route('admin.gastos.custo-twilio') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        });
        const data = await r.json();
        if (data.records?.length) {
            const total = data.records.reduce((s, rec) => s + parseFloat(rec.price || 0), 0);
            el.textContent = `Custo real: US$ ${Math.abs(total).toFixed(4)}`;
            el.style.color = 'var(--sa-secondary)';
        } else {
            el.textContent = data.records === null ? 'Configure o Twilio para ver custo real.' : 'Sem dados Twilio no mês.';
        }
    } catch {
        el.textContent = 'Erro ao buscar dados Twilio.';
    }
    btn.disabled = false;
    btn.textContent = 'Buscar custo real Twilio';
}
</script>
@endpush
@endsection
