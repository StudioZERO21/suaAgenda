// ============================================================
// suaAgenda.pro — Roles Screen (Cadastro de Cargos)
// ============================================================
const { useState } = React;

const PERM_LEVELS = [
  { value:'admin',       label:'Administrador — acesso total'    },
  { value:'manager',     label:'Gerente — relatórios e equipe'   },
  { value:'professional',label:'Profissional — agenda própria'   },
  { value:'receptionist',label:'Recepcionista — agendamentos'    },
  { value:'intern',      label:'Estagiário — acesso limitado'    },
];

const ROLE_COLORS = ['#ef4444','#f59e0b','#10b981','#6366f1','#ec4899','#0ea5e9','#8b5cf6','#1a1a1a','#d4a574','#14b8a6'];

const INIT_ROLES = [
  { id:1, name:'Administrador', permLevel:'admin',        color:'#ef4444', desc:'Acesso total ao sistema. Gerencia planos, configurações e permissões.',   count:1, commission:0 },
  { id:2, name:'Gerente',       permLevel:'manager',      color:'#f59e0b', desc:'Gerencia equipe, relatórios e configurações operacionais.',                count:1, commission:0 },
  { id:3, name:'Barbeiro',      permLevel:'professional', color:'#1a1a1a', desc:'Realiza atendimentos, acessa sua agenda e vê suas comissões.',             count:2, commission:40 },
  { id:4, name:'Colorista',     permLevel:'professional', color:'#6366f1', desc:'Especialista em coloração. Acessa sua agenda e histórico de clientes.',   count:1, commission:42 },
  { id:5, name:'Recepcionista', permLevel:'receptionist', color:'#10b981', desc:'Gerencia agendamentos, cadastros de clientes e pagamentos.',               count:1, commission:0 },
  { id:6, name:'Estagiário',    permLevel:'intern',       color:'#64748b', desc:'Acesso supervisionado. Pode ver agenda mas não editar dados sensíveis.',  count:1, commission:20 },
];

function RoleModal({ open, onClose, role, onSave }) {
  const blank = { name:'', permLevel:'professional', color:'#6366f1', desc:'', commission:0, count:0 };
  const [form, setForm] = useState(role || blank);
  const [saving, setSaving] = useState(false);
  React.useEffect(() => { setForm(role || blank); }, [role]);
  const set = (k,v) => setForm(f=>({...f,[k]:v}));
  const save = () => {
    if (!form.name.trim()) return window.SA_TOAST('Preencha o nome do cargo','error');
    setSaving(true);
    setTimeout(() => { setSaving(false); onSave(form); onClose(); window.SA_TOAST(role?'Cargo atualizado!':'Cargo criado!','success'); }, 600);
  };

  return (
    <Modal open={open} onClose={onClose} size="md"
      title={role ? 'Editar Cargo' : 'Novo Cargo'}
      subtitle="Configure o cargo e suas permissões padrão"
      footer={<>
        <Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn>
        <Btn size="sm" loading={saving} onClick={save} icon={<Icon name="check" size={14}/>}>{role ? 'Salvar' : 'Criar Cargo'}</Btn>
      </>}>
      <div style={{ display:'flex', flexDirection:'column', gap:16 }}>
        {/* Preview */}
        <div style={{ display:'flex', alignItems:'center', gap:14, padding:14, background:'var(--sa-surface2)', borderRadius:12, border:'1px solid var(--sa-border)' }}>
          <div style={{ width:44, height:44, borderRadius:12, background:form.color, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
            <Icon name="user" size={20} style={{ color:'#fff' }}/>
          </div>
          <div>
            <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:16, fontWeight:700, color:'var(--sa-text1)' }}>{form.name || 'Nome do cargo'}</div>
            <div style={{ fontSize:12, color:'var(--sa-text3)', marginTop:2 }}>{PERM_LEVELS.find(p=>p.value===form.permLevel)?.label || '—'}</div>
          </div>
        </div>

        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
          <Inp label="Nome do cargo" value={form.name} onChange={e=>set('name',e.target.value)} required placeholder="Ex: Barbeiro Sênior"/>
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:6 }}>Comissão padrão (%)</label>
            <input type="number" value={form.commission} onChange={e=>set('commission',Math.min(100,Number(e.target.value)))} min={0} max={100}
              style={{ width:'100%', padding:'9px 12px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
          </div>
        </div>

        <Sel label="Nível de permissão padrão" value={form.permLevel} onChange={e=>set('permLevel',e.target.value)}
          options={PERM_LEVELS.map(p=>({value:p.value,label:p.label}))}/>

        <Txta label="Descrição do cargo" value={form.desc} onChange={e=>set('desc',e.target.value)} placeholder="Descreva as responsabilidades..." rows={2}/>

        {/* Color */}
        <div>
          <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:8 }}>Cor do cargo</label>
          <div style={{ display:'flex', gap:8, flexWrap:'wrap' }}>
            {ROLE_COLORS.map(c => (
              <button key={c} onClick={()=>set('color',c)} style={{ width:28, height:28, borderRadius:'50%', background:c, border:form.color===c?'3px solid var(--sa-text1)':'2px solid transparent', cursor:'pointer', transition:'border 150ms' }}/>
            ))}
          </div>
        </div>

        {/* Permission preview */}
        <div style={{ background:'var(--sa-surface2)', borderRadius:10, padding:'14px 16px', border:'1px solid var(--sa-border)' }}>
          <div style={{ fontSize:12, fontWeight:700, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', marginBottom:10 }}>Permissões incluídas neste nível</div>
          {(form.permLevel === 'admin'
            ? ['Acesso total','Gerenciar planos','Editar permissões','Ver todos os relatórios','Configurações da empresa']
            : form.permLevel === 'manager'
            ? ['Ver agenda completa','Relatórios de receita','Gerenciar equipe','Criar agendamentos']
            : form.permLevel === 'professional'
            ? ['Ver própria agenda','Criar agendamentos próprios','Ver histórico de clientes','Ver próprias comissões']
            : form.permLevel === 'receptionist'
            ? ['Ver agenda completa','Criar/editar agendamentos','Cadastrar clientes','Ver pagamentos']
            : ['Ver própria agenda','Criar agendamentos (supervisionado)']
          ).map(p => (
            <div key={p} style={{ display:'flex', alignItems:'center', gap:8, padding:'4px 0' }}>
              <Icon name="check" size={12} style={{ color:'#10b981', flexShrink:0 }}/>
              <span style={{ fontSize:12, color:'var(--sa-text2)' }}>{p}</span>
            </div>
          ))}
        </div>
      </div>
    </Modal>
  );
}

function RolesScreen() {
  const [roles, setRoles]     = useState(INIT_ROLES);
  const [modal, setModal]     = useState(false);
  const [editing, setEditing] = useState(null);
  const openNew  = () => { setEditing(null); setModal(true); };
  const openEdit = (r) => { setEditing(r); setModal(true); };
  const doDelete = (id) => {
    const r = roles.find(x=>x.id===id);
    if (['Administrador'].includes(r?.name)) return window.SA_TOAST('Cargo padrão não pode ser excluído','error');
    setRoles(p=>p.filter(x=>x.id!==id)); window.SA_TOAST('Cargo removido','error');
  };
  const doSave = (form) => {
    if (editing) setRoles(p=>p.map(x=>x.id===editing.id?{...x,...form}:x));
    else setRoles(p=>[...p,{...form,id:Date.now(),count:0}]);
  };

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Cargos" subtitle="Defina os cargos da sua equipe"
        actions={<Btn onClick={openNew} icon={<Icon name="plus" size={15}/>}>Novo Cargo</Btn>}/>

      <div style={{ padding:'20px 32px 0' }}>
        {/* Summary cards */}
        <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:16, marginBottom:24 }}>
          {[
            { label:'Total de cargos', value:roles.length, icon:'user'  },
            { label:'Funcionários',    value:roles.reduce((s,r)=>s+r.count,0), icon:'users' },
            { label:'Com comissão',    value:roles.filter(r=>r.commission>0).length, icon:'dollar' },
          ].map(c => {
            const counted = useCountUp(c.value);
            return (
              <div key={c.label} style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, padding:'22px 22px 0', position:'relative', overflow:'hidden', minHeight:120, display:'flex', flexDirection:'column' }}>
                <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:8, opacity:.75 }}>{c.label}</div>
                <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:32, fontWeight:800, color:'var(--sa-text1)', lineHeight:1 }}>{counted}</div>
                <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.07 }}><Icon name={c.icon} size={130} style={{ color:'var(--sa-primary)' }}/></div>
              </div>
            );
          })}
        </div>

        {/* Role cards */}
        <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:16 }}>
          {roles.map(r => {
            const pl = PERM_LEVELS.find(p=>p.value===r.permLevel);
            return (
              <div key={r.id} style={{ background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:16, overflow:'hidden', transition:'box-shadow 200ms' }}
                onMouseEnter={e=>e.currentTarget.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'}
                onMouseLeave={e=>e.currentTarget.style.boxShadow='none'}>
                {/* Color bar */}
                <div style={{ height:4, background:r.color }}/>
                <div style={{ padding:'18px 18px 0' }}>
                  <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:12 }}>
                    <div style={{ display:'flex', alignItems:'center', gap:12 }}>
                      <div style={{ width:42, height:42, borderRadius:12, background:r.color, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
                        <Icon name="user" size={18} style={{ color:'#fff' }}/>
                      </div>
                      <div>
                        <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:16, fontWeight:700, color:'var(--sa-text1)' }}>{r.name}</div>
                        <div style={{ fontSize:11, color:'var(--sa-text3)', marginTop:2 }}>{r.count} funcion{r.count===1?'ário':'ários'}</div>
                      </div>
                    </div>
                    <span style={{ fontSize:11, fontWeight:600, padding:'3px 9px', borderRadius:20, background:`${r.color}15`, color:r.color }}>{r.permLevel}</span>
                  </div>

                  <p style={{ fontSize:12, color:'var(--sa-text3)', lineHeight:1.6, margin:'0 0 14px' }}>{r.desc}</p>

                  <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:8, marginBottom:14, padding:'10px', background:'var(--sa-surface2)', borderRadius:8 }}>
                    <div style={{ textAlign:'center' }}>
                      <div style={{ fontFamily:"var(--sa-font-heading)", fontSize:20, fontWeight:800, color:'var(--sa-text1)' }}>{r.commission > 0 ? `${r.commission}%` : '—'}</div>
                      <div style={{ fontSize:10, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', marginTop:2 }}>Comissão</div>
                    </div>
                    <div style={{ textAlign:'center', borderLeft:'1px solid var(--sa-border)' }}>
                      <div style={{ fontFamily:"var(--sa-font-heading)", fontSize:12, fontWeight:700, color:'var(--sa-text1)', lineHeight:1.4, paddingTop:2 }}>{pl?.label.split('—')[0].trim() || r.permLevel}</div>
                      <div style={{ fontSize:10, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', marginTop:2 }}>Nível</div>
                    </div>
                  </div>
                </div>
                <div style={{ padding:'10px 14px', borderTop:'1px solid var(--sa-border)', display:'flex', gap:8 }}>
                  <Btn size="sm" variant="muted" fullWidth onClick={()=>openEdit(r)} icon={<Icon name="edit" size={13}/>}>Editar</Btn>
                  <Btn size="sm" variant="ghost" onClick={()=>doDelete(r.id)} icon={<Icon name="trash" size={13}/>}/>
                </div>
              </div>
            );
          })}
          {/* Add new role card */}
          <button onClick={openNew} style={{ background:'var(--sa-surface2)', border:'2px dashed var(--sa-border)', borderRadius:16, padding:'32px', cursor:'pointer', display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:10, transition:'all 200ms', fontFamily:'var(--sa-font-body)' }}
            onMouseEnter={e=>{e.currentTarget.style.borderColor='var(--sa-primary)';e.currentTarget.style.background='color-mix(in srgb,var(--sa-primary) 4%,transparent)';}}
            onMouseLeave={e=>{e.currentTarget.style.borderColor='var(--sa-border)';e.currentTarget.style.background='var(--sa-surface2)';}}>
            <div style={{ width:44, height:44, borderRadius:12, background:'color-mix(in srgb,var(--sa-primary) 10%,transparent)', display:'flex', alignItems:'center', justifyContent:'center' }}>
              <Icon name="plus" size={20} style={{ color:'var(--sa-primary)' }}/>
            </div>
            <div style={{ fontSize:14, fontWeight:600, color:'var(--sa-text2)' }}>Novo Cargo</div>
          </button>
        </div>
      </div>
      <RoleModal open={modal} onClose={()=>setModal(false)} role={editing} onSave={doSave}/>
    </div>
  );
}

Object.assign(window, { RolesScreen });
