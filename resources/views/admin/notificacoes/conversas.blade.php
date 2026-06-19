@extends('layouts.app')
@section('title', 'Conversas WhatsApp')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('admin.notificacoes.index') }}"
           style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
           onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
           onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Conversas WhatsApp</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">Mensagens recebidas e enviadas via Twilio</p>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;max-width:1100px;height:calc(100vh - 180px);min-height:500px">

    {{-- ── Lista de contatos ──────────────────────────────── --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;display:flex;flex-direction:column">
        <div style="padding:14px 16px;border-bottom:1px solid var(--sa-border);background:var(--sa-surface2)">
            <span style="font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Contatos</span>
        </div>
        <div style="overflow-y:auto;flex:1">
            @forelse($contatos as $c)
            @php $ativo = $numero === $c->contato; @endphp
            <a href="{{ route('admin.notificacoes.conversas', ['numero' => $c->contato]) }}"
               style="display:block;padding:14px 16px;border-bottom:1px solid var(--sa-border);text-decoration:none;transition:background 120ms;
                      {{ $ativo ? 'background:color-mix(in srgb,var(--sa-primary) 6%,transparent)' : '' }}"
               onmouseover="if(!{{ $ativo ? 'true' : 'false' }}) this.style.background='var(--sa-surface2)'"
               onmouseout="if(!{{ $ativo ? 'true' : 'false' }}) this.style.background='transparent'">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.334.101 11.893c0 2.096.549 4.14 1.595 5.945L0 24l6.335-1.652c1.746.943 3.71 1.444 5.71 1.445h.006c6.585 0 11.946-5.336 11.949-11.896.001-3.176-1.24-6.165-3.48-8.448zm-8.475 18.3h-.004c-1.774 0-3.513-.474-5.03-1.37l-.36-.214-3.742.976.999-3.648-.235-.374c-.99-1.574-1.512-3.393-1.511-5.26.002-5.45 4.437-9.884 9.889-9.884 2.64 0 5.122 1.03 6.988 2.898 1.866 1.869 2.893 4.352 2.892 6.993-.003 5.451-4.437 9.883-9.886 9.883zm5.43-7.403c-.297-.149-1.758-.867-2.03-.967-.271-.099-.47-.148-.669.15-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347z"/></svg>
                    </div>
                    <div style="min-width:0">
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $c->contato }}
                        </div>
                        <div style="font-size:11px;color:var(--sa-text3)">
                            {{ $c->total }} mensagen{{ $c->total != 1 ? 's' : '' }} · {{ \Carbon\Carbon::parse($c->ultima_msg)->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </a>
            @empty
            <div style="padding:32px 16px;text-align:center;color:var(--sa-text3);font-size:13px">
                Nenhuma conversa ainda.<br>
                <span style="font-size:12px">Configure o webhook para receber mensagens.</span>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── Chat ────────────────────────────────────────────── --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);display:flex;flex-direction:column;overflow:hidden">

        @if($numero)
        {{-- Header do chat --}}
        <div style="padding:14px 20px;border-bottom:1px solid var(--sa-border);background:var(--sa-surface2);display:flex;align-items:center;gap:12px">
            <div style="width:36px;height:36px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.334.101 11.893c0 2.096.549 4.14 1.595 5.945L0 24l6.335-1.652c1.746.943 3.71 1.444 5.71 1.445h.006c6.585 0 11.946-5.336 11.949-11.896.001-3.176-1.24-6.165-3.48-8.448zm-8.475 18.3h-.004c-1.774 0-3.513-.474-5.03-1.37l-.36-.214-3.742.976.999-3.648-.235-.374c-.99-1.574-1.512-3.393-1.511-5.26.002-5.45 4.437-9.884 9.889-9.884 2.64 0 5.122 1.03 6.988 2.898 1.866 1.869 2.893 4.352 2.892 6.993-.003 5.451-4.437 9.883-9.886 9.883zm5.43-7.403c-.297-.149-1.758-.867-2.03-.967-.271-.099-.47-.148-.669.15-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347z"/></svg>
            </div>
            <div>
                <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $numero }}</div>
                <div style="font-size:12px;color:var(--sa-text3)">{{ $mensagens->count() }} mensagen{{ $mensagens->count() != 1 ? 's' : '' }}</div>
            </div>
            <button onclick="location.reload()" title="Atualizar"
                    style="margin-left:auto;width:32px;height:32px;border-radius:8px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
            </button>
        </div>

        {{-- Mensagens --}}
        <div id="chat-messages" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px;background:#f0f0f0">
            @forelse($mensagens as $msg)
            @php $outbound = $msg->direction === 'outbound'; @endphp
            <div style="display:flex;justify-content:{{ $outbound ? 'flex-end' : 'flex-start' }}">
                <div style="max-width:70%;padding:10px 14px;border-radius:{{ $outbound ? '16px 4px 16px 16px' : '4px 16px 16px 16px' }};
                            background:{{ $outbound ? '#dcf8c6' : '#ffffff' }};
                            box-shadow:0 1px 2px rgba(0,0,0,.1)">
                    <div style="font-size:14px;color:#1a1a1a;line-height:1.5;white-space:pre-wrap">{{ $msg->body }}</div>
                    <div style="font-size:10px;color:#888;margin-top:4px;text-align:right">
                        {{ $msg->created_at->format('d/m H:i') }}
                        @if($outbound)
                        <span style="margin-left:4px">✓✓</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div style="text-align:center;color:#888;font-size:13px;margin:auto">Nenhuma mensagem ainda.</div>
            @endforelse
        </div>

        {{-- Input de resposta --}}
        <div style="padding:14px 16px;border-top:1px solid var(--sa-border);background:var(--sa-surface2);display:flex;gap:10px;align-items:flex-end">
            <textarea id="msg-input" rows="2" placeholder="Digite uma mensagem..."
                      style="flex:1;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;resize:none;transition:border-color 180ms"
                      onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                      onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();enviarResposta()}"></textarea>
            <button onclick="enviarResposta()"
                    style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:8px;border:none;cursor:pointer;background:#25d366;color:#fff;flex-shrink:0;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>

        @else
        {{-- Estado vazio --}}
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--sa-text3);gap:12px">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".3"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            <p style="font-size:14px;margin:0">Selecione um contato para ver a conversa</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const NUMERO_ATUAL = @json($numero);

// Scroll para o final das mensagens
const chat = document.getElementById('chat-messages');
if (chat) chat.scrollTop = chat.scrollHeight;

async function enviarResposta() {
    const input = document.getElementById('msg-input');
    const msg = input.value.trim();
    if (!msg || !NUMERO_ATUAL) return;

    input.disabled = true;

    try {
        const r = await fetch('{{ route('admin.notificacoes.responder') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero: NUMERO_ATUAL, mensagem: msg }),
        });
        const data = await r.json();

        if (data.ok) {
            // Adiciona mensagem enviada no chat visualmente
            const bubble = document.createElement('div');
            bubble.style.cssText = 'display:flex;justify-content:flex-end';
            bubble.innerHTML = `<div style="max-width:70%;padding:10px 14px;border-radius:16px 4px 16px 16px;background:#dcf8c6;box-shadow:0 1px 2px rgba(0,0,0,.1)">
                <div style="font-size:14px;color:#1a1a1a;line-height:1.5;white-space:pre-wrap">${msg.replace(/</g,'&lt;')}</div>
                <div style="font-size:10px;color:#888;margin-top:4px;text-align:right">agora ✓✓</div>
            </div>`;
            chat.appendChild(bubble);
            chat.scrollTop = chat.scrollHeight;
            input.value = '';
        } else {
            Swal.fire({ icon: 'error', title: 'Erro ao enviar', text: data.erro ?? 'Falha desconhecida', confirmButtonColor: 'var(--sa-primary)' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'Erro de rede', confirmButtonColor: 'var(--sa-primary)' });
    } finally {
        input.disabled = false;
        input.focus();
    }
}

// Auto-refresh a cada 15s para pegar novas mensagens inbound
@if($numero)
setInterval(() => location.reload(), 15000);
@endif
</script>
@endpush
@endsection
