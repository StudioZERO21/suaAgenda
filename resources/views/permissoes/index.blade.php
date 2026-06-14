@extends('layouts.app')
@section('title', 'Permissões')

@section('content')
<x-sa.page x-data="permissionsApp()">
    <x-sa.app-header title="Permissões & Acesso" subtitle="Gerencie grupos ACL e atribuições por cargo">
        <x-slot:actions>
            <x-sa.btn @click="openGroupModal(null)"
                      :icon="view('components.sa.icons.plus')->render()">
                Novo Grupo
            </x-sa.btn>
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body padding="16px 32px 0">
        <div style="display:flex;gap:24px">
            {{-- Vertical tabs (PermissionsScreen.jsx — classes CSS, não :style inline) --}}
            <nav style="width:180px;flex-shrink:0" aria-label="Seções de permissões">
                <button type="button" class="sa-vtab" :class="{ 'sa-vtab--active': tab === 'matrix' }" @click="tab = 'matrix'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Matriz de Acesso
                </button>
                <button type="button" class="sa-vtab" :class="{ 'sa-vtab--active': tab === 'groups' }" @click="tab = 'groups'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/></svg>
                    Grupos ACL
                </button>
                <button type="button" class="sa-vtab" :class="{ 'sa-vtab--active': tab === 'roles' }" @click="tab = 'roles'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Cargos & Grupos
                </button>
                <button type="button" class="sa-vtab" :class="{ 'sa-vtab--active': tab === 'users' }" @click="tab = 'users'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    Usuários & Funções
                </button>
            </nav>

            <div style="flex:1;min-width:0">

                {{-- MATRIX TAB --}}
                <div x-show="tab === 'matrix'" x-cloak>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                        Visão completa de permissões por cargo. As colunas são os cargos cadastrados na empresa. Clique em ✦ para editar via grupos ACL.
                    </p>
                    <div class="sa-perm-matrix-wrap">
                        <table class="sa-perm-matrix">
                            <thead>
                                <tr>
                                    <th>Permissão</th>
                                    <template x-for="role in cargos" :key="role.id">
                                        <th>
                                            <div style="display:flex;flex-direction:column;align-items:center;gap:3px">
                                                <div style="display:flex;align-items:center;gap:5px">
                                                    <div style="width:8px;height:8px;border-radius:50%" :style="'background:' + role.cor"></div>
                                                    <span x-text="role.nome"></span>
                                                </div>
                                                <span x-show="getGroupForRole(role.id)"
                                                      class="sa-grupo-badge"
                                                      :style="grupoBadgeStyle(getGroupForRole(role.id)?.cor)"
                                                      x-text="getGroupForRole(role.id)?.nome"></span>
                                                <button type="button" @click.stop="openAssign(role)" style="font-size:9px;color:var(--sa-secondary);background:none;border:none;cursor:pointer;font-weight:600;padding:0">✦ Editar grupo</button>
                                            </div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <template x-for="[cat, perms] in catalogoEntries" :key="cat">
                                <tbody>
                                    <tr class="sa-perm-cat-row" @click="toggleCatExpand(cat)">
                                        <td :colspan="cargos.length + 1">
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="isCatExpanded(cat) ? '' : 'transform:rotate(-90deg)'"><polyline points="6 9 12 15 18 9"/></svg>
                                                <span x-text="cat"></span>
                                                <span style="font-size:11px;color:var(--sa-text3);font-weight:400" x-text="'(' + perms.length + ' permissões)'"></span>
                                            </div>
                                        </td>
                                    </tr>
                                    <template x-for="perm in perms" :key="perm.id">
                                        <tr class="sa-tr sa-perm-row" x-show="isCatExpanded(cat)">
                                            <td x-text="perm.label"></td>
                                            <template x-for="role in cargos" :key="role.id + '-' + perm.id">
                                                <td>
                                                    <div class="sa-perm-check"
                                                         :style="'background:' + (hasPerm(role.id, perm.id) ? role.cor + '18' : 'transparent') + ';border:1px solid ' + (hasPerm(role.id, perm.id) ? role.cor : 'var(--sa-border)')">
                                                        <svg x-show="hasPerm(role.id, perm.id)" width="11" height="11" viewBox="0 0 24 24" fill="none" :stroke="role.cor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                        <span x-show="!hasPerm(role.id, perm.id)" style="font-size:10px;color:var(--sa-text3);opacity:.4">—</span>
                                                    </div>
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </template>
                        </table>
                    </div>
                </div>

                {{-- GROUPS TAB --}}
                <div x-show="tab === 'groups'" x-cloak>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                        Grupos ACL agrupam permissões e são atribuídos a cargos. Crie grupos personalizados para necessidades específicas.
                    </p>
                    <div class="sa-acl-groups-grid">
                        <template x-for="g in grupos" :key="g.id">
                            <div class="sa-acl-group-card">
                                <div class="sa-acl-group-card__strip" :style="'background:' + g.cor"></div>
                                <div class="sa-acl-group-card__body">
                                    <div class="sa-acl-group-card__head">
                                        <div class="sa-acl-group-card__title" x-text="g.nome"></div>
                                        <span class="sa-grupo-badge sa-grupo-badge--md"
                                              :style="grupoBadgeStyle(g.cor)"
                                              x-text="g.perms.length + ' perms.'"></span>
                                    </div>
                                    <p class="sa-acl-group-card__desc" x-text="g.descricao"></p>
                                    <div class="sa-acl-group-card__perms">
                                        <template x-for="row in groupCatalogPreview(g)" :key="g.id + '-' + row.cat">
                                            <div class="sa-acl-group-card__perm-row">
                                                <span x-text="row.cat"></span>
                                                <span :style="'color:' + g.cor" x-text="row.count + '/' + row.total"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="sa-acl-group-card__roles" x-show="assignedRoles(g.id).length > 0">
                                        <div class="sa-acl-group-card__roles-label">Cargos com este grupo</div>
                                        <div class="sa-acl-group-card__roles-list">
                                            <template x-for="r in assignedRoles(g.id)" :key="r.id">
                                                <span class="sa-acl-group-card__role-tag"
                                                      :style="'background:' + r.cor + '15;color:' + r.cor"
                                                      x-text="r.nome"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="sa-acl-group-card__footer">
                                    <x-sa.btn size="sm" variant="muted" @click="openGroupModal(g)"
                                              :icon="'<svg width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7\'/><path d=\'M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z\'/></svg>'">
                                        Editar
                                    </x-sa.btn>
                                    <x-sa.btn size="sm" variant="ghost" @click="deleteGroup(g.id)"
                                              :icon="'<svg width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><polyline points=\'3 6 5 6 21 6\'/><path d=\'M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6\'/></svg>'">
                                        &nbsp;
                                    </x-sa.btn>
                                </div>
                            </div>
                        </template>
                        <button type="button" class="sa-acl-group-add" @click="openGroupModal(null)">
                            <div class="sa-acl-group-add__icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                            <div class="sa-acl-group-add__label">Novo Grupo ACL</div>
                        </button>
                    </div>
                </div>

                {{-- ROLES TAB --}}
                <div x-show="tab === 'roles'" x-cloak>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                        Atribua um grupo de acesso ACL para cada cargo da empresa. Os cargos são gerenciados em <strong>Cargos</strong>.
                    </p>
                    <div class="sa-role-assign-list">
                        <template x-for="role in cargos" :key="role.id">
                            <div class="sa-role-assign-row">
                                <div class="sa-role-assign-row__icon" :style="'background:' + role.cor">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                                <div class="sa-role-assign-row__role">
                                    <div class="sa-role-assign-row__name" x-text="role.nome"></div>
                                    <div class="sa-role-assign-row__nivel" x-text="role.nivel"></div>
                                </div>
                                <svg class="sa-role-assign-row__arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                <div class="sa-role-assign-row__group">
                                    <div class="sa-role-assign-row__group-inner" x-show="getGroupForRole(role.id)">
                                        <div class="sa-role-assign-row__group-dot" :style="'background:' + roleBarColor(role.id)"></div>
                                        <div>
                                            <div class="sa-role-assign-row__group-name" x-text="getGroupForRole(role.id)?.nome"></div>
                                            <div class="sa-role-assign-row__group-meta" x-text="rolePermCount(role.id) + ' / ' + totalPerms + ' permissões'"></div>
                                        </div>
                                    </div>
                                    <div class="sa-role-assign-row__empty" x-show="!getGroupForRole(role.id)">Sem grupo atribuído — sem acesso</div>
                                </div>
                                <div class="sa-role-assign-row__bar-wrap">
                                    <div class="sa-role-assign-row__bar-track">
                                        <div class="sa-role-assign-row__bar-fill" :style="roleBarStyle(role.id)"></div>
                                    </div>
                                    <div class="sa-role-assign-row__bar-pct" x-text="rolePermPct(role.id) + '%'"></div>
                                </div>
                                <x-sa.btn size="sm" variant="muted" @click="openAssign(role)"
                                          :icon="'<svg width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7\'/><path d=\'M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z\'/></svg>'">
                                    Alterar
                                </x-sa.btn>
                            </div>
                        </template>
                    </div>

                    <div class="sa-acl-hint">
                        <div class="sa-acl-hint__title">💡 Como funciona o ACL</div>
                        <p class="sa-acl-hint__text">
                            Cada <strong>Cargo</strong> recebe um <strong>Grupo de Acesso ACL</strong>. O grupo define quais ações o cargo pode realizar.
                            Ao alterar o grupo de um cargo, todos os funcionários com esse cargo são afetados imediatamente.
                            Crie grupos personalizados na aba <strong>Grupos ACL</strong> para cenários específicos.
                        </p>
                    </div>
                </div>

                {{-- USERS TAB --}}
                <div x-show="tab === 'users'" x-cloak>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                        Funcionários com acesso ao painel. Atribua um ou mais grupos ACL diretamente ou use o grupo do cargo vinculado.
                    </p>
                    <div class="sa-funcionario-list">
                        <template x-for="f in funcionarios" :key="f.id">
                            <div class="sa-funcionario-card">
                                <div class="sa-funcionario-card__head">
                                    <div class="sa-funcionario-card__avatar" x-text="(f.name || '?').charAt(0).toUpperCase()"></div>
                                    <div class="sa-funcionario-card__info">
                                        <div class="sa-funcionario-card__name" x-text="f.name"></div>
                                        <div class="sa-funcionario-card__email" x-text="f.email"></div>
                                    </div>
                                    <div class="sa-funcionario-card__badges">
                                        <span x-show="f.funcao"
                                              class="sa-funcionario-card__badge"
                                              style="background:color-mix(in srgb,var(--sa-primary) 10%,transparent);color:var(--sa-primary)"
                                              x-text="f.funcao"></span>
                                        <span x-show="f.acl_manual"
                                              class="sa-funcionario-card__badge"
                                              style="background:rgba(99,102,241,.12);color:#6366f1">Grupos manuais</span>
                                        <span x-show="!f.ativo"
                                              class="sa-funcionario-card__badge"
                                              style="background:rgba(107,114,128,.12);color:#6b7280">Inativo</span>
                                    </div>
                                    <x-sa.btn x-show="f.funcao_slug !== 'admin_empresa'" size="sm" variant="muted" @click="openUserGrupos(f)"
                                              :icon="'<svg width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7\'/><path d=\'M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z\'/></svg>'">
                                        Grupos
                                    </x-sa.btn>
                                </div>
                                <div class="sa-funcionario-card__meta">
                                    <div class="sa-funcionario-card__meta-item">
                                        <span class="sa-funcionario-card__meta-label">Grupo ACL</span>
                                        <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                                            <template x-for="g in (f.grupos.length ? f.grupos : [f.grupo])" :key="f.id + '-g-' + (g.id || g.nome)">
                                                <span class="sa-grupo-badge sa-grupo-badge--md"
                                                      :style="grupoBadgeStyle(g.cor || f.grupo.cor)"
                                                      x-text="g.nome"></span>
                                            </template>
                                            <span class="sa-funcionario-card__meta-value"
                                                  style="font-size:12px;color:var(--sa-text3)"
                                                  x-text="'(' + f.grupo.perms.length + '/' + totalPerms + ')'"></span>
                                        </div>
                                    </div>
                                    <div class="sa-funcionario-card__meta-item" x-show="f.cargo">
                                        <span class="sa-funcionario-card__meta-label">Cargo</span>
                                        <span class="sa-funcionario-card__meta-value" :style="'color:' + f.cargo.cor" x-text="f.cargo.nome"></span>
                                    </div>
                                    <div class="sa-funcionario-card__meta-item" x-show="f.cargo_grupo && !f.acl_manual">
                                        <span class="sa-funcionario-card__meta-label">Via cargo</span>
                                        <span class="sa-grupo-badge sa-grupo-badge--md"
                                              :style="grupoBadgeStyle(f.cargo_grupo.cor)"
                                              x-text="f.cargo_grupo.nome"></span>
                                    </div>
                                    <div class="sa-funcionario-card__meta-item" x-show="f.profissional">
                                        <span class="sa-funcionario-card__meta-label">Profissional</span>
                                        <span class="sa-funcionario-card__meta-value" x-text="f.profissional.nome"></span>
                                    </div>
                                </div>
                                <div x-show="userPermPreview(f).length > 0">
                                    <div class="sa-funcionario-card__perms-head">Permissões por módulo</div>
                                    <div class="sa-funcionario-card__perms-grid">
                                        <template x-for="row in userPermPreview(f)" :key="f.id + '-' + row.cat">
                                            <div class="sa-funcionario-card__perm-cat">
                                                <div class="sa-funcionario-card__perm-cat-head">
                                                    <span class="sa-funcionario-card__perm-cat-name" x-text="row.cat"></span>
                                                    <span class="sa-funcionario-card__perm-cat-count"
                                                          :style="'color:' + f.grupo.cor"
                                                          x-text="row.count + '/' + row.total"></span>
                                                </div>
                                                <div class="sa-funcionario-card__perm-labels" x-text="row.labels"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p x-show="funcionarios.length === 0" style="font-size:14px;color:var(--sa-text3);text-align:center;padding:32px 0">Nenhum funcionário com acesso ao painel.</p>
                    </div>
                    <div class="sa-acl-hint" style="margin-top:16px">
                        <div class="sa-acl-hint__title">💡 Grupos por funcionário</div>
                        <p class="sa-acl-hint__text">
                            Por padrão, o grupo ACL vem do <strong>cargo</strong> do profissional vinculado.
                            Use <strong>Grupos</strong> para atribuir um ou mais grupos manualmente — nesse modo, alterações no cargo não sobrescrevem a escolha.
                            Clique em <strong>Usar grupo do cargo</strong> no modal para voltar à sincronização automática.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </x-sa.body>

    {{-- GroupModal --}}
    <div x-show="groupModalOpen" x-cloak
         @keydown.escape.window="groupModalOpen = false"
         class="sa-modal-overlay"
         @click.self="groupModalOpen = false">
        <div class="sa-group-modal" @click.stop>
            <div class="sa-group-modal__header">
                <div>
                    <h3 class="sa-group-modal__title" x-text="editGroup ? 'Editar Grupo de Acesso' : 'Novo Grupo de Acesso'"></h3>
                    <p class="sa-group-modal__subtitle">Defina quais permissões fazem parte deste grupo</p>
                </div>
                <button type="button" class="sa-group-modal__close" @click="groupModalOpen = false" aria-label="Fechar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="sa-group-modal__body">
                <div class="sa-group-modal__form">
                    <div class="sa-group-modal__row">
                        <div class="sa-group-modal__field">
                            <label>Nome do grupo <span style="color:var(--sa-secondary)">*</span></label>
                            <input type="text" x-model="groupForm.nome" placeholder="Ex: Profissional Sênior" required>
                        </div>
                        <div class="sa-group-modal__field">
                            <label>Descrição</label>
                            <textarea x-model="groupForm.descricao" rows="1" placeholder="Breve descrição"></textarea>
                        </div>
                    </div>
                    <div class="sa-group-modal__field">
                        <label>Cor</label>
                        <div class="sa-group-modal__colors">
                            <template x-for="c in groupColors" :key="c">
                                <button type="button" class="sa-group-modal__swatch" @click="groupForm.cor = c"
                                        :style="'background:' + c + ';border:' + (groupForm.cor === c ? '3px solid var(--sa-text1)' : '2px solid transparent')"></button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <div class="sa-group-modal__perms-head">
                            <label x-text="'Permissões (' + groupForm.perms.length + '/' + totalPerms + ')'"></label>
                            <x-sa.btn variant="muted" size="sm" @click="toggleAllPerms()">
                                <span x-text="groupForm.perms.length === totalPerms ? 'Desmarcar todas' : 'Selecionar todas'"></span>
                            </x-sa.btn>
                        </div>
                        <div class="sa-group-modal__perms-grid">
                            <template x-for="[cat, perms] in catalogoEntries" :key="'gf-' + cat">
                                <div class="sa-group-modal__cat-card">
                                    <div class="sa-group-modal__cat-head" @click="toggleCatPerms(cat)">
                                        <div class="sa-group-modal__cat-check"
                                             :style="'border:2px solid ' + (isCatAllOn(cat) ? groupForm.cor : 'var(--sa-border)') + ';background:' + (isCatAllOn(cat) ? groupForm.cor : isCatPartial(cat) ? groupForm.cor + '40' : 'transparent')">
                                            <svg x-show="isCatAllOn(cat) || isCatPartial(cat)" width="9" height="9" viewBox="0 0 24 24" fill="none" :stroke="isCatAllOn(cat) ? '#fff' : groupForm.cor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        </div>
                                        <span class="sa-group-modal__cat-title" x-text="cat"></span>
                                    </div>
                                    <template x-for="p in perms" :key="p.id">
                                        <div class="sa-group-modal__perm-row" @click="togglePerm(p.id)">
                                            <div class="sa-group-modal__perm-check"
                                                 :style="'border:1.5px solid ' + (groupForm.perms.includes(p.id) ? groupForm.cor : 'var(--sa-border)') + ';background:' + (groupForm.perms.includes(p.id) ? groupForm.cor : 'transparent')">
                                                <svg x-show="groupForm.perms.includes(p.id)" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            </div>
                                            <span class="sa-group-modal__perm-label" x-text="p.label"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sa-group-modal__footer">
                <x-sa.btn variant="secondary" size="sm" @click="groupModalOpen = false">Cancelar</x-sa.btn>
                <x-sa.btn size="sm" @click="saveGroup()" x-bind:disabled="groupSaving">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                    <span x-text="editGroup ? 'Salvar' : 'Criar Grupo'"></span>
                </x-sa.btn>
            </div>
        </div>
    </div>

    {{-- AssignModal --}}
    <div x-show="assignModalOpen" x-cloak
         @keydown.escape.window="assignModalOpen = false"
         class="sa-modal-overlay"
         @click.self="assignModalOpen = false">
        <div class="sa-assign-modal" @click.stop>
            <div class="sa-assign-modal__header">
                <div>
                    <h3 class="sa-assign-modal__title" x-text="'Atribuir grupo — ' + (assignRole?.nome || '')"></h3>
                    <p class="sa-assign-modal__subtitle">Selecione o grupo de acesso para este cargo</p>
                </div>
                <button type="button" class="sa-assign-modal__close" @click="assignModalOpen = false" aria-label="Fechar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="sa-assign-modal__body">
                <div class="sa-assign-modal__options">
                    <template x-for="g in grupos" :key="'assign-' + g.id">
                        <div class="sa-assign-option" @click="assignSel = String(g.id)"
                             :style="'border-color:' + (isAssignSelected(g.id) ? g.cor : 'var(--sa-border)') + ';background:' + (isAssignSelected(g.id) ? g.cor + '08' : 'var(--sa-surface)')">
                            <div class="sa-assign-option__radio"
                                 :style="'border:2px solid ' + g.cor + ';background:' + (isAssignSelected(g.id) ? g.cor : 'transparent')"></div>
                            <div style="flex:1;min-width:0">
                                <div class="sa-assign-option__name" x-text="g.nome"></div>
                                <div class="sa-assign-option__desc" x-text="g.descricao"></div>
                                <div class="sa-assign-option__count" :style="'color:' + g.cor" x-text="g.perms.length + ' permissões'"></div>
                            </div>
                        </div>
                    </template>
                    <div class="sa-assign-modal__empty" x-show="assignSel === ''">Nenhum grupo = sem acesso ao sistema</div>
                </div>
            </div>
            <div class="sa-assign-modal__footer">
                <x-sa.btn variant="secondary" size="sm" @click="assignModalOpen = false">Cancelar</x-sa.btn>
                <x-sa.btn size="sm" @click="doAssign()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                    Atribuir
                </x-sa.btn>
            </div>
        </div>
    </div>

    {{-- UserGruposModal --}}
    <div x-show="userGruposModalOpen" x-cloak
         @keydown.escape.window="userGruposModalOpen = false"
         class="sa-modal-overlay"
         @click.self="userGruposModalOpen = false">
        <div class="sa-assign-modal" @click.stop>
            <div class="sa-assign-modal__header">
                <div>
                    <h3 class="sa-assign-modal__title" x-text="'Grupos ACL — ' + (editUser?.name || '')"></h3>
                    <p class="sa-assign-modal__subtitle">Selecione um ou mais grupos de acesso para este funcionário</p>
                </div>
                <button type="button" class="sa-assign-modal__close" @click="userGruposModalOpen = false" aria-label="Fechar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="sa-assign-modal__body">
                <div class="sa-assign-modal__options">
                    <template x-for="g in grupos" :key="'ug-' + g.id">
                        <div class="sa-assign-option" @click="toggleUserGrupo(g.id)"
                             :style="'border-color:' + (isUserGrupoSelected(g.id) ? g.cor : 'var(--sa-border)') + ';background:' + (isUserGrupoSelected(g.id) ? g.cor + '08' : 'var(--sa-surface)')">
                            <div class="sa-assign-option__check"
                                 :style="'border:2px solid ' + g.cor + ';background:' + (isUserGrupoSelected(g.id) ? g.cor : 'transparent')">
                                <svg x-show="isUserGrupoSelected(g.id)" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div class="sa-assign-option__name" x-text="g.nome"></div>
                                <div class="sa-assign-option__desc" x-text="g.descricao"></div>
                                <div class="sa-assign-option__count" :style="'color:' + g.cor" x-text="g.perms.length + ' permissões'"></div>
                            </div>
                        </div>
                    </template>
                    <div class="sa-assign-modal__empty" x-show="userGruposSel.length === 0">Nenhum grupo = sem acesso ACL (permissões da função global, se houver)</div>
                </div>
                <div x-show="editUser?.cargo_grupo" style="margin-top:12px;padding:12px;background:var(--sa-surface2);border-radius:8px;border:1px solid var(--sa-border)">
                    <div style="font-size:12px;color:var(--sa-text3);margin-bottom:6px">Grupo sugerido pelo cargo</div>
                    <span class="sa-grupo-badge sa-grupo-badge--md"
                          :style="grupoBadgeStyle(editUser?.cargo_grupo?.cor)"
                          x-text="editUser?.cargo_grupo?.nome"></span>
                </div>
            </div>
            <div class="sa-assign-modal__footer" style="justify-content:space-between">
                <x-sa.btn variant="muted" size="sm" @click="syncUserGruposFromCargo()" x-show="editUser?.cargo_grupo">
                    Usar grupo do cargo
                </x-sa.btn>
                <div style="display:flex;gap:8px;margin-left:auto">
                    <x-sa.btn variant="secondary" size="sm" @click="userGruposModalOpen = false">Cancelar</x-sa.btn>
                    <x-sa.btn size="sm" @click="saveUserGrupos()" x-bind:disabled="userGruposSaving">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                        Salvar
                    </x-sa.btn>
                </div>
            </div>
        </div>
    </div>
</x-sa.page>

@push('scripts')
<script>
function permissionsApp() {
    const catalogo = @json($catalogo);
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const jsonHeaders = () => ({ 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() });

    const blankGroup = () => ({ nome: '', cor: '#6366f1', descricao: '', perms: [] });

    return {
        tab: 'matrix',
        catalogo,
        grupos: @json($gruposJson),
        cargos: @json($cargosJson),
        roleGroups: @json($roleGroupsJson),
        funcionarios: @json($funcionariosJson),
        expandedCat: {},
        groupModalOpen: false,
        assignModalOpen: false,
        userGruposModalOpen: false,
        editGroup: null,
        assignRole: null,
        editUser: null,
        assignSel: '',
        userGruposSel: [],
        userGruposSaving: false,
        groupForm: blankGroup(),
        groupSaving: false,
        groupColors: ['#ef4444', '#f59e0b', '#10b981', '#6366f1', '#ec4899', '#0ea5e9', '#1a1a1a', '#d4a574'],

        get catalogoEntries() {
            return Object.entries(this.catalogo);
        },

        get totalPerms() {
            return Object.values(this.catalogo).flat().length;
        },

        get allPermIds() {
            return Object.values(this.catalogo).flat().map(p => p.id);
        },

        grupoBadgeStyle(cor) {
            if (!cor) {
                return '';
            }
            return '--grupo-badge-color:' + cor + ';--grupo-badge-bg:' + cor + '15';
        },

        getGroupId(roleId) {
            const key = String(roleId);
            return this.roleGroups[key] ?? this.roleGroups[roleId] ?? '';
        },

        getGroupForRole(roleId) {
            const gId = this.getGroupId(roleId);
            if (!gId) {
                return null;
            }
            return this.grupos.find(g => String(g.id) === String(gId)) || null;
        },

        hasPerm(roleId, permId) {
            const grp = this.getGroupForRole(roleId);
            return grp?.perms?.includes(permId) || false;
        },

        isCatExpanded(cat) {
            return this.expandedCat[cat] !== false;
        },

        toggleCatExpand(cat) {
            this.expandedCat = { ...this.expandedCat, [cat]: !this.expandedCat[cat] };
        },

        assignedRoles(groupId) {
            return this.cargos.filter(r => String(this.getGroupId(r.id)) === String(groupId));
        },

        catPermCount(g, cat) {
            const ids = this.catalogo[cat].map(p => p.id);
            return ids.filter(id => g.perms.includes(id)).length;
        },

        groupCatalogPreview(g) {
            return this.catalogoEntries
                .map(([cat, perms]) => {
                    const count = this.catPermCount(g, cat);
                    return count > 0 ? { cat, count, total: perms.length } : null;
                })
                .filter(Boolean);
        },

        userPermPreview(f) {
            const permSet = new Set(f.grupo?.perms || []);
            return this.catalogoEntries
                .map(([cat, perms]) => {
                    const active = perms.filter(p => permSet.has(p.id));
                    if (!active.length) {
                        return null;
                    }
                    return {
                        cat,
                        count: active.length,
                        total: perms.length,
                        labels: active.map(p => p.label).join(' · '),
                    };
                })
                .filter(Boolean);
        },

        rolePermCount(roleId) {
            return this.getGroupForRole(roleId)?.perms?.length || 0;
        },

        rolePermPct(roleId) {
            return this.totalPerms ? Math.round(this.rolePermCount(roleId) / this.totalPerms * 100) : 0;
        },

        roleBarColor(roleId) {
            const grp = this.getGroupForRole(roleId);
            return grp ? grp.cor : 'var(--sa-border)';
        },

        roleBarStyle(roleId) {
            return 'width:' + this.rolePermPct(roleId) + '%;background:' + this.roleBarColor(roleId);
        },

        isAssignSelected(groupId) {
            return String(this.assignSel) === String(groupId);
        },

        openGroupModal(group) {
            this.editGroup = group;
            this.groupForm = group
                ? { nome: group.nome, cor: group.cor, descricao: group.descricao, perms: [...group.perms] }
                : blankGroup();
            this.groupModalOpen = true;
        },

        openAssign(role) {
            this.assignRole = role;
            const gId = this.getGroupId(role.id);
            this.assignSel = gId ? String(gId) : '';
            this.assignModalOpen = true;
        },

        openUserGrupos(f) {
            this.editUser = f;
            this.userGruposSel = (f.grupo_ids || []).map(id => String(id));
            this.userGruposModalOpen = true;
        },

        isUserGrupoSelected(groupId) {
            return this.userGruposSel.includes(String(groupId));
        },

        toggleUserGrupo(groupId) {
            const key = String(groupId);
            this.userGruposSel = this.userGruposSel.includes(key)
                ? this.userGruposSel.filter(x => x !== key)
                : [...this.userGruposSel, key];
        },

        updateFuncionario(updated) {
            this.funcionarios = this.funcionarios.map(f => f.id === updated.id ? updated : f);
            if (this.editUser?.id === updated.id) {
                this.editUser = updated;
            }
        },

        async saveUserGrupos() {
            if (!this.editUser) return;
            this.userGruposSaving = true;
            try {
                const r = await fetch('/permissoes/usuarios/' + this.editUser.id + '/grupos', {
                    method: 'PATCH',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ grupo_ids: this.userGruposSel.map(id => parseInt(id, 10)) }),
                });
                const data = await r.json();
                if (!r.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Erro ao salvar grupos.');
                this.updateFuncionario(data.funcionario);
                this.userGruposModalOpen = false;
                Swal.fire({ title: 'Grupos atualizados!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            } finally {
                this.userGruposSaving = false;
            }
        },

        async syncUserGruposFromCargo() {
            if (!this.editUser) return;
            this.userGruposSaving = true;
            try {
                const r = await fetch('/permissoes/usuarios/' + this.editUser.id + '/grupos/cargo', {
                    method: 'POST',
                    headers: jsonHeaders(),
                });
                const data = await r.json();
                if (!r.ok) throw new Error(data.message || 'Erro ao sincronizar com cargo.');
                this.updateFuncionario(data.funcionario);
                this.userGruposSel = (data.funcionario.grupo_ids || []).map(id => String(id));
                this.userGruposModalOpen = false;
                Swal.fire({ title: 'Grupos sincronizados com o cargo!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            } finally {
                this.userGruposSaving = false;
            }
        },

        isCatAllOn(cat) {
            const ids = this.catalogo[cat].map(p => p.id);
            return ids.every(id => this.groupForm.perms.includes(id));
        },

        isCatPartial(cat) {
            const ids = this.catalogo[cat].map(p => p.id);
            return ids.some(id => this.groupForm.perms.includes(id)) && !this.isCatAllOn(cat);
        },

        toggleAllPerms() {
            this.groupForm.perms = this.groupForm.perms.length === this.totalPerms
                ? []
                : [...this.allPermIds];
        },

        togglePerm(id) {
            this.groupForm.perms = this.groupForm.perms.includes(id)
                ? this.groupForm.perms.filter(x => x !== id)
                : [...this.groupForm.perms, id];
        },

        toggleCatPerms(cat) {
            const ids = this.catalogo[cat].map(p => p.id);
            const allOn = ids.every(id => this.groupForm.perms.includes(id));
            this.groupForm.perms = allOn
                ? this.groupForm.perms.filter(x => !ids.includes(x))
                : [...new Set([...this.groupForm.perms, ...ids])];
        },

        async saveGroup() {
            if (!this.groupForm.nome.trim()) {
                return Swal.fire({ title: 'Atenção', text: 'Nome do grupo obrigatório.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            }
            this.groupSaving = true;
            try {
                const url = this.editGroup ? '/permissoes/grupos/' + this.editGroup.id : '/permissoes/grupos';
                const r = await fetch(url, {
                    method: this.editGroup ? 'PUT' : 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify(this.groupForm),
                });
                const data = await r.json();
                if (!r.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Erro ao salvar grupo.');
                if (this.editGroup) {
                    this.grupos = this.grupos.map(g => g.id === data.id ? data : g);
                } else {
                    this.grupos.push(data);
                }
                this.groupModalOpen = false;
                Swal.fire({ title: this.editGroup ? 'Grupo atualizado!' : 'Grupo criado!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            } finally {
                this.groupSaving = false;
            }
        },

        deleteGroup(id) {
            const grupo = this.grupos.find(g => g.id === id);
            if (grupo?.is_system) {
                return Swal.fire({ title: 'Atenção', text: 'Grupos padrão não podem ser excluídos.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            }
            Swal.fire({
                title: 'Remover grupo?',
                text: 'Cargos vinculados perderão este grupo.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
            }).then(async result => {
                if (!result.isConfirmed) return;
                try {
                    const r = await fetch('/permissoes/grupos/' + id, { method: 'DELETE', headers: jsonHeaders() });
                    if (!r.ok) throw new Error('Erro ao remover grupo.');
                    this.grupos = this.grupos.filter(g => g.id !== id);
                    Object.entries(this.roleGroups).forEach(([cargoId, gId]) => {
                        if (gId === id) delete this.roleGroups[cargoId];
                    });
                    Swal.fire({ title: 'Grupo removido', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                } catch (e) {
                    Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
                }
            });
        },

        async doAssign() {
            if (!this.assignRole) return;
            try {
                const r = await fetch('/permissoes/cargos/' + this.assignRole.id + '/grupo', {
                    method: 'PATCH',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ grupo_id: this.assignSel || null }),
                });
                if (!r.ok) throw new Error('Erro ao atribuir grupo.');
                this.roleGroups = { ...this.roleGroups, [this.assignRole.id]: this.assignSel || null };
                this.assignModalOpen = false;
                Swal.fire({ title: 'Grupo atribuído!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            }
        },

    };
}
</script>
@endpush
@endsection
