@extends('layouts.app')
@section('title', 'Site Público')

@push('styles')
<style>
    .sa-site-layout { display:grid; grid-template-columns:190px minmax(0,1fr); gap:24px; align-items:start; }
    .sa-site-content { min-width:0; }
    .sa-site-nav { position:sticky; top:16px; display:flex; flex-direction:column; gap:2px; }
    .sa-site-nav button {
        display:flex; align-items:center; gap:9px; padding:10px 12px; border-radius:9px; border:none; cursor:pointer;
        width:100%; text-align:left; font-size:13px; font-family:var(--sa-font-body); background:transparent;
        color:var(--sa-text2); font-weight:500; transition:all 150ms; border-left:2px solid transparent;
    }
    .sa-site-nav button.active {
        background:color-mix(in srgb,var(--sa-primary) 8%,transparent); color:var(--sa-primary);
        font-weight:600; border-left-color:var(--sa-primary);
    }
    /* Flex no wrapper interno — o div x-show externo fica block (evita conflito Alpine + x-cloak) */
    .sa-site-tab-panel { display:flex; flex-direction:column; gap:20px; }
    .sa-site-field {
        width:100%; padding:9px 12px; font-size:13px; border:1.5px solid var(--sa-border); border-radius:8px;
        background:var(--sa-surface); color:var(--sa-text1); outline:none; box-sizing:border-box;
        font-family:var(--sa-font-body); transition:border-color 160ms,outline 160ms;
    }
    .sa-site-field:focus { border-color:var(--sa-primary); outline:3px solid rgba(0,0,0,.06); }
    .sa-site-label { font-size:13px; font-weight:600; color:var(--sa-text1); display:block; margin-bottom:6px; letter-spacing:.2px; }
    .sa-site-helper { font-size:11px; color:var(--sa-text3); margin-top:4px; }
    .sa-site-dropzone { border:2px dashed var(--sa-border); border-radius:12px; padding:32px; text-align:center; cursor:pointer; transition:all 200ms; }
    .sa-site-dropzone:hover { border-color:var(--sa-primary); background:color-mix(in srgb,var(--sa-primary) 3%,transparent); }
    .sa-site-dropzone--sm { padding:24px; border-radius:10px; }
    .sa-site-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
    .sa-site-stats-grid > template { display:none; }
    .sa-toggle { width:42px; height:24px; border-radius:12px; border:none; cursor:pointer; background:var(--sa-border); position:relative; flex-shrink:0; padding:0; transition:background 200ms; }
    .sa-toggle.is-on { background:var(--sa-primary); }
    .sa-toggle__knob { position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; transition:left 200ms; box-shadow:0 1px 4px rgba(0,0,0,.2); }
    .sa-toggle.is-on .sa-toggle__knob { left:20px; }
    .sa-setting-row { display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid var(--sa-border); gap:16px; }
    .sa-setting-row:last-child { border-bottom:none; }
    .sa-setting-row__label { font-size:13px; font-weight:600; color:var(--sa-text1); }
    .sa-setting-row__sub { font-size:12px; color:var(--sa-text3); margin-top:2px; }
    .sa-setting-row__text { flex:1; padding-right:20px; }
    .sa-site-public-btn {
        display:flex; align-items:center; justify-content:center; gap:8px; margin-top:12px; padding:10px 12px;
        border-radius:9px; border:1.5px solid var(--sa-border); background:var(--sa-surface); color:var(--sa-text2);
        font-size:12px; font-weight:600; text-decoration:none; transition:border-color 180ms,color 180ms;
    }
    .sa-site-public-btn:hover { border-color:var(--sa-primary); color:var(--sa-text1); }
    @media (max-width:768px) {
        .sa-site-layout { grid-template-columns:1fr; }
        .sa-site-nav { position:static; flex-direction:row; overflow-x:auto; }
        .sa-site-stats-grid { grid-template-columns:repeat(2,1fr); }
    }
</style>
@endpush

@push('scripts')
<script>
/** Alpine state for Site Público — dados injetados no script (padrão permissionsApp). */
function siteApp() {
    const saveUrl = @json(route('site.save'));
    const uploadBannerUrl = @json(route('site.upload.banner'));
    const removeBannerUrl = @json(route('site.remove.banner'));
    const uploadOgUrl = @json(route('site.upload.og'));
    const csrfToken = @json(csrf_token());

    return {
        tab: 'banner',
        saving: false,
        uploading: false,
        uploadingOg: false,
        site: @json($site),
        bannerUrl: @json($bannerUrl),
        ogUrl: @json($ogUrl),

        toggleBool(key) {
            this.site[key] = !this.site[key];
        },

        async save() {
            this.saving = true;
            try {
                const res = await fetch(saveUrl, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(this.site),
                });
                if (!res.ok) throw new Error();
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Configurações do site salvas!', showConfirmButton: false,
                    timer: 2800, timerProgressBar: true,
                });
            } catch {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'error',
                    title: 'Erro ao salvar. Tente novamente.', showConfirmButton: false, timer: 2800,
                });
            } finally {
                this.saving = false;
            }
        },

        triggerBannerUpload() {
            this.$refs.bannerInput.click();
        },

        async handleBannerFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.uploading = true;
            const fd = new FormData();
            fd.append('image', file);
            fd.append('_token', csrfToken);
            try {
                const res = await fetch(uploadBannerUrl, { method: 'POST', body: fd });
                if (!res.ok) throw new Error();
                const json = await res.json();
                this.bannerUrl = json.url;
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Banner enviado com sucesso!', showConfirmButton: false,
                    timer: 2500, timerProgressBar: true,
                });
            } catch {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'error',
                    title: 'Erro ao enviar o banner.', showConfirmButton: false, timer: 2500,
                });
            } finally {
                this.uploading = false;
                e.target.value = '';
            }
        },

        async removeBanner() {
            const r = await Swal.fire({
                title: 'Remover banner?', text: 'O banner atual será apagado permanentemente.',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar', confirmButtonColor: '#ef4444',
                cancelButtonColor: 'transparent', customClass: { cancelButton: 'swal-cancel-muted' },
            });
            if (!r.isConfirmed) return;
            try {
                const res = await fetch(removeBannerUrl, {
                    method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken },
                });
                if (!res.ok) throw new Error();
                this.bannerUrl = null;
            } catch {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'error',
                    title: 'Erro ao remover o banner.', showConfirmButton: false, timer: 2500,
                });
            }
        },

        triggerOgUpload() {
            this.$refs.ogInput.click();
        },

        async handleOgFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.uploadingOg = true;
            const fd = new FormData();
            fd.append('image', file);
            fd.append('_token', csrfToken);
            try {
                const res = await fetch(uploadOgUrl, { method: 'POST', body: fd });
                if (!res.ok) throw new Error();
                const json = await res.json();
                this.ogUrl = json.url;
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'OG Image enviada!', showConfirmButton: false, timer: 2500, timerProgressBar: true,
                });
            } catch {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'error',
                    title: 'Erro ao enviar a imagem.', showConfirmButton: false, timer: 2500,
                });
            } finally {
                this.uploadingOg = false;
                e.target.value = '';
            }
        },
    };
}
</script>
@endpush

@section('content')
<x-sa.page x-data="siteApp()">
    <x-sa.app-header title="Configurações do Site" subtitle="Personalize sua página pública de agendamento">
        <x-slot:actions>
            <x-sa.btn type="button" @click="save()" x-bind:disabled="saving">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                <span x-show="!saving">Salvar Alterações</span>
                <span x-show="saving" x-cloak>Salvando…</span>
            </x-sa.btn>
            @if($publicUrl)
            <x-sa.btn variant="secondary" :href="$publicUrl" target="_blank">Ver Página Pública</x-sa.btn>
            @endif
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body>
        <div class="sa-site-layout">
            <nav class="sa-site-nav">
                @foreach([
                    ['id' => 'banner',   'label' => 'Banner & Hero',   'icon' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'],
                    ['id' => 'sections', 'label' => 'Seções',          'icon' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>'],
                    ['id' => 'messages', 'label' => 'Mensagens',       'icon' => '<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>'],
                    ['id' => 'seo',      'label' => 'SEO & Analytics', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>'],
                ] as $t)
                <button type="button" @click="tab = '{{ $t['id'] }}'" :class="{ active: tab === '{{ $t['id'] }}' }">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $t['icon'] !!}</svg>
                    {{ $t['label'] }}
                </button>
                @endforeach

                <div style="margin-top:16px;padding:12px;background:color-mix(in srgb,var(--sa-secondary) 10%,transparent);border-radius:10px;border:1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent)">
                    <div style="font-size:12px;font-weight:700;color:var(--sa-secondary);margin-bottom:6px">Pré-visualização</div>
                    <div style="font-size:11px;color:var(--sa-text3);line-height:1.6">Acesse a página pública pelo botão no rodapé do painel</div>
                    @if($publicUrl)
                    <a href="{{ $publicUrl }}" target="_blank" style="display:block;margin-top:8px;font-size:11px;color:var(--sa-secondary);word-break:break-all;text-decoration:none">{{ $publicUrl }}</a>
                    @endif
                </div>

                @if($publicUrl)
                <a href="{{ $publicUrl }}" target="_blank" class="sa-site-public-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                    Página Pública
                </a>
                @endif
            </nav>

            <div class="sa-site-content">

                {{-- Banner & Hero --}}
                <div x-show="tab === 'banner'">
                    <div class="sa-site-tab-panel">
                    <x-sa.card padding="22px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 16px">Textos do Hero</h3>
                        <div style="display:flex;flex-direction:column;gap:12px">
                            <div>
                                <label class="sa-site-label">Título principal (H1)</label>
                                <input type="text" class="sa-site-field" x-model="site.headline" placeholder="Ex: Arte em cada detalhe.">
                            </div>
                            <div>
                                <label class="sa-site-label">Subtítulo</label>
                                <textarea rows="2" class="sa-site-field" x-model="site.subheadline" placeholder="Descrição breve do negócio" style="resize:vertical"></textarea>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                <div>
                                    <label class="sa-site-label">Texto do botão principal</label>
                                    <input type="text" class="sa-site-field" x-model="site.cta_text" placeholder="Ex: Agendar Horário">
                                </div>
                                <div>
                                    <label class="sa-site-label">Botão secundário (telefone)</label>
                                    <input type="text" class="sa-site-field" x-model="site.cta_secondary" placeholder="(11) 99999-0000">
                                </div>
                            </div>
                        </div>
                    </x-sa.card>

                    <x-sa.card padding="22px">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                            <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0">Barra de Estatísticas</h3>
                            <button type="button" role="switch" class="sa-toggle" :class="{ 'is-on': site.show_stats }" :aria-checked="site.show_stats ? 'true' : 'false'" @click="toggleBool('show_stats')">
                                <span class="sa-toggle__knob"></span>
                            </button>
                        </div>
                        <div x-show="site.show_stats" class="sa-site-stats-grid">
                            <template x-for="(stat, i) in site.stats_items" :key="i">
                                <div style="background:var(--sa-surface2);border-radius:9px;padding:10px 12px;border:1px solid var(--sa-border)">
                                    <input type="text" x-model="site.stats_items[i].n"
                                        style="width:100%;font-size:18px;font-weight:800;color:var(--sa-secondary);border:none;background:transparent;font-family:var(--sa-font-heading);outline:none;margin-bottom:4px">
                                    <input type="text" x-model="site.stats_items[i].l"
                                        style="width:100%;font-size:11px;color:var(--sa-text3);border:none;background:transparent;font-family:var(--sa-font-body);outline:none">
                                </div>
                            </template>
                        </div>
                    </x-sa.card>

                    <x-sa.card padding="22px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 14px">Banner Principal</h3>
                        <input type="file" x-ref="bannerInput" accept="image/jpeg,image/png,image/webp" style="display:none" @change="handleBannerFile($event)">

                        <div x-show="bannerUrl" x-cloak style="margin-bottom:14px">
                            <div style="position:relative;border-radius:10px;overflow:hidden;border:1px solid var(--sa-border)">
                                <img :src="bannerUrl" alt="Banner atual" style="width:100%;max-height:180px;object-fit:cover;display:block">
                                <button type="button" @click="removeBanner()"
                                    style="position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);color:#fff;transition:background 150ms"
                                    onmouseover="this.style.background='rgba(239,68,68,.85)'"
                                    onmouseout="this.style.background='rgba(0,0,0,.6)'"
                                    title="Remover banner">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                                </button>
                                <button type="button" @click="triggerBannerUpload()"
                                    style="position:absolute;top:10px;right:50px;width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);color:#fff;transition:background 150ms"
                                    onmouseover="this.style.background='rgba(0,0,0,.85)'"
                                    onmouseout="this.style.background='rgba(0,0,0,.6)'"
                                    title="Trocar banner">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </button>
                            </div>
                        </div>

                        <div x-show="!bannerUrl" class="sa-site-dropzone" @click="triggerBannerUpload()">
                            <div x-show="!uploading">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;margin:0 auto 10px;opacity:.4">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                <div style="font-size:14px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">Fazer upload do banner</div>
                                <div style="font-size:12px;color:var(--sa-text3)">JPG, PNG ou WebP · Recomendado: 1920×800px · Máx. 5MB</div>
                            </div>
                            <div x-show="uploading" x-cloak style="font-size:14px;color:var(--sa-text3)">Enviando…</div>
                        </div>
                    </x-sa.card>
                    </div>
                </div>

                {{-- Seções --}}
                <div x-show="tab === 'sections'">
                    <x-sa.card padding="22px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 4px">Seções Visíveis</h3>
                        <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px">Controle quais seções aparecem na sua página pública</p>
                        @foreach([
                            ['key' => 'show_services',     'label' => 'Serviços',         'sub' => 'Grid com todos os serviços e preços'],
                            ['key' => 'show_portfolio',    'label' => 'Portfólio',        'sub' => 'Galeria de fotos dos trabalhos'],
                            ['key' => 'show_team',         'label' => 'Equipe',           'sub' => 'Cards dos profissionais com botão de agendamento'],
                            ['key' => 'show_testimonials', 'label' => 'Depoimentos',      'sub' => 'Carrossel de avaliações de clientes'],
                            ['key' => 'show_store',        'label' => 'Loja de Produtos', 'sub' => 'Exibe produtos para compra (requer produtos cadastrados)'],
                            ['key' => 'show_booking_cta',  'label' => 'Seção de CTA',     'sub' => 'Chamada final para agendamento antes do rodapé'],
                            ['key' => 'show_map',          'label' => 'Mapa & Contato',   'sub' => 'Localização e informações de contato no rodapé'],
                        ] as $sec)
                        <div class="sa-setting-row">
                            <div class="sa-setting-row__text">
                                <div class="sa-setting-row__label">{{ $sec['label'] }}</div>
                                <div class="sa-setting-row__sub">{{ $sec['sub'] }}</div>
                            </div>
                            <button type="button" role="switch" class="sa-toggle"
                                :class="{ 'is-on': site.{{ $sec['key'] }} }"
                                :aria-checked="site.{{ $sec['key'] }} ? 'true' : 'false'"
                                @click="toggleBool('{{ $sec['key'] }}')">
                                <span class="sa-toggle__knob"></span>
                            </button>
                        </div>
                        @endforeach
                    </x-sa.card>
                </div>

                {{-- Mensagens --}}
                <div x-show="tab === 'messages'">
                    <div class="sa-site-tab-panel">
                    <x-sa.card padding="22px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 4px">Mensagens Automáticas</h3>
                        <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px">
                            Use
                            <code style="background:var(--sa-surface2);padding:1px 5px;border-radius:4px;font-size:12px">{hora}</code>,
                            <code style="background:var(--sa-surface2);padding:1px 5px;border-radius:4px;font-size:12px">{nome}</code>,
                            <code style="background:var(--sa-surface2);padding:1px 5px;border-radius:4px;font-size:12px">{servico}</code>
                            como variáveis
                        </p>
                        <div style="display:flex;flex-direction:column;gap:14px">
                            <div>
                                <label class="sa-site-label">Confirmação de agendamento</label>
                                <textarea rows="2" class="sa-site-field" x-model="site.confirmation_msg" style="resize:vertical"></textarea>
                            </div>
                            <div>
                                <label class="sa-site-label">Lembrete (WhatsApp/SMS)</label>
                                <textarea rows="2" class="sa-site-field" x-model="site.reminder_msg" style="resize:vertical"></textarea>
                            </div>
                            <div>
                                <label class="sa-site-label">Cancelamento</label>
                                <textarea rows="2" class="sa-site-field" x-model="site.cancellation_msg" style="resize:vertical"></textarea>
                            </div>
                        </div>
                    </x-sa.card>

                    <x-sa.card padding="22px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 16px">Textos da Página</h3>
                        <div style="display:flex;flex-direction:column;gap:14px">
                            <div>
                                <label class="sa-site-label">Aviso LGPD (formulário de agendamento)</label>
                                <textarea rows="2" class="sa-site-field" x-model="site.lgpd_msg" style="resize:vertical"></textarea>
                            </div>
                            <div>
                                <label class="sa-site-label">Texto do rodapé</label>
                                <input type="text" class="sa-site-field" x-model="site.footer_text" placeholder="Powered by suaAgenda.pro">
                            </div>
                            <div>
                                <label class="sa-site-label">Popup de boas-vindas (deixe em branco para desativar)</label>
                                <textarea rows="3" class="sa-site-field" x-model="site.welcome_popup" placeholder="Ex: Bem-vindo! Aproveite 10% de desconto no primeiro agendamento." style="resize:vertical"></textarea>
                            </div>
                        </div>
                    </x-sa.card>
                    </div>
                </div>

                {{-- SEO & Analytics --}}
                <div x-show="tab === 'seo'">
                    <div class="sa-site-tab-panel">
                    <x-sa.card padding="22px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 16px">SEO & Metadados</h3>
                        <div style="display:flex;flex-direction:column;gap:12px">
                            <div>
                                <label class="sa-site-label">Título da página (meta title)</label>
                                <input type="text" class="sa-site-field" x-model="site.meta_title">
                                <div class="sa-site-helper" x-text="(site.meta_title || '').length + '/60 caracteres — ideal entre 50–60'"></div>
                            </div>
                            <div>
                                <label class="sa-site-label">Descrição (meta description)</label>
                                <textarea rows="2" class="sa-site-field" x-model="site.meta_desc" style="resize:vertical"></textarea>
                                <div class="sa-site-helper" x-text="(site.meta_desc || '').length + '/160 caracteres — ideal entre 140–160'"></div>
                            </div>
                            <div>
                                <label class="sa-site-label">Palavras-chave (separadas por vírgula)</label>
                                <input type="text" class="sa-site-field" x-model="site.keywords">
                            </div>
                        </div>
                    </x-sa.card>

                    <x-sa.card padding="22px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 16px">Imagem de Compartilhamento (OG Image)</h3>
                        <input type="file" x-ref="ogInput" accept="image/jpeg,image/png,image/webp" style="display:none" @change="handleOgFile($event)">

                        <div x-show="ogUrl" x-cloak style="margin-bottom:14px">
                            <div style="position:relative;border-radius:10px;overflow:hidden;border:1px solid var(--sa-border)">
                                <img :src="ogUrl" alt="OG Image atual" style="width:100%;max-height:140px;object-fit:cover;display:block">
                                <button type="button" @click="triggerOgUpload()"
                                    style="position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);color:#fff;transition:background 150ms"
                                    onmouseover="this.style.background='rgba(0,0,0,.85)'"
                                    onmouseout="this.style.background='rgba(0,0,0,.6)'"
                                    title="Trocar OG Image">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </button>
                            </div>
                        </div>

                        <div x-show="!ogUrl" class="sa-site-dropzone sa-site-dropzone--sm" @click="triggerOgUpload()">
                            <div x-show="!uploadingOg">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;margin:0 auto 8px;opacity:.4">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                <div style="font-size:13px;color:var(--sa-text3)">Recomendado: 1200×630px · JPG, PNG ou WebP · Máx. 5MB</div>
                            </div>
                            <div x-show="uploadingOg" x-cloak style="font-size:13px;color:var(--sa-text3)">Enviando…</div>
                        </div>
                    </x-sa.card>

                    <x-sa.card padding="22px">
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 16px">Analytics & Rastreamento</h3>
                        <div style="display:flex;flex-direction:column;gap:12px">
                            <div>
                                <label class="sa-site-label">Google Analytics (ID de medição)</label>
                                <input type="text" class="sa-site-field" x-model="site.google_analytics" placeholder="G-XXXXXXXXXX">
                            </div>
                            <div style="padding:10px 14px;background:var(--sa-surface2);border-radius:9px;border:1px solid var(--sa-border)">
                                <div style="font-size:12px;color:var(--sa-text3);line-height:1.6">⚡ Analytics leve integrado (sem cookies, sem PII) — ativo por padrão em todos os planos</div>
                            </div>
                        </div>
                    </x-sa.card>
                    </div>
                </div>

            </div>
        </div>
    </x-sa.body>
</x-sa.page>
@endsection
