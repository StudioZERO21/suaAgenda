// ============================================================
// suaAgenda.pro — Permissions Screen v2
// ACL Groups + dynamic roles from company
// ============================================================
const { useState, useMemo } = React;

// ── ACL PERMISSION CATALOGUE ──────────────────────────────────
const ACL_CATALOGUE = {
  'Agenda': [
    { id:'cal_view',    label:'Ver agenda completa'       },
    { id:'cal_own',     label:'Ver própria agenda'        },
    { id:'cal_create',  label:'Criar agendamentos'        },
    { id:'cal_edit',    label:'Editar agendamentos'       },
    { id:'cal_delete',  label:'Cancelar agendamentos'     },
    { id:'cal_move',    label:'Mover agendamentos (drag)' },
  ],
  'Clientes': [
    { id:'cli_view',    label:'Ver lista de clientes'     },
    { id:'cli_create',  label:'Cadastrar clientes'        },
    { id:'cli_edit',    label:'Editar dados de clientes'  },
    { id:'cli_delete',  label:'Excluir clientes'          },
    { id:'cli_history', label:'Ver histórico de clientes' },
    { id:'cli_photos',  label:'Gerenciar fotos / antes-depois' },
  ],
  'Financeiro': [
    { id:'fin_view',    label:'Ver receita total'         },
    { id:'fin_own',     label:'Ver próprias comissões'    },
    { id:'fin_pdv',     label:'Operar PDV'                },
    { id:'fin_export',  label:'Exportar relatórios'       },
  ],
  'Equipe': [
    { id:'stf_view',    label:'Ver lista de funcionários' },
    { id:'stf_create',  label:'Cadastrar funcionários'    },
    { id:'stf_edit',    label:'Editar funcionários'       },
    { id:'stf_delete',  label:'Remover funcionários'      },
  ],
  'Configurações': [
    { id:'cfg_theme',   label:'Alterar tema e aparência'  },
    { id:'cfg_company', label:'Editar dados da empresa'   },
    { id:'cfg_plans',   label:'Gerenciar planos'          },
    { id:'cfg_perms',   label:'Editar permissões'         },
    { id:'cfg_api',     label:'Acessar API & Webhooks'    },
    { id:'cfg_site',    label:'Configurar site público'   },
  ],
  'Portfólio & Produtos': [
    { id:'ptf_view',    label:'Ver portfólio'             },
    { id:'ptf_edit',    label:'Gerenciar portfólio'       },
    { id:'prd_view',    label:'Ver produtos'              },
    { id:'prd_edit',    label:'Gerenciar produtos'        },
  ],
};

// ── BUILT-IN ACL GROUPS ───────────────────────────────────────
const INIT_GROUPS = [
  { id:'g-admin',  name:'Acesso Total',       color:'#ef4444', desc:'Todas as permissões habilitadas',
    perms: Object.values(ACL_CATALOGUE).flat().map(p=>p.id) },
  { id:'g-mgr',    name:'Gestão Operacional', color:'#f59e0b', desc:'Relatórios, equipe e agenda completa',
    perms:['cal_view','cal_create','cal_edit','cal_delete','cal_move','cli_view','cli_create','cli_edit','cli_history','fin_view','fin_export','stf_view','stf_create','stf_edit','prd_view','prd_edit','ptf_view','ptf_edit','cfg_theme','cfg_company','cfg_site'] },
  { id:'g-prof',   name:'Profissional',       color:'#6366f1', desc:'Agenda própria, clientes atendidos e comissões',
    perms:['cal_own','cal_move','cli_view','cli_history','cli_photos','fin_own','ptf_view','ptf_edit','prd_view'] },
  { id:'g-recep',  name:'Recepção',           color:'#10b981', desc:'Agendamentos, cadastros e PDV',
    perms:['cal_view','cal_create','cal_edit','cal_move','cli_view','cli_create','cli_edit','cli_history','fin_pdv','prd_view','ptf_view'] },
  { id:'g-intern', name:'Estagiário',         color:'#64748b', desc:'Visualização supervisionada',
    perms:['cal_own','cli_view','ptf_view','prd_view'] },
];

// Get roles from company (RolesScreen) or fall back to defaults
function getCompanyRoles() {
  return window.SA_COMPANY_ROLES || [
    { id:1, name:'Administrador', color:'#ef4444', permLevel:'admin',        commission:0  },
    { id:2, name:'Gerente',       color:'#f59e0b', permLevel:'manager',      commission:0  },
    { id:3, name:'Barbeiro',      color:'#1a1a1a', permLevel:'professional', commission:40 },
    { id:4, name:'Colorista',     color:'#6366f1', permLevel:'professional', commission:42 },
    { id:5, name:'Recepcionista', color:'#10b981', permLevel:'receptionist', commission:0  },
  ];
}

// ── GROUP EDITOR MODAL ────────────────────────────────────────
function GroupModal({ open, onClose, group, onSave }) {
  const blank = { name:'', color:'#6366f1', desc:'', perms:[] };
  const [form, setForm] = useState(group || blank);
  const [saving, setSaving] = useState(false);
  React.useEffect(()=>{ setForm(group||blank); }, [group]);
  const set = (k,v) => setForm(f=>({...f,[k]:v}));
  const allPerms = Object.values(ACL_CATALOGUE).flat().map(p=>p.id);
  const toggleAll = () => set('perms', form.perms.length===allPerms.length ? [] : [...allPerms]);
  const togglePerm = (id) => set('perms', form.perms.includes(id) ? form.perms.filter(x=>x!==id) : [...form.perms, id]);
  const toggleCat = (cat) => {
    const ids = ACL_CATALOGUE[cat].map(p=>p.id);
    const allOn = ids.every(id=>form.perms.includes(id));
    set('perms', allOn ? form.perms.filter(x=>!ids.includes(x)) : [...new Set([...form.perms,...ids])]);
  };
  const save = () => {
    if (!form.name.trim()) return window.SA_TOAST('Nome do grupo obrigatório','error');
    setSaving(true);
    setTimeout(()=>{ setSaving(false); onSave(form); onClose(); window.SA_TOAST(group?'Grupo atualizado!':'Grupo criado!','success'); },700);
  };
  const GROUP_COLORS = ['#ef4444','#f59e0b','#10b981','#6366f1','#ec4899','#0ea5e9','#1a1a1a','#d4a574'];
  return (
    <Modal open={open} onClose={onClose} size="lg" title={group?'Editar Grupo de Acesso':'Novo Grupo de Acesso'} subtitle="Defina quais permissões fazem parte deste grupo"
      footer={<><Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn><Btn size="sm" loading={saving} onClick={save} icon={<Icon name="check" size={14}/>}>{group?'Salvar':'Criar Grupo'}</Btn></>}>
      <div style={{ display:'flex', flexDirection:'column', gap:16 }}>
        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
          <Inp label="Nome do grupo" value={form.name} onChange={e=>set('name',e.target.value)} required placeholder="Ex: Profissional Sênior"/>
          <Txta label="Descrição" value={form.desc} onChange={e=>set('desc',e.target.value)} rows={1} placeholder="Breve descrição"/>
        </div>
        <div>
          <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:8 }}>Cor</label>
          <div style={{ display:'flex', gap:8 }}>
            {GROUP_COLORS.map(c=><button key={c} onClick={()=>set('color',c)} style={{ width:26, height:26, borderRadius:'50%', background:c, border:form.color===c?'3px solid var(--sa-text1)':'2px solid transparent', cursor:'pointer' }}/>)}
          </div>
        </div>
        <div>
          <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:10 }}>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>Permissões ({form.perms.length}/{allPerms.length})</label>
            <Btn variant="muted" size="sm" onClick={toggleAll}>{form.perms.length===allPerms.length?'Desmarcar todas':'Selecionar todas'}</Btn>
          </div>
          <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:10, maxHeight:360, overflowY:'auto' }}>
            {Object.entries(ACL_CATALOGUE).map(([cat, perms])=>{
              const catIds = perms.map(p=>p.id);
              const catOn = catIds.every(id=>form.perms.includes(id));
              const catPartial = catIds.some(id=>form.perms.includes(id)) && !catOn;
              return (
                <div key={cat} style={{ background:'var(--sa-surface2)', borderRadius:10, padding:'10px 12px', border:'1px solid var(--sa-border)' }}>
                  <div style={{ display:'flex', alignItems:'center', gap:8, marginBottom:8, cursor:'pointer' }} onClick={()=>toggleCat(cat)}>
                    <div style={{ width:16, height:16, borderRadius:4, border:`2px solid ${catOn?form.color:'var(--sa-border)'}`, background:catOn?form.color:catPartial?`${form.color}40`:'transparent', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
                      {(catOn||catPartial)&&<Icon name="check" size={9} style={{ color:catOn?'#fff':form.color }}/>}
                    </div>
                    <span style={{ fontSize:12, fontWeight:700, color:'var(--sa-text1)' }}>{cat}</span>
                  </div>
                  {perms.map(p=>(
                    <div key={p.id} style={{ display:'flex', alignItems:'center', gap:7, padding:'4px 0', cursor:'pointer' }} onClick={()=>togglePerm(p.id)}>
                      <div style={{ width:14, height:14, borderRadius:3, border:`1.5px solid ${form.perms.includes(p.id)?form.color:'var(--sa-border)'}`, background:form.perms.includes(p.id)?form.color:'transparent', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
                        {form.perms.includes(p.id)&&<Icon name="check" size={8} style={{ color:'#fff' }}/>}
                      </div>
                      <span style={{ fontSize:11, color:'var(--sa-text2)' }}>{p.label}</span>
                    </div>
                  ))}
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </Modal>
  );
}

// ── ASSIGN GROUP MODAL ────────────────────────────────────────
function AssignModal({ open, onClose, role, groups, currentGroupId, onAssign }) {
  const [sel, setSel] = useState(currentGroupId||'');
  React.useEffect(()=>{ setSel(currentGroupId||''); },[currentGroupId]);
  const group = groups.find(g=>g.id===sel);
  return (
    <Modal open={open} onClose={onClose} size="sm" title={`Atribuir grupo — ${role?.name}`} subtitle="Selecione o grupo de acesso para este cargo"
      footer={<><Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn><Btn size="sm" onClick={()=>{ onAssign(role?.id,sel); onClose(); window.SA_TOAST('Grupo atribuído!','success'); }} icon={<Icon name="check" size={14}/>}>Atribuir</Btn></>}>
      <div style={{ display:'flex', flexDirection:'column', gap:8 }}>
        {groups.map(g=>(
          <div key={g.id} onClick={()=>setSel(g.id)}
            style={{ display:'flex', alignItems:'flex-start', gap:12, padding:'12px 14px', borderRadius:10, border:`2px solid ${sel===g.id?g.color:'var(--sa-border)'}`, background:sel===g.id?`${g.color}08`:'var(--sa-surface)', cursor:'pointer', transition:'all 150ms' }}>
            <div style={{ width:14, height:14, borderRadius:'50%', border:`2px solid ${g.color}`, background:sel===g.id?g.color:'transparent', marginTop:2, flexShrink:0 }}/>
            <div style={{ flex:1 }}>
              <div style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)' }}>{g.name}</div>
              <div style={{ fontSize:11, color:'var(--sa-text3)', marginTop:2 }}>{g.desc}</div>
              <div style={{ fontSize:11, color:g.color, marginTop:4, fontWeight:600 }}>{g.perms.length} permissões</div>
            </div>
          </div>
        ))}
        {sel===''&&<div style={{ padding:'10px 0', textAlign:'center', fontSize:13, color:'var(--sa-text3)' }}>Nenhum grupo = sem acesso ao sistema</div>}
      </div>
    </Modal>
  );
}

// ── MAIN SCREEN ───────────────────────────────────────────────
function PermissionsScreen() {
  const [tab,       setTab]       = useState('matrix');
  const [groups,    setGroups]    = useState(INIT_GROUPS);
  const [groupModal,setGroupModal]= useState(false);
  const [editGroup, setEditGroup] = useState(null);
  const [assignModal,setAssignModal]=useState(false);
  const [assignRole, setAssignRole]=useState(null);
  const [roleGroups, setRoleGroups]=useState({ 1:'g-admin', 2:'g-mgr', 3:'g-prof', 4:'g-prof', 5:'g-recep' });
  const [expandedCat,setExpandedCat]=useState({});

  const companyRoles = getCompanyRoles();
  const toggleCat = (cat) => setExpandedCat(p=>({...p,[cat]:!p[cat]}));

  const saveGroup = (form) => {
    if (editGroup) setGroups(prev=>prev.map(g=>g.id===editGroup.id?{...g,...form}:g));
    else setGroups(prev=>[...prev,{...form,id:`g-${Date.now()}`}]);
  };
  const deleteGroup = (id) => {
    if (['g-admin','g-mgr','g-prof','g-recep','g-intern'].includes(id)) return window.SA_TOAST('Grupos padrão não podem ser excluídos','error');
    setGroups(prev=>prev.filter(g=>g.id!==id));
    window.SA_TOAST('Grupo removido','error');
  };

  const openAssign = (role) => { setAssignRole(role); setAssignModal(true); };
  const doAssign = (roleId, groupId) => setRoleGroups(p=>({...p,[roleId]:groupId}));

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Permissões & Acesso" subtitle="Gerencie grupos ACL e atribuições por cargo"
        actions={<Btn onClick={()=>{ setEditGroup(null); setGroupModal(true); }} icon={<Icon name="plus" size={15}/>}>Novo Grupo</Btn>}/>

      <div style={{ padding:'16px 32px 0', display:'flex', gap:24 }}>
        {/* Vertical tabs */}
        <div style={{ width:180, flexShrink:0 }}>
          {[['matrix','Matriz de Acesso','check'],['groups','Grupos ACL','sparkle'],['roles','Cargos & Grupos','user']].map(([id,lbl,ic])=>(
            <button key={id} onClick={()=>setTab(id)} style={{ display:'flex', alignItems:'center', gap:9, padding:'10px 12px', borderRadius:9, border:'none', cursor:'pointer', width:'100%', textAlign:'left', background:tab===id?'color-mix(in srgb,var(--sa-primary) 8%,transparent)':'transparent', color:tab===id?'var(--sa-primary)':'var(--sa-text2)', fontWeight:tab===id?600:500, fontSize:13, fontFamily:'var(--sa-font-body)', borderLeft:tab===id?'2px solid var(--sa-primary)':'2px solid transparent', transition:'all 150ms', marginBottom:2 }}>
              <Icon name={ic} size={15}/>{lbl}
            </button>
          ))}
        </div>

        <div style={{ flex:1, minWidth:0 }}>

          {/* ── MATRIX VIEW ──────────────────────────────────── */}
          {tab==='matrix'&&(
            <div>
              <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 16px', lineHeight:1.6 }}>
                Visão completa de permissões por cargo. As colunas são os cargos cadastrados na empresa. Clique em ✦ para editar via grupos ACL.
              </p>
              <div style={{ overflowX:'auto' }}>
                <table style={{ width:'100%', borderCollapse:'collapse', minWidth:600 }}>
                  <thead>
                    <tr>
                      <th style={{ padding:'10px 14px', fontSize:12, fontWeight:600, color:'var(--sa-text3)', textAlign:'left', borderBottom:'2px solid var(--sa-border)', background:'var(--sa-surface2)', width:200, position:'sticky', left:0 }}>Permissão</th>
                      {companyRoles.map(role=>{
                        const gId = roleGroups[role.id];
                        const grp = groups.find(g=>g.id===gId);
                        return (
                          <th key={role.id} style={{ padding:'10px 10px', fontSize:12, fontWeight:600, color:'var(--sa-text1)', textAlign:'center', borderBottom:'2px solid var(--sa-border)', background:'var(--sa-surface2)', minWidth:100 }}>
                            <div style={{ display:'flex', flexDirection:'column', alignItems:'center', gap:3 }}>
                              <div style={{ display:'flex', alignItems:'center', gap:5 }}>
                                <div style={{ width:8, height:8, borderRadius:'50%', background:role.color }}/>
                                <span>{role.name}</span>
                              </div>
                              {grp&&<span style={{ fontSize:9, fontWeight:700, padding:'2px 6px', borderRadius:20, background:`${grp.color}15`, color:grp.color }}>{grp.name}</span>}
                              <button onClick={()=>openAssign(role)} style={{ fontSize:9, color:'var(--sa-secondary)', background:'none', border:'none', cursor:'pointer', fontWeight:600 }}>✦ Editar grupo</button>
                            </div>
                          </th>
                        );
                      })}
                    </tr>
                  </thead>
                  <tbody>
                    {Object.entries(ACL_CATALOGUE).map(([cat, perms])=>(
                      <React.Fragment key={cat}>
                        {/* Category header row */}
                        <tr onClick={()=>toggleCat(cat)} style={{ cursor:'pointer', background:'color-mix(in srgb,var(--sa-primary) 4%,transparent)' }}>
                          <td colSpan={companyRoles.length+1} style={{ padding:'8px 14px', fontSize:12, fontWeight:700, color:'var(--sa-text1)', borderBottom:'1px solid var(--sa-border)', borderTop:'1px solid var(--sa-border)', position:'sticky', left:0 }}>
                            <div style={{ display:'flex', alignItems:'center', gap:8 }}>
                              <Icon name={expandedCat[cat]===false?'chevR':'chevD'} size={12}/>
                              {cat} <span style={{ fontSize:11, color:'var(--sa-text3)', fontWeight:400 }}>({perms.length} permissões)</span>
                            </div>
                          </td>
                        </tr>
                        {/* Permission rows */}
                        {expandedCat[cat]!==false && perms.map((perm,i)=>(
                          <tr key={perm.id} onMouseEnter={e=>e.currentTarget.style.background='var(--sa-surface2)'} onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
                            <td style={{ padding:'9px 14px 9px 22px', fontSize:12, color:'var(--sa-text2)', borderBottom:'1px solid var(--sa-border)', position:'sticky', left:0, background:'var(--sa-surface)' }}>{perm.label}</td>
                            {companyRoles.map(role=>{
                              const gId = roleGroups[role.id];
                              const grp = groups.find(g=>g.id===gId);
                              const hasPerm = grp?.perms?.includes(perm.id) || false;
                              return (
                                <td key={role.id} style={{ textAlign:'center', borderBottom:'1px solid var(--sa-border)', padding:'9px 4px' }}>
                                  <div style={{ display:'inline-flex', width:22, height:22, borderRadius:6, alignItems:'center', justifyContent:'center', background:hasPerm?`${role.color}18`:'transparent', border:`1px solid ${hasPerm?role.color:' var(--sa-border)'}` }}>
                                    {hasPerm
                                      ? <Icon name="check" size={11} style={{ color:role.color }}/>
                                      : <span style={{ fontSize:10, color:'var(--sa-text3)', opacity:.4 }}>—</span>}
                                  </div>
                                </td>
                              );
                            })}
                          </tr>
                        ))}
                      </React.Fragment>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ── GROUPS VIEW ──────────────────────────────────── */}
          {tab==='groups'&&(
            <div>
              <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 16px', lineHeight:1.6 }}>
                Grupos ACL agrupam permissões e são atribuídos a cargos. Crie grupos personalizados para necessidades específicas.
              </p>
              <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:14 }}>
                {groups.map(g=>{
                  const assignedRoles = companyRoles.filter(r=>roleGroups[r.id]===g.id);
                  return (
                    <div key={g.id} style={{ background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:14, overflow:'hidden', transition:'box-shadow 200ms' }}
                      onMouseEnter={e=>e.currentTarget.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'}
                      onMouseLeave={e=>e.currentTarget.style.boxShadow='none'}>
                      <div style={{ height:4, background:g.color }}/>
                      <div style={{ padding:'16px 16px 0' }}>
                        <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:6 }}>
                          <div style={{ fontSize:14, fontWeight:700, color:'var(--sa-text1)', fontFamily:"var(--sa-font-heading)" }}>{g.name}</div>
                          <span style={{ fontSize:11, fontWeight:700, padding:'2px 8px', borderRadius:20, background:`${g.color}15`, color:g.color }}>{g.perms.length} perms.</span>
                        </div>
                        <p style={{ fontSize:12, color:'var(--sa-text3)', margin:'0 0 12px', lineHeight:1.5 }}>{g.desc}</p>
                        {/* Permission preview */}
                        <div style={{ marginBottom:12 }}>
                          {Object.entries(ACL_CATALOGUE).map(([cat,perms])=>{
                            const count = perms.filter(p=>g.perms.includes(p.id)).length;
                            if (!count) return null;
                            return (
                              <div key={cat} style={{ display:'flex', justifyContent:'space-between', padding:'4px 0', borderBottom:'1px solid var(--sa-border)' }}>
                                <span style={{ fontSize:11, color:'var(--sa-text3)' }}>{cat}</span>
                                <span style={{ fontSize:11, fontWeight:600, color:g.color }}>{count}/{perms.length}</span>
                              </div>
                            );
                          })}
                        </div>
                        {/* Assigned roles */}
                        {assignedRoles.length>0&&(
                          <div style={{ marginBottom:12 }}>
                            <div style={{ fontSize:10, fontWeight:700, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', marginBottom:6 }}>Cargos com este grupo</div>
                            <div style={{ display:'flex', gap:5, flexWrap:'wrap' }}>
                              {assignedRoles.map(r=>(
                                <span key={r.id} style={{ fontSize:11, fontWeight:600, padding:'2px 8px', borderRadius:20, background:`${r.color}15`, color:r.color }}>{r.name}</span>
                              ))}
                            </div>
                          </div>
                        )}
                      </div>
                      <div style={{ padding:'10px 12px', borderTop:'1px solid var(--sa-border)', display:'flex', gap:6 }}>
                        <Btn size="sm" variant="muted" fullWidth onClick={()=>{ setEditGroup(g); setGroupModal(true); }} icon={<Icon name="edit" size={12}/>}>Editar</Btn>
                        <Btn size="sm" variant="ghost" onClick={()=>deleteGroup(g.id)} icon={<Icon name="trash" size={12}/>}/>
                      </div>
                    </div>
                  );
                })}
                {/* Add group card */}
                <button onClick={()=>{ setEditGroup(null); setGroupModal(true); }}
                  style={{ background:'var(--sa-surface2)', border:'2px dashed var(--sa-border)', borderRadius:14, padding:'32px', cursor:'pointer', display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:10, transition:'all 200ms', fontFamily:'var(--sa-font-body)' }}
                  onMouseEnter={e=>{e.currentTarget.style.borderColor='var(--sa-primary)';e.currentTarget.style.background='color-mix(in srgb,var(--sa-primary) 4%,transparent)';}}
                  onMouseLeave={e=>{e.currentTarget.style.borderColor='var(--sa-border)';e.currentTarget.style.background='var(--sa-surface2)';}}>
                  <div style={{ width:44, height:44, borderRadius:12, background:'color-mix(in srgb,var(--sa-primary) 10%,transparent)', display:'flex', alignItems:'center', justifyContent:'center' }}>
                    <Icon name="plus" size={20} style={{ color:'var(--sa-primary)' }}/>
                  </div>
                  <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text2)' }}>Novo Grupo ACL</div>
                </button>
              </div>
            </div>
          )}

          {/* ── ROLES & GROUPS ASSIGNMENT ────────────────────── */}
          {tab==='roles'&&(
            <div>
              <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 16px', lineHeight:1.6 }}>
                Atribua um grupo de acesso ACL para cada cargo da empresa. Os cargos são gerenciados em <strong>Cargos</strong>.
              </p>
              <div style={{ display:'flex', flexDirection:'column', gap:10 }}>
                {companyRoles.map(role=>{
                  const gId = roleGroups[role.id];
                  const grp = groups.find(g=>g.id===gId);
                  const permCount = grp?.perms?.length || 0;
                  const totalPerms = Object.values(ACL_CATALOGUE).flat().length;
                  return (
                    <div key={role.id} style={{ display:'flex', alignItems:'center', gap:14, padding:'16px 18px', background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:12, transition:'box-shadow 150ms' }}
                      onMouseEnter={e=>e.currentTarget.style.boxShadow='0 4px 12px rgba(0,0,0,.07)'}
                      onMouseLeave={e=>e.currentTarget.style.boxShadow='none'}>
                      {/* Role */}
                      <div style={{ width:36, height:36, borderRadius:10, background:role.color, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
                        <Icon name="user" size={16} style={{ color:'#fff' }}/>
                      </div>
                      <div style={{ flex:1, minWidth:0 }}>
                        <div style={{ fontSize:14, fontWeight:700, color:'var(--sa-text1)' }}>{role.name}</div>
                        <div style={{ fontSize:11, color:'var(--sa-text3)', marginTop:2 }}>{role.permLevel}</div>
                      </div>
                      {/* Arrow */}
                      <Icon name="chevR" size={14} style={{ color:'var(--sa-text3)' }}/>
                      {/* Group */}
                      <div style={{ flex:1, minWidth:0 }}>
                        {grp ? (
                          <div style={{ display:'flex', alignItems:'center', gap:10 }}>
                            <div style={{ width:10, height:10, borderRadius:'50%', background:grp.color, flexShrink:0 }}/>
                            <div>
                              <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>{grp.name}</div>
                              <div style={{ fontSize:11, color:'var(--sa-text3)', marginTop:1 }}>{permCount} / {totalPerms} permissões</div>
                            </div>
                          </div>
                        ) : (
                          <div style={{ fontSize:13, color:'var(--sa-text3)', fontStyle:'italic' }}>Sem grupo atribuído — sem acesso</div>
                        )}
                      </div>
                      {/* Permission bar */}
                      <div style={{ width:80, flexShrink:0 }}>
                        <div style={{ height:5, borderRadius:3, background:'var(--sa-surface2)', overflow:'hidden' }}>
                          <div style={{ height:'100%', borderRadius:3, background:grp?.color||'var(--sa-border)', width:`${Math.round(permCount/totalPerms*100)}%`, transition:'width 600ms ease' }}/>
                        </div>
                        <div style={{ fontSize:10, color:'var(--sa-text3)', marginTop:3, textAlign:'right' }}>{Math.round(permCount/totalPerms*100)}%</div>
                      </div>
                      <Btn size="sm" variant="muted" onClick={()=>openAssign(role)} icon={<Icon name="edit" size={12}/>}>Alterar</Btn>
                    </div>
                  );
                })}
              </div>

              {/* ACL hint */}
              <div style={{ marginTop:20, padding:'14px 16px', background:'color-mix(in srgb,var(--sa-secondary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent)', borderRadius:10 }}>
                <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-secondary)', marginBottom:4 }}>💡 Como funciona o ACL</div>
                <p style={{ fontSize:12, color:'var(--sa-text3)', margin:0, lineHeight:1.7 }}>
                  Cada <strong>Cargo</strong> recebe um <strong>Grupo de Acesso ACL</strong>. O grupo define quais ações o cargo pode realizar.
                  Ao alterar o grupo de um cargo, todos os funcionários com esse cargo são afetados imediatamente.
                  Crie grupos personalizados na aba <strong>Grupos ACL</strong> para cenários específicos.
                </p>
              </div>
            </div>
          )}

        </div>
      </div>

      <GroupModal open={groupModal} onClose={()=>setGroupModal(false)} group={editGroup} onSave={saveGroup}/>
      <AssignModal open={assignModal} onClose={()=>setAssignModal(false)} role={assignRole} groups={groups} currentGroupId={roleGroups[assignRole?.id]} onAssign={doAssign}/>
    </div>
  );
}

Object.assign(window, { PermissionsScreen });
