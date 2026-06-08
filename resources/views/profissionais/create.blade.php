@extends('layouts.app')
@section('title', 'Novo Profissional')
@section('page-title', 'Novo Profissional')

@section('content')
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
        <a href="{{ route('profissionais.index') }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:var(--sa-font-heading);font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 2px">Novo Profissional</h1>
            <p style="font-size:13px;color:var(--sa-text3);margin:0">Preencha os dados do profissional</p>
        </div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px">
        <form method="POST" action="{{ route('profissionais.store') }}" style="display:flex;flex-direction:column;gap:18px">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nome completo <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Nome do profissional"
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('name') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('name')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Especialidade</label>
                    <input type="text" name="especialidade" value="{{ old('especialidade') }}" placeholder="Ex: Barbeiro, Cabeleireiro, Manicure"
                           style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                        Comissão (%)
                        <span style="font-size:11px;color:var(--sa-text3);font-weight:400;margin-left:4px">opcional</span>
                    </label>
                    <input type="number" name="comissao_pct" value="{{ old('comissao_pct') }}" min="0" max="100" step="0.5" placeholder="Ex: 30"
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('comissao_pct') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('comissao_pct')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;padding:14px;background:var(--sa-surface2);border-radius:8px;border:1px solid var(--sa-border)">
                <input type="checkbox" name="ativo" id="ativo" value="1"
                       style="width:16px;height:16px;accent-color:var(--sa-primary);cursor:pointer;flex-shrink:0"
                       {{ old('ativo', true) ? 'checked' : '' }}>
                <label for="ativo" style="font-size:13px;color:var(--sa-text2);cursor:pointer">
                    Profissional ativo (disponível para agendamentos)
                </label>
            </div>

            @if($servicos->isNotEmpty())
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:10px">Serviços que este profissional realiza</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px">
                    @foreach($servicos as $servico)
                    <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;cursor:pointer;transition:border-color 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                        <input type="checkbox" name="servicos[]" value="{{ $servico->id }}"
                               style="accent-color:var(--sa-primary);width:15px;height:15px;flex-shrink:0"
                               {{ in_array($servico->id, old('servicos', [])) ? 'checked' : '' }}>
                        <div>
                            <div style="display:flex;align-items:center;gap:6px">
                                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $servico->cor }};flex-shrink:0"></span>
                                <span style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $servico->nome }}</span>
                            </div>
                            <div style="font-size:11px;color:var(--sa-text3);margin-top:1px">{{ $servico->duracaoFormatada() }} • {{ $servico->precoFormatado() }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div style="display:flex;gap:10px;padding-top:4px">
                <button type="submit"
                        style="padding:11px 24px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    Salvar Profissional
                </button>
                <a href="{{ route('profissionais.index') }}"
                   style="padding:11px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:all 180ms"
                   onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
