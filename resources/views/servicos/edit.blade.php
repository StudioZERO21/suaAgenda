@extends('layouts.app')
@section('title', 'Editar Serviço')
@section('page-title', 'Editar Serviço')

@section('content')
<div style="max-width:680px">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
        <a href="{{ route('servicos.index') }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 2px">Editar Serviço</h1>
            <p style="font-size:13px;color:var(--sa-text3);margin:0">{{ $servico->nome }}</p>
        </div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px">
        <form method="POST" action="{{ route('servicos.update', $servico) }}" style="display:flex;flex-direction:column;gap:18px">
            @csrf
            @method('PUT')

            @php $vinculados = $servico->profissionais->pluck('id')->map(fn($id) => (string)$id)->toArray(); @endphp

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:5px">Nome do serviço <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="text" name="nome" value="{{ old('nome', $servico->nome) }}" required placeholder="Ex: Corte de cabelo"
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('nome') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:9px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                           onfocus="this.style.borderColor='var(--sa-secondary)'" onblur="this.style.borderColor='var(--sa-border)'">
                    @error('nome')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:5px">Duração (minutos) <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="number" name="duracao_minutos" value="{{ old('duracao_minutos', $servico->duracao_minutos) }}" min="5" max="480" step="5" required
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('duracao_minutos') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:9px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                           onfocus="this.style.borderColor='var(--sa-secondary)'" onblur="this.style.borderColor='var(--sa-border)'">
                    @error('duracao_minutos')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:5px">Preço (R$) <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="number" name="preco" value="{{ old('preco', $servico->preco) }}" min="0" step="0.01" required
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('preco') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:9px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                           onfocus="this.style.borderColor='var(--sa-secondary)'" onblur="this.style.borderColor='var(--sa-border)'">
                    @error('preco')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:5px">Categoria</label>
                    <input type="text" name="categoria" value="{{ old('categoria', $servico->categoria) }}" placeholder="Ex: Cabelo, Barba, Tratamento"
                           style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:9px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                           onfocus="this.style.borderColor='var(--sa-secondary)'" onblur="this.style.borderColor='var(--sa-border)'">
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:5px">Cor (no calendário)</label>
                    <div style="display:flex;align-items:center;gap:10px">
                        <input type="color" name="cor" value="{{ old('cor', $servico->cor) }}"
                               style="width:44px;height:40px;padding:2px;border:1.5px solid var(--sa-border);border-radius:9px;cursor:pointer;background:var(--sa-surface)">
                        <span style="font-size:13px;color:var(--sa-text3)">Cor de identificação no calendário</span>
                    </div>
                </div>

                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:5px">Descrição</label>
                    <textarea name="descricao" rows="2" placeholder="Descrição opcional do serviço"
                              style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:9px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;resize:vertical;transition:border-color 180ms;font-family:'Inter',sans-serif"
                              onfocus="this.style.borderColor='var(--sa-secondary)'" onblur="this.style.borderColor='var(--sa-border)'">{{ old('descricao', $servico->descricao) }}</textarea>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;padding:14px;background:var(--sa-surface2);border-radius:9px;border:1px solid var(--sa-border)">
                <input type="checkbox" name="ativo" id="ativo" value="1"
                       style="width:16px;height:16px;accent-color:var(--sa-primary);cursor:pointer;flex-shrink:0"
                       {{ old('ativo', $servico->ativo) ? 'checked' : '' }}>
                <label for="ativo" style="font-size:13px;color:var(--sa-text2);cursor:pointer">
                    Serviço ativo (disponível para agendamento)
                </label>
            </div>

            @if($profissionais->isNotEmpty())
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:10px">Profissionais que realizam este serviço</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px">
                    @foreach($profissionais as $prof)
                    @php $checked = in_array((string)$prof->id, old('profissionais', $vinculados)); @endphp
                    <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid {{ $checked ? 'var(--sa-secondary)' : 'var(--sa-border)' }};border-radius:9px;cursor:pointer;transition:border-color 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='{{ $checked ? 'var(--sa-secondary)' : 'var(--sa-border)' }}'">
                        <input type="checkbox" name="profissionais[]" value="{{ $prof->id }}"
                               style="accent-color:var(--sa-primary);width:15px;height:15px;flex-shrink:0"
                               {{ $checked ? 'checked' : '' }}>
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
                        style="padding:11px 24px;border-radius:9px;border:none;cursor:pointer;font-size:14px;font-weight:600;background:var(--sa-primary);color:#fff;transition:background 180ms"
                        onmouseover="this.style.background='var(--sa-secondary)'" onmouseout="this.style.background='var(--sa-primary)'">
                    Salvar Alterações
                </button>
                <a href="{{ route('servicos.index') }}"
                   style="padding:11px 20px;border-radius:9px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:all 180ms"
                   onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
