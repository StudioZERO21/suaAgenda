@extends('layouts.app')
@section('title', 'Portfólio')

@section('content')
<x-sa.page x-data="portfolioApp()">
    <x-sa.app-header title="Portfólio" subtitle="Gerencie as fotos dos trabalhos realizados">
        <x-slot:actions>
            <div style="display:flex;gap:8px">
                <x-sa.btn variant="secondary" @click="showUpload = !showUpload"
                          :icon="'<svg width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><line x1=\'12\' y1=\'19\' x2=\'12\' y2=\'5\'/><polyline points=\'5 12 12 5 19 12\'/></svg>'">
                    <span x-text="showUpload ? 'Fechar upload' : 'Fazer upload'"></span>
                </x-sa.btn>
                <x-sa.btn @click="publish()"
                          :icon="'<svg width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><circle cx=\'12\' cy=\'12\' r=\'10\'/><line x1=\'2\' y1=\'12\' x2=\'22\' y2=\'12\'/><path d=\'M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z\'/></svg>'">
                    Publicar na página
                </x-sa.btn>
            </div>
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body>
        <div class="sa-grid-4" style="margin-bottom:20px">
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Total de fotos</div>
                <div class="sa-tint-card__value" x-text="photos.length"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Destaques</div>
                <div class="sa-tint-card__value" x-text="featuredCount"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Categorias</div>
                <div class="sa-tint-card__value" x-text="categorias.length - 1"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Profissionais</div>
                <div class="sa-tint-card__value" x-text="profissionais.length"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
            </div>
        </div>

        <div x-show="showUpload" x-cloak style="margin-bottom:20px">
            <x-sa.card style="padding:20px">
                <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 16px">Adicionar Fotos</h3>
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div
                        @dragover.prevent="drag = true"
                        @dragleave.prevent="drag = false"
                        @drop.prevent="handleFileDrop($event)"
                        @click="$refs.fileInput.click()"
                        :style="'border:2px dashed ' + (drag ? 'var(--sa-primary)' : 'var(--sa-border)') + ';border-radius:12px;padding:32px 20px;text-align:center;background:' + (drag ? 'color-mix(in srgb,var(--sa-primary) 5%,transparent)' : 'var(--sa-surface2)') + ';transition:all 200ms;cursor:pointer'">
                        <div style="width:48px;height:48px;border-radius:50%;background:color-mix(in srgb,var(--sa-primary) 10%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                        </div>
                        <p style="font-size:14px;font-weight:600;color:var(--sa-text1);margin:0 0 4px">Arraste fotos aqui</p>
                        <p style="font-size:12px;color:var(--sa-text3);margin:0 0 12px">ou clique para selecionar</p>
                        <input type="file" accept="image/jpeg,image/png,image/webp" multiple x-ref="fileInput"
                               @change="uploadFiles($event.target.files)" style="display:none">
                        <x-sa.btn variant="muted" size="sm" @click.stop="$refs.fileInput.click()"
                                  :icon="'<svg width=\'13\' height=\'13\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><line x1=\'12\' y1=\'19\' x2=\'12\' y2=\'5\'/><polyline points=\'5 12 12 5 19 12\'/></svg>'">
                            Selecionar arquivos
                        </x-sa.btn>
                        <p style="font-size:11px;color:var(--sa-text3);margin-top:8px;margin-bottom:0">JPG, PNG, WebP · Máx. 10MB cada · Múltiplos arquivos</p>
                    </div>
                    <div style="padding:16px;background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border)">
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:12px">Adicionar foto de demonstração</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                            <div>
                                <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6px">Título</label>
                                <input type="text" x-model="uploadTitle" placeholder="Ex: Degradê moderno" class="sa-search-input" style="max-width:none;width:100%">
                            </div>
                            <div>
                                <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6px">Categoria</label>
                                <select x-model="uploadCategory" style="width:100%;font-size:13px;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);cursor:pointer;outline:none">
                                    <template x-for="cat in categorias.slice(1)" :key="cat">
                                        <option :value="cat" x-text="cat"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr auto;gap:10px;align-items:flex-end">
                            <div>
                                <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6px">Profissional</label>
                                <select x-model="uploadProfId" style="width:100%;font-size:13px;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);cursor:pointer;outline:none">
                                    <template x-for="p in profissionais" :key="p.id">
                                        <option :value="String(p.id)" x-text="p.nome"></option>
                                    </template>
                                </select>
                            </div>
                            <x-sa.btn @click="addPhoto()"
                                      :icon="'<svg width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><line x1=\'12\' y1=\'5\' x2=\'12\' y2=\'19\'/><line x1=\'5\' y1=\'12\' x2=\'19\' y2=\'12\'/></svg>'">
                                Adicionar
                            </x-sa.btn>
                        </div>
                    </div>
                </div>
            </x-sa.card>
        </div>

        <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
            <div style="position:relative;flex:1;max-width:280px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" x-model="search" placeholder="Buscar fotos..." class="sa-search-input" style="max-width:none;width:100%">
            </div>
            <div style="display:flex;gap:6px;overflow-x:auto">
                <template x-for="cat in categorias" :key="cat">
                    <button type="button" @click="category = cat"
                            :style="'padding:6px 14px;border-radius:20px;border:1.5px solid ' + (category === cat ? 'var(--sa-primary)' : 'var(--sa-border)') + ';background:' + (category === cat ? 'var(--sa-primary)' : 'var(--sa-surface)') + ';color:' + (category === cat ? '#fff' : 'var(--sa-text2)') + ';font-size:12px;font-weight:' + (category === cat ? '700' : '400') + ';cursor:pointer;font-family:var(--sa-font-body);white-space:nowrap;transition:all 160ms'">
                        <span x-text="cat"></span>
                    </button>
                </template>
            </div>
            <select x-model="profFilter" style="font-size:13px;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);cursor:pointer;outline:none">
                <option value="all">Todos os profissionais</option>
                <template x-for="p in profissionais" :key="p.id">
                    <option :value="String(p.id)" x-text="p.nome"></option>
                </template>
            </select>
            <span style="font-size:12px;color:var(--sa-text3);margin-left:auto;flex-shrink:0" x-text="filtered.length + ' foto' + (filtered.length !== 1 ? 's' : '')"></span>
        </div>

        <template x-if="filtered.length === 0">
            <div style="text-align:center;padding:60px;color:var(--sa-text3)">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:.3"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <div style="font-size:14px">Nenhuma foto encontrada para este filtro</div>
            </div>
        </template>
        <div x-show="filtered.length > 0" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
            <template x-for="photo in filtered" :key="photo.id">
                <div style="position:relative;border-radius:12px;overflow:hidden;cursor:pointer;background:var(--sa-surface2);border:1px solid var(--sa-border);aspect-ratio:4/3"
                     @click="openPhoto(photo)"
                     @mouseenter="$el.querySelector('.photo-overlay').style.opacity = '1'"
                     @mouseleave="$el.querySelector('.photo-overlay').style.opacity = '0'">
                    {{-- Imagem real quando disponível --}}
                    <template x-if="photo.imagem_url">
                        <img :src="photo.imagem_url" :alt="photo.titulo" loading="lazy" decoding="async"
                             style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                    </template>
                    {{-- Placeholder quando sem imagem --}}
                    <template x-if="!photo.imagem_url">
                        <div style="position:absolute;inset:0">
                            <svg width="100%" height="100%" style="position:absolute;inset:0">
                                <defs>
                                    <pattern :id="'ph-' + photo.id" patternUnits="userSpaceOnUse" width="20" height="20" patternTransform="rotate(45)">
                                        <rect width="20" height="20" fill="var(--sa-surface2)"/>
                                        <rect width="10" height="20" :fill="profColor(photo) + '08'"/>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" :fill="'url(#ph-' + photo.id + ')'"/>
                            </svg>
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:.25">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" :stroke="profColor(photo)" stroke-width="1.3">
                                    <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/>
                                </svg>
                                <span style="font-family:monospace;font-size:9px;margin-top:4px" :style="'color:' + profColor(photo)" x-text="'foto-' + photo.id + '.jpg'"></span>
                            </div>
                        </div>
                    </template>
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7) 0%,transparent 60%);pointer-events:none"></div>
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:8px 10px">
                        <div style="font-size:11px;font-weight:700;color:#fff;margin-bottom:2px;text-shadow:0 1px 2px rgba(0,0,0,.6)" x-text="photo.titulo"></div>
                        <div style="font-size:10px;color:rgba(255,255,255,.6)" x-text="photo.prof + ' · ' + photo.categoria"></div>
                    </div>
                    <div x-show="photo.destaque" style="position:absolute;top:8px;left:8px;background:var(--sa-secondary);border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;color:#fff">★ Destaque</div>
                    <div class="photo-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,.45);opacity:0;transition:opacity 180ms;display:flex;align-items:center;justify-content:center;pointer-events:none">
                        <div style="font-size:12px;color:#fff;font-weight:600">Ver detalhes</div>
                    </div>
                </div>
            </template>
        </div>
    </x-sa.body>

    <x-sa.modal open="photoModalOpen" size="md">
        <x-slot:title><span x-text="selPhoto?.titulo || ''"></span></x-slot:title>
        <x-slot:subtitle><span x-text="selPhoto ? selPhoto.prof + ' · ' + selPhoto.categoria : ''"></span></x-slot:subtitle>
        <x-slot:footer>
            <x-sa.btn variant="danger" size="sm" @click="deletePhoto(selPhoto.id); closePhoto()"
                      :icon="'<svg width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><polyline points=\'3 6 5 6 21 6\'/><path d=\'M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6\'/></svg>'">
                Excluir
            </x-sa.btn>
            <x-sa.btn variant="secondary" size="sm" @click="toggleFeatured(selPhoto.id); closePhoto()"
                      :icon="'<svg width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><polygon points=\'12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2\'/></svg>'">
                <span x-text="selPhoto?.destaque ? 'Remover destaque' : 'Marcar como destaque'"></span>
            </x-sa.btn>
            <x-sa.btn size="sm" @click="closePhoto()">Fechar</x-sa.btn>
        </x-slot:footer>

        <template x-if="selPhoto">
            <div>
                <div style="width:100%;border-radius:12px;overflow:hidden;margin-bottom:16px;aspect-ratio:16/10;position:relative;background:#111;border:1px solid var(--sa-border)">
                    <template x-if="selPhoto.imagem_url">
                        <img :src="selPhoto.imagem_url" :alt="selPhoto.titulo" loading="lazy" decoding="async"
                             style="width:100%;height:100%;object-fit:contain">
                    </template>
                    <template x-if="!selPhoto.imagem_url">
                        <div style="position:absolute;inset:0">
                            <svg width="100%" height="100%">
                                <defs>
                                    <pattern :id="'phm-' + selPhoto.id" patternUnits="userSpaceOnUse" width="20" height="20" patternTransform="rotate(45)">
                                        <rect width="20" height="20" fill="var(--sa-surface2)"/>
                                        <rect width="10" height="20" :fill="profColor(selPhoto) + '10'"/>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" :fill="'url(#phm-' + selPhoto.id + ')'"/>
                            </svg>
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:.2">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" :stroke="profColor(selPhoto)" stroke-width="1">
                                    <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/>
                                </svg>
                                <div style="font-family:monospace;font-size:12px;margin-top:8px" :style="'color:' + profColor(selPhoto)" x-text="'foto-' + selPhoto.id + '.jpg'"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="selPhoto.destaque" style="position:absolute;top:12px;left:12px;background:var(--sa-secondary);border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700;color:#fff">★ Destaque</div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div style="background:var(--sa-surface2);border-radius:8px;padding:10px 14px">
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Profissional</div>
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1);margin-top:3px" x-text="selPhoto.prof"></div>
                    </div>
                    <div style="background:var(--sa-surface2);border-radius:8px;padding:10px 14px">
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Categoria</div>
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1);margin-top:3px" x-text="selPhoto.categoria"></div>
                    </div>
                    <div style="background:var(--sa-surface2);border-radius:8px;padding:10px 14px">
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Data</div>
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1);margin-top:3px" x-text="formatDate(selPhoto.data)"></div>
                    </div>
                    <div style="background:var(--sa-surface2);border-radius:8px;padding:10px 14px">
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Tags</div>
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1);margin-top:3px" x-text="selPhoto.tags?.length ? selPhoto.tags.join(', ') : '—'"></div>
                    </div>
                </div>
            </div>
        </template>
    </x-sa.modal>
</x-sa.page>

@push('scripts')
<script>
function portfolioApp() {
    return {
        photos: @json($fotosJson),
        categorias: @json($categorias),
        profissionais: @json($profissionais),
        category: 'Todos',
        profFilter: 'all',
        search: '',
        selPhoto: null,
        photoModalOpen: false,
        showUpload: false,
        drag: false,
        uploadTitle: '',
        uploadCategory: 'Corte',
        uploadProfId: '',

        init() {
            this.$watch('photoModalOpen', val => { if (!val) this.selPhoto = null; });
            if (this.profissionais.length) this.uploadProfId = String(this.profissionais[0].id);
        },

        get featuredCount() {
            return this.photos.filter(p => p.destaque).length;
        },

        get filtered() {
            const q = this.search.toLowerCase();
            return this.photos.filter(p => {
                if (this.category !== 'Todos' && p.categoria !== this.category) return false;
                if (this.profFilter !== 'all' && String(p.prof_id) !== this.profFilter) return false;
                if (q && !p.titulo.toLowerCase().includes(q) && !p.prof.toLowerCase().includes(q)) return false;
                return true;
            });
        },

        profColor(photo) {
            return photo?.cor || '#888';
        },

        formatDate(date) {
            if (!date) return '—';
            const [y, m, d] = date.split('-');
            return `${d}/${m}/${y}`;
        },

        toast(text, icon = 'success') {
            Swal.fire({ toast: true, position: 'top-end', icon, title: text, showConfirmButton: false, timer: 2800, timerProgressBar: true });
        },

        openPhoto(photo) {
            this.selPhoto = photo;
            this.photoModalOpen = true;
        },

        closePhoto() {
            this.photoModalOpen = false;
            this.selPhoto = null;
        },

        handleFileDrop(e) {
            this.drag = false;
            const files = e.dataTransfer?.files;
            if (files?.length) this.uploadFiles(files);
        },

        async uploadFiles(files) {
            if (!files?.length) return;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            let uploaded = 0;
            for (const file of files) {
                if (!file.type.startsWith('image/')) continue;
                if (file.size > 10 * 1024 * 1024) {
                    this.toast(`${file.name}: arquivo maior que 10MB`, 'error');
                    continue;
                }
                const fd = new FormData();
                fd.append('arquivo', file);
                fd.append('titulo', file.name.replace(/\.[^.]+$/, ''));
                fd.append('categoria', this.uploadCategory);
                if (this.uploadProfId) fd.append('profissional_id', this.uploadProfId);

                const res = await fetch('{{ route('portfolio.fotos.store') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    body: fd,
                });
                if (!res.ok) {
                    this.toast(`Erro ao enviar ${file.name}`, 'error');
                    continue;
                }
                const data = await res.json();
                this.photos.unshift(data);
                uploaded++;
            }
            if (uploaded > 0) {
                this.toast(`${uploaded} foto${uploaded > 1 ? 's' : ''} adicionada${uploaded > 1 ? 's' : ''}!`, 'success');
            }
        },

        async addPhoto() {
            if (!this.uploadTitle.trim()) {
                return this.toast('Adicione um título para a foto', 'error');
            }
            const res = await fetch('{{ route('portfolio.fotos.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({
                    titulo: this.uploadTitle.trim(),
                    categoria: this.uploadCategory,
                    profissional_id: this.uploadProfId || null,
                }),
            });
            if (!res.ok) {
                return this.toast('Erro ao adicionar foto', 'error');
            }
            const data = await res.json();
            this.photos.unshift(data);
            this.uploadTitle = '';
            this.toast('Foto adicionada ao portfólio!', 'success');
        },

        async deletePhoto(id) {
            const res = await fetch(`/portfolio/fotos/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            });
            if (res.ok || res.status === 204) {
                this.photos = this.photos.filter(p => p.id !== id);
                this.toast('Foto removida', 'error');
            }
        },

        async toggleFeatured(id) {
            const res = await fetch(`/portfolio/fotos/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            });
            if (res.ok) {
                const data = await res.json();
                this.photos = this.photos.map(p => p.id === id ? { ...p, destaque: data.destaque } : p);
                const msg = data.destaque ? 'Marcado como destaque!' : 'Destaque removido';
                this.toast(msg, 'success');
            }
        },

        publish() {
            this.toast('Portfólio público atualizado!', 'success');
        },
    };
}
</script>
@endpush
@endsection
