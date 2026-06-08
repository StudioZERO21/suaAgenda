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
            {{-- Vertical tabs --}}
            <div style="width:180px;flex-shrink:0">
                <button type="button" @click="tab = 'matrix'"
                        :style="tabStyle('matrix')"
                        style="display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:9px;border:none;cursor:pointer;width:100%;text-align:left;font-size:13px;font-family:var(--sa-font-body);margin-bottom:2px;transition:all 150ms">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Matriz de Acesso
                </button>
                <button type="button" @click="tab = 'groups'"
                        :style="tabStyle('groups')"
                        style="display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:9px;border:none;cursor:pointer;width:100%;text-align:left;font-size:13px;font-family:var(--sa-font-body);margin-bottom:2px;transition:all 150ms">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/></svg>
                    Grupos ACL
                </button>
                <button type="button" @click="tab = 'roles'"
                        :style="tabStyle('roles')"
                        style="display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:9px;border:none;cursor:pointer;width:100%;text-align:left;font-size:13px;font-family:var(--sa-font-body);margin-bottom:2px;transition:all 150ms">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Cargos & Grupos
                </button>
                <button type="button" @click="tab = 'users'"
                        :style="tabStyle('users')"
                        style="display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:9px;border:none;cursor:pointer;width:100%;text-align:left;font-size:13px;font-family:var(--sa-font-body);margin-bottom:2px;transition:all 150ms">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    Usuários & Funções
                </button>
            </div>

            <div style="flex:1;min-width:0">

                {{-- MATRIX TAB --}}
                <div x-show="tab === 'matrix'" x-cloak>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                        Visão completa de permissões por cargo. As colunas são os cargos cadastrados na empresa. Clique em ✦ para editar via grupos ACL.
                    </p>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse;min-width:600px">
                            <thead>
                                <tr>
                                    <th style="padding:10px 14px;font-size:12px;font-weight:600;color:var(--sa-text3);text-align:left;border-bottom:2px solid var(--sa-border);background:var(--sa-surface2);width:200px;position:sticky;left:0">Permissão</th>
                                    <template x-for="role in cargos" :key="role.id">
                                        <th style="padding:10px;font-size:12px;font-weight:600;color:var(--sa-text1);text-align:center;border-bottom:2px solid var(--sa-border);background:var(--sa-surface2);min-width:100px">
                                            <div style="display:flex;flex-direction:column;align-items:center;gap:3px">
                                                <div style="display:flex;align-items:center;gap:5px">
                                                    <div style="width:8px;height:8px;border-radius:50%" :style="'background:' + role.cor"></div>
                                                    <span x-text="role.nome"></span>
                                                </div>
                                                <template x-if="getGroupForRole(role.id)">
                                                    <span style="font-size:9px;font-weight:700;padding:2px 6px;border-radius:20px"
                                                          :style="'background:' + getGroupForRole(role.id).cor + '15;color:' + getGroupForRole(role.id).cor"
                                                          x-text="getGroupForRole(role.id).nome"></span>
                                                </template>
                                                <button type="button" @click="openAssign(role)" style="font-size:9px;color:var(--sa-secondary);background:none;border:none;cursor:pointer;font-weight:600">✦ Editar grupo</button>
                                            </div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="[cat, perms] in catalogoEntries" :key="cat">
                                    <tr @click="toggleCatExpand(cat)" style="cursor:pointer;background:color-mix(in srgb,var(--sa-primary) 4%,transparent)">
                                        <td :colspan="cargos.length + 1" style="padding:8px 14px;font-size:12px;font-weight:700;color:var(--sa-text1);border-bottom:1px solid var(--sa-border);border-top:1px solid var(--sa-border)">
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="isCatExpanded(cat) ? '' : 'transform:rotate(-90deg)'"><polyline points="6 9 12 15 18 9"/></svg>
                                                <span x-text="cat"></span>
                                                <span style="font-size:11px;color:var(--sa-text3);font-weight:400" x-text="'(' + perms.length + ' permissões)'"></span>
                                            </div>
                                        </td>
                                    </tr>
                                    <template x-for="perm in perms" :key="perm.id">
                                        <tr class="sa-tr" x-show="isCatExpanded(cat)">
                                            <td style="padding:9px 14px 9px 22px;font-size:12px;color:var(--sa-text2);border-bottom:1px solid var(--sa-border);position:sticky;left:0;background:var(--sa-surface)" x-text="perm.label"></td>
                                            <template x-for="role in cargos" :key="role.id + '-' + perm.id">
                                                <td style="text-align:center;border-bottom:1px solid var(--sa-border);padding:9px 4px">
                                                    <div style="display:inline-flex;width:22px;height:22px;border-radius:6px;align-items:center;justify-content:center"
                                                         :style="'background:' + (hasPerm(role.id, perm.id) ? role.cor + '18' : 'transparent') + ';border:1px solid ' + (hasPerm(role.id, perm.id) ? role.cor : 'var(--sa-border)')">
                                                        <svg x-show="hasPerm(role.id, perm.id)" width="11" height="11" viewBox="0 0 24 24" fill="none" :stroke="role.cor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                        <span x-show="!hasPerm(role.id, perm.id)" style="font-size:10px;color:var(--sa-text3);opacity:.4">—</span>
                                                    </div>
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- GROUPS TAB --}}
                <div x-show="tab === 'groups'" x-cloak>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                        Grupos ACL agrupam permissões e são atribuídos a cargos. Crie grupos personalizados para necessidades específicas.
                    </p>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        <template x-for="g in grupos" :key="g.id">
                            <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:14px;overflow:hidden;transition:box-shadow 200ms"
                                 @mouseenter="$el.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'"
                                 @mouseleave="$el.style.boxShadow='none'">
                                <div :style="'height:4px;background:' + g.cor"></div>
                                <div style="padding:16px 16px 0">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
                                        <div style="font-size:14px;font-weight:700;color:var(--sa-text1);font-family:var(--sa-font-heading)" x-text="g.nome"></div>
                                        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px"
                                              :style="'background:' + g.cor + '15;color:' + g.cor"
                                              x-text="g.perms.length + ' perms.'"></span>
                                    </div>
                                    <p style="font-size:12px;color:var(--sa-text3);margin:0 0 12px;line-height:1.5" x-text="g.descricao"></p>
                                    <div style="margin-bottom:12px">
                                        <template x-for="[cat, perms] in catalogoEntries" :key="g.id + '-' + cat">
                                            <div x-show="catPermCount(g, cat) > 0" style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--sa-border)">
                                                <span style="font-size:11px;color:var(--sa-text3)" x-text="cat"></span>
                                                <span style="font-size:11px;font-weight:600" :style="'color:' + g.cor" x-text="catPermCount(g, cat) + '/' + perms.length"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="assignedRoles(g.id).length > 0" style="margin-bottom:12px">
                                        <div style="font-size:10px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Cargos com este grupo</div>
                                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                                            <template x-for="r in assignedRoles(g.id)" :key="r.id">
                                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px"
                                                      :style="'background:' + r.cor + '15;color:' + r.cor"
                                                      x-text="r.nome"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div style="padding:10px 12px;border-top:1px solid var(--sa-border);display:flex;gap:6px">
                                    <x-sa.btn size="sm" variant="muted" @click="openGroupModal(g)" style="flex:1">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Editar
                                    </x-sa.btn>
                                    <x-sa.btn size="sm" variant="ghost" @click="deleteGroup(g.id)">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                    </x-sa.btn>
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="openGroupModal(null)"
                                style="background:var(--sa-surface2);border:2px dashed var(--sa-border);border-radius:14px;padding:32px;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;transition:all 200ms;font-family:var(--sa-font-body)"
                                @mouseenter="$el.style.borderColor='var(--sa-primary)';$el.style.background='color-mix(in srgb,var(--sa-primary) 4%,transparent)'"
                                @mouseleave="$el.style.borderColor='var(--sa-border)';$el.style.background='var(--sa-surface2)'">
                            <div style="width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,var(--sa-primary) 10%,transparent);display:flex;align-items:center;justify-content:center">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                            <div style="font-size:13px;font-weight:600;color:var(--sa-text2)">Novo Grupo ACL</div>
                        </button>
                    </div>
                </div>

                {{-- ROLES TAB --}}
                <div x-show="tab === 'roles'" x-cloak>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                        Atribua um grupo de acesso ACL para cada cargo da empresa. Os cargos são gerenciados em <strong>Cargos</strong>.
                    </p>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <template x-for="role in cargos" :key="role.id">
                            <div style="display:flex;align-items:center;gap:14px;padding:16px 18px;background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:12px;transition:box-shadow 150ms"
                                 @mouseenter="$el.style.boxShadow='0 4px 12px rgba(0,0,0,.07)'"
                                 @mouseleave="$el.style.boxShadow='none'">
                                <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0"
                                     :style="'background:' + role.cor">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:14px;font-weight:700;color:var(--sa-text1)" x-text="role.nome"></div>
                                    <div style="font-size:11px;color:var(--sa-text3);margin-top:2px" x-text="role.nivel"></div>
                                </div>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                <div style="flex:1;min-width:0">
                                    <template x-if="getGroupForRole(role.id)">
                                        <div style="display:flex;align-items:center;gap:10px">
                                            <div style="width:10px;height:10px;border-radius:50%;flex-shrink:0" :style="'background:' + getGroupForRole(role.id).cor"></div>
                                            <div>
                                                <div style="font-size:13px;font-weight:600;color:var(--sa-text1)" x-text="getGroupForRole(role.id).nome"></div>
                                                <div style="font-size:11px;color:var(--sa-text3);margin-top:1px" x-text="rolePermCount(role.id) + ' / ' + totalPerms + ' permissões'"></div>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!getGroupForRole(role.id)">
                                        <div style="font-size:13px;color:var(--sa-text3);font-style:italic">Sem grupo atribuído — sem acesso</div>
                                    </template>
                                </div>
                                <div style="width:80px;flex-shrink:0">
                                    <div style="height:5px;border-radius:3px;background:var(--sa-surface2);overflow:hidden">
                                        <div style="height:100%;border-radius:3px;transition:width 600ms ease"
                                             :style="'width:' + rolePermPct(role.id) + '%;background:' + (getGroupForRole(role.id)?.cor || 'var(--sa-border)')"></div>
                                    </div>
                                    <div style="font-size:10px;color:var(--sa-text3);margin-top:3px;text-align:right" x-text="rolePermPct(role.id) + '%'"></div>
                                </div>
                                <x-sa.btn size="sm" variant="muted" @click="openAssign(role)">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Alterar
                                </x-sa.btn>
                            </div>
                        </template>
                    </div>

                    {{-- ACL hint --}}
                    <div style="margin-top:20px;padding:14px 16px;background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent);border-radius:10px">
                        <div style="font-size:13px;font-weight:600;color:var(--sa-secondary);margin-bottom:4px">💡 Como funciona o ACL</div>
                        <p style="font-size:12px;color:var(--sa-text3);margin:0;line-height:1.7">
                            Cada <strong>Cargo</strong> recebe um <strong>Grupo de Acesso ACL</strong>. O grupo define quais ações o cargo pode realizar.
                            Ao alterar o grupo de um cargo, todos os funcionários com esse cargo são afetados imediatamente.
                            Crie grupos personalizados na aba <strong>Grupos ACL</strong> para cenários específicos.
                        </p>
                    </div>
                </div>

                {{-- USERS TAB --}}
                <div x-show="tab === 'users'" x-cloak>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                        Gerencie as funções dos membros da empresa. A função determina o acesso ao painel.
                    </p>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <template x-for="u in users" :key="u.id">
                            <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:12px;transition:box-shadow 150ms"
                                 @mouseenter="$el.style.boxShadow='0 4px 12px rgba(0,0,0,.06)'"
                                 @mouseleave="$el.style.boxShadow='none'">
                                <div style="width:38px;height:38px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;font-family:'Inter',sans-serif;flex-shrink:0"
                                     x-text="(u.name || '?').charAt(0).toUpperCase()"></div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:14px;font-weight:600;color:var(--sa-text1)" x-text="u.name"></div>
                                    <div style="font-size:12px;color:var(--sa-text3);margin-top:1px" x-text="u.email"></div>
                                </div>
                                <div style="flex-shrink:0">
                                    <span x-show="!u.ativo" style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(107,114,128,.12);color:#6b7280;margin-right:8px">Inativo</span>
                                </div>
                                <select x-model="u.role"
                                        @change="changeUserRole(u)"
                                        style="font-size:13px;padding:8px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;min-width:160px;appearance:none"
                                        onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                    <option value="">Sem função</option>
                                    <option value="admin_empresa">Administrador</option>
                                    <option value="gestor">Gestor</option>
                                    <option value="analista">Analista</option>
                                </select>
                            </div>
                        </template>
                        <template x-if="users.length === 0">
                            <p style="font-size:14px;color:var(--sa-text3);text-align:center;padding:32px 0">Nenhum membro cadastrado.</p>
                        </template>
                    </div>
                    <div style="margin-top:16px;padding:14px 16px;background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent);border-radius:10px">
                        <div style="font-size:13px;font-weight:600;color:var(--sa-secondary);margin-bottom:4px">💡 Funções disponíveis</div>
                        <p style="font-size:12px;color:var(--sa-text3);margin:0;line-height:1.7">
                            <strong>Administrador</strong> — acesso total. <strong>Gestor</strong> — gerencia equipe e relatórios.
                            <strong>Analista</strong> — visualização e agendamentos. Alterações têm efeito imediato.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </x-sa.body>

    {{-- GroupModal --}}
    <div x-show="groupModalOpen" x-cloak
         @keydown.escape.window="groupModalOpen = false"
         style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px"
         @click.self="groupModalOpen = false">
        <div style="background:var(--sa-surface);border-radius:16px;width:100%;max-width:820px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.2);animation:sa-modal-in 250ms ease">
            <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0">
                <div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0" x-text="editGroup ? 'Editar Grupo de Acesso' : 'Novo Grupo de Acesso'"></h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0">Defina quais permissões fazem parte deste grupo</p>
                </div>
                <button type="button" @click="groupModalOpen = false" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;display:flex;border-radius:6px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:20px 28px;overflow-y:auto;flex:1">
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Nome do grupo</label>
                            <input type="text" x-model="groupForm.nome" placeholder="Ex: Profissional Sênior" required
                                   style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Descrição</label>
                            <textarea x-model="groupForm.descricao" rows="1" placeholder="Breve descrição"
                                      style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box;resize:vertical"></textarea>
                        </div>
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:8px">Cor</label>
                        <div style="display:flex;gap:8px">
                            <template x-for="c in groupColors" :key="c">
                                <button type="button" @click="groupForm.cor = c"
                                        :style="'width:26px;height:26px;border-radius:50%;background:' + c + ';border:' + (groupForm.cor === c ? '3px solid var(--sa-text1)' : '2px solid transparent') + ';cursor:pointer'"></button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1)" x-text="'Permissões (' + groupForm.perms.length + '/' + totalPerms + ')'"></label>
                            <x-sa.btn variant="muted" size="sm" @click="toggleAllPerms()">
                                <span x-text="groupForm.perms.length === totalPerms ? 'Desmarcar todas' : 'Selecionar todas'"></span>
                            </x-sa.btn>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;max-height:360px;overflow-y:auto">
                            <template x-for="[cat, perms] in catalogoEntries" :key="'gf-' + cat">
                                <div style="background:var(--sa-surface2);border-radius:10px;padding:10px 12px;border:1px solid var(--sa-border)">
                                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer" @click="toggleCatPerms(cat)">
                                        <div style="width:16px;height:16px;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0"
                                             :style="'border:2px solid ' + (isCatAllOn(cat) ? groupForm.cor : 'var(--sa-border)') + ';background:' + (isCatAllOn(cat) ? groupForm.cor : isCatPartial(cat) ? groupForm.cor + '40' : 'transparent')">
                                            <template x-if="isCatAllOn(cat) || isCatPartial(cat)">
                                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" :stroke="isCatAllOn(cat) ? '#fff' : groupForm.cor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            </template>
                                        </div>
                                        <span style="font-size:12px;font-weight:700;color:var(--sa-text1)" x-text="cat"></span>
                                    </div>
                                    <template x-for="p in perms" :key="p.id">
                                        <div style="display:flex;align-items:center;gap:7px;padding:4px 0;cursor:pointer" @click="togglePerm(p.id)">
                                            <div style="width:14px;height:14px;border-radius:3px;display:flex;align-items:center;justify-content:center;flex-shrink:0"
                                                 :style="'border:1.5px solid ' + (groupForm.perms.includes(p.id) ? groupForm.cor : 'var(--sa-border)') + ';background:' + (groupForm.perms.includes(p.id) ? groupForm.cor : 'transparent')">
                                                <template x-if="groupForm.perms.includes(p.id)">
                                                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                                </template>
                                            </div>
                                            <span style="font-size:11px;color:var(--sa-text2)" x-text="p.label"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div style="padding:16px 28px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
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
         style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px"
         @click.self="assignModalOpen = false">
        <div style="background:var(--sa-surface);border-radius:16px;width:100%;max-width:460px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.2);animation:sa-modal-in 250ms ease">
            <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0">
                <div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0" x-text="'Atribuir grupo — ' + (assignRole?.nome || '')"></h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0">Selecione o grupo de acesso para este cargo</p>
                </div>
                <button type="button" @click="assignModalOpen = false" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;display:flex;border-radius:6px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:20px 28px;overflow-y:auto;flex:1">
                <div style="display:flex;flex-direction:column;gap:8px">
                    <template x-for="g in grupos" :key="'assign-' + g.id">
                        <div @click="assignSel = g.id"
                             style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:10px;cursor:pointer;transition:all 150ms"
                             :style="'border:2px solid ' + (assignSel === g.id ? g.cor : 'var(--sa-border)') + ';background:' + (assignSel === g.id ? g.cor + '08' : 'var(--sa-surface)')">
                            <div style="width:14px;height:14px;border-radius:50%;margin-top:2px;flex-shrink:0"
                                 :style="'border:2px solid ' + g.cor + ';background:' + (assignSel === g.id ? g.cor : 'transparent')"></div>
                            <div style="flex:1">
                                <div style="font-size:13px;font-weight:700;color:var(--sa-text1)" x-text="g.nome"></div>
                                <div style="font-size:11px;color:var(--sa-text3);margin-top:2px" x-text="g.descricao"></div>
                                <div style="font-size:11px;margin-top:4px;font-weight:600" :style="'color:' + g.cor" x-text="g.perms.length + ' permissões'"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="assignSel === ''" style="padding:10px 0;text-align:center;font-size:13px;color:var(--sa-text3)">Nenhum grupo = sem acesso ao sistema</div>
                </div>
            </div>
            <div style="padding:16px 28px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
                <x-sa.btn variant="secondary" size="sm" @click="assignModalOpen = false">Cancelar</x-sa.btn>
                <x-sa.btn size="sm" @click="doAssign()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                    Atribuir
                </x-sa.btn>
            </div>
        </div>
    </div>
</x-sa.page>

@push('scripts')
<script>
function permissionsApp() {
    const catalogo = @json($catalogo);
    const defaultGroups = ['g-admin', 'g-mgr', 'g-prof', 'g-recep', 'g-intern'];

    const blankGroup = () => ({ nome: '', cor: '#6366f1', descricao: '', perms: [] });

    return {
        tab: 'matrix',
        catalogo,
        grupos: @json($gruposJson),
        cargos: @json($cargosJson),
        roleGroups: @json($roleGroupsJson),
        users: @json($usersJson),
        expandedCat: {},
        groupModalOpen: false,
        assignModalOpen: false,
        editGroup: null,
        assignRole: null,
        assignSel: '',
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

        tabStyle(id) {
            const active = this.tab === id;
            return 'background:' + (active ? 'color-mix(in srgb,var(--sa-primary) 8%,transparent)' : 'transparent')
                + ';color:' + (active ? 'var(--sa-primary)' : 'var(--sa-text2)')
                + ';font-weight:' + (active ? '600' : '500')
                + ';border-left:2px solid ' + (active ? 'var(--sa-primary)' : 'transparent');
        },

        getGroupId(roleId) {
            return this.roleGroups[roleId] ?? this.roleGroups[String(roleId)] ?? '';
        },

        getGroupForRole(roleId) {
            const gId = this.getGroupId(roleId);
            return this.grupos.find(g => g.id === gId) || null;
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
            return this.cargos.filter(r => this.getGroupId(r.id) === groupId);
        },

        catPermCount(g, cat) {
            const ids = this.catalogo[cat].map(p => p.id);
            return ids.filter(id => g.perms.includes(id)).length;
        },

        rolePermCount(roleId) {
            return this.getGroupForRole(roleId)?.perms?.length || 0;
        },

        rolePermPct(roleId) {
            return this.totalPerms ? Math.round(this.rolePermCount(roleId) / this.totalPerms * 100) : 0;
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
            this.assignSel = this.getGroupId(role.id) || '';
            this.assignModalOpen = true;
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

        saveGroup() {
            if (!this.groupForm.nome.trim()) {
                return Swal.fire({ title: 'Atenção', text: 'Nome do grupo obrigatório.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            }
            this.groupSaving = true;
            setTimeout(() => {
                if (this.editGroup) {
                    this.grupos = this.grupos.map(g =>
                        g.id === this.editGroup.id ? { ...g, ...this.groupForm } : g
                    );
                } else {
                    this.grupos.push({ ...this.groupForm, id: 'g-' + Date.now() });
                }
                this.groupSaving = false;
                this.groupModalOpen = false;
                Swal.fire({
                    title: this.editGroup ? 'Grupo atualizado!' : 'Grupo criado!',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#1a1a1a',
                });
            }, 700);
        },

        deleteGroup(id) {
            if (defaultGroups.includes(id)) {
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
            }).then(result => {
                if (result.isConfirmed) {
                    this.grupos = this.grupos.filter(g => g.id !== id);
                    Swal.fire({ title: 'Grupo removido', icon: 'success', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
                }
            });
        },

        doAssign() {
            if (!this.assignRole) return;
            this.roleGroups = { ...this.roleGroups, [this.assignRole.id]: this.assignSel };
            this.assignModalOpen = false;
            Swal.fire({ title: 'Grupo atribuído!', icon: 'success', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
        },

        async changeUserRole(user) {
            if (!user.role) {
                return Swal.fire({ title: 'Atenção', text: 'Selecione uma função válida.', icon: 'warning', confirmButtonColor: '#1a1a1a' });
            }
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const r = await fetch('/permissoes/usuarios/' + user.id + '/role', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ role: user.role }),
                });
                if (!r.ok) throw new Error('Sem permissão para alterar esta função.');
                Swal.fire({ title: 'Função atualizada!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            }
        },
    };
}
</script>
@endpush
@endsection
