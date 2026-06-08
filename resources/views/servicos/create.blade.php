@extends('layouts.app')
@section('title', 'Novo Serviço')
@section('page-title', 'Novo Serviço')

@section('content')
<div style="max-width:680px">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
        <a href="{{ route('servicos.index') }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:var(--sa-font-heading);font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 2px">Novo Serviço</h1>
            <p style="font-size:13px;color:var(--sa-text3);margin:0">Preencha os dados do serviço</p>
        </div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px">
        <form method="POST" action="{{ route('servicos.store') }}" style="display:flex;flex-direction:column;gap:18px">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                {{-- Nome --}}
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nome do serviço <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="text" name="nome" value="{{ old('nome') }}" required placeholder="Ex: Corte de cabelo"
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('nome') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('nome')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Duração --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Duração (minutos) <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="number" name="duracao_minutos" value="{{ old('duracao_minutos', 30) }}" min="5" max="480" step="5" required
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('duracao_minutos') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('duracao_minutos')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Preço --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Preço (R$) <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="number" name="preco" value="{{ old('preco', '0.00') }}" min="0" step="0.01" required
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('preco') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('preco')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Categoria --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Categoria</label>
                    <input type="text" name="categoria" value="{{ old('categoria') }}" placeholder="Ex: Cabelo, Barba, Tratamento"
                           style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                </div>

                {{-- Cor --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Cor (no calendário)</label>
                    <div style="display:flex;align-items:center;gap:10px">
                        <input type="color" name="cor" value="{{ old('cor', '#1a1a1a') }}"
                               style="width:44px;height:40px;padding:2px;border:1.5px solid var(--sa-border);border-radius:8px;cursor:pointer;background:var(--sa-surface)">
                        <span style="font-size:13px;color:var(--sa-text3)">Escolha uma cor para identificar este serviço</span>
                    </div>
                </div>

                {{-- Descrição --}}
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Descrição</label>
                    <textarea name="descricao" rows="2" placeholder="Descrição opcional do serviço"
                              style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;resize:vertical;transition:border-color 180ms,outline 180ms;font-family:var(--sa-font-body)"
                              onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">{{ old('descricao') }}</textarea>
                </div>
            </div>

            {{-- Ativo --}}
            <div style="display:flex;align-items:center;gap:10px;padding:14px;background:var(--sa-surface2);border-radius:8px;border:1px solid var(--sa-border)">
                <input type="checkbox" name="ativo" id="ativo" value="1"
                       style="width:16px;height:16px;accent-color:var(--sa-primary);cursor:pointer;flex-shrink:0"
                       {{ old('ativo', true) ? 'checked' : '' }}>
                <label for="ativo" style="font-size:13px;color:var(--sa-text2);cursor:pointer">
                    Serviço ativo (disponível para agendamento)
                </label>
            </div>

            {{-- Profissionais --}}
            @if($profissionais->isNotEmpty())
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:10px">Profissionais que realizam este serviço</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px">
                    @foreach($profissionais as $prof)
                    <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;cursor:pointer;transition:border-color 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                        <input type="checkbox" name="profissionais[]" value="{{ $prof->id }}"
                               style="accent-color:var(--sa-primary);width:15px;height:15px;flex-shrink:0"
                               {{ in_array($prof->id, old('profissionais', [])) ? 'checked' : '' }}>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $prof->name }}</div>
                            @if($prof->especialidade)
                            <div style="font-size:11px;color:var(--sa-text3)">{{ $prof->especialidade }}</div>
                            @endif
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
                    Salvar Serviço
                </button>
                <a href="{{ route('servicos.index') }}"
                   style="padding:11px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:all 180ms"
                   onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
