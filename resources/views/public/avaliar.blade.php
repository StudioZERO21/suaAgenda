@extends('layouts.public')
@section('title', 'Avaliar Atendimento — ' . $company->name)

@section('header-right')
<div style="margin-left:auto;font-size:13px;color:rgba(255,255,255,.6)">{{ $company->name }}</div>
@endsection

@section('content')

<div style="text-align:center;margin-bottom:28px">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(212,165,116,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
    </div>
    <h1 style="font-family:var(--sa-font-heading);font-size:22px;font-weight:700;color:var(--sa-text1);margin-bottom:6px">Como foi seu atendimento?</h1>
    <p style="font-size:14px;color:var(--sa-text3)">{{ $ag->servico?->nome }} com {{ $ag->profissional?->name }} — {{ $ag->data_hora->format('d/m/Y') }}</p>
</div>

<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;margin-bottom:20px"
     x-data="avaliarApp()">
    <form method="POST" action="{{ route('avaliacao.store', $token) }}">
        @csrf

        {{-- Star rating --}}
        <div style="margin-bottom:24px">
            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:12px;text-align:center">
                Selecione sua nota
            </label>
            <div style="display:flex;justify-content:center;gap:8px">
                <template x-for="n in 5" :key="n">
                    <button type="button" @click="nota = n" @mouseover="hovered = n" @mouseleave="hovered = 0"
                            style="background:none;border:none;cursor:pointer;padding:0;font-size:40px;line-height:1;transition:transform 120ms"
                            :style="'background:none;border:none;cursor:pointer;padding:0;font-size:40px;line-height:1;transition:transform 120ms;transform:' + (nota === n ? 'scale(1.2)' : 'scale(1)')">
                        <span :style="'color:' + (n <= (hovered || nota) ? 'var(--sa-secondary)' : 'var(--sa-border)')">★</span>
                    </button>
                </template>
            </div>
            <input type="hidden" name="nota" :value="nota">
            <p x-show="nota > 0" style="text-align:center;margin-top:10px;font-size:13px;font-weight:600"
               :style="'color:' + labelColor()">
                <span x-text="label()"></span>
            </p>
        </div>

        {{-- Comentário --}}
        <div style="margin-bottom:24px">
            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                Deixe um comentário <span style="font-weight:400;color:var(--sa-text3)">(opcional)</span>
            </label>
            <textarea name="comentario" rows="3" maxlength="500"
                      placeholder="Como foi sua experiência?"
                      style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;resize:vertical;transition:border-color 180ms;box-sizing:border-box"
                      onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                      onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'"></textarea>
        </div>

        <button type="submit" x-bind:disabled="nota === 0"
                style="width:100%;padding:12px 20px;border-radius:8px;border:none;cursor:pointer;font-size:15px;font-weight:700;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;transition:filter 200ms,opacity 200ms"
                x-bind:style="nota === 0 ? 'width:100%;padding:12px 20px;border-radius:8px;border:none;cursor:not-allowed;font-size:15px;font-weight:700;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;opacity:.4' : 'width:100%;padding:12px 20px;border-radius:8px;border:none;cursor:pointer;font-size:15px;font-weight:700;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff'"
                onmouseover="if(this.disabled) return; this.style.filter='brightness(1.1)'"
                onmouseout="this.style.filter='none'">
            Enviar Avaliação
        </button>
    </form>
</div>

<div style="text-align:center">
    <a href="{{ route('agendamento.meu', $token) }}"
       style="font-size:13px;color:var(--sa-text3);text-decoration:none"
       onmouseover="this.style.color='var(--sa-text1)'" onmouseout="this.style.color='var(--sa-text3)'">
        Voltar para meu agendamento
    </a>
</div>

@endsection

@push('scripts')
<script>
function avaliarApp() {
    return {
        nota: 0,
        hovered: 0,
        labels: ['', 'Muito ruim', 'Ruim', 'Regular', 'Bom', 'Excelente!'],
        colors: ['', '#ef4444', '#f97316', '#f59e0b', '#10b981', '#059669'],
        label() { return this.labels[this.nota] || ''; },
        labelColor() { return this.colors[this.nota] || 'var(--sa-text1)'; },
    };
}
</script>
@endpush
