// ============================================================
// suaAgenda.pro — Staff Screen (Funcionários)
// ============================================================
const { useState, useEffect } = React;

const STAFF_ROLES = ['Administrador','Gerente','Barbeiro','Colorista','Manicure','Recepcionista','Estagiário'];
const STAFF_STATUS = ['active','inactive','vacation'];
const STATUS_CFG = {
  active:   { label:'Ativo',    color:'#10b981', bg:'rgba(16,185,129,.1)'  },
  inactive: { label:'Inativo',  color:'#ef4444', bg:'rgba(239,68,68,.08)' },
  vacation: { label:'Férias',   color:'#f59e0b', bg:'rgba(245,158,11,.1)' },
};

const INIT_STAFF = [
  { id:1, name:'João Silva',    role:'Barbeiro',     email:'joao@barbearia.com',  phone:'(11) 91111-1111', status:'active',   commission:40, since:'2020-03-15', specialties:['Degradê','Corte clássico','Navalha'],    rating:4.9, appts:89,  color:'#1a1a1a' },
  { id:2, name:'Carlos Mendes', role:'Barbeiro',     email:'carlos@barbearia.com',phone:'(11) 92222-2222', status:'active',   commission:38, since:'2021-07-01', specialties:['Barba','Bigode','Acabamentos'],          rating:4.7, appts:72,  color:'#d4a574' },
  { id:3, name:'Ana Costa',     role:'Colorista',    email:'ana@barbearia.com',   phone:'(11) 93333-3333', status:'active',   commission:42, since:'2022-01-10', specialties:['Coloração','Mechas','Tratamentos'],      rating:4.8, appts:54,  color:'#6366f1' },
  { id:4, name:'Maria Oliveira',role:'Administrador',email:'maria@barbearia.com', phone:'(11) 94444-4444', status:'active',   commission:0,  since:'2020-01-01', specialties:['Gestão','Atendimento'],                 rating:5.0, appts:0,   color:'#10b981' },
  { id:5, name:'Pedro Santos',  role:'Recepcionista',email:'pedro@barbearia.com', phone:'(11) 95555-5555', status:'vacation', commission:0,  since:'2023-05-20', specialties:['Atendimento','Agendamento'],            rating:4.6, appts:0,   color:'#f59e0b' },
  { id:6, name:'Lucas Ferreira',role:'Estagiário',   email:'lucas@barbearia.com', phone:'(11) 96666-6666', status:'inactive', commission:20, since:'2024-02-01', specialties:['Corte simples'],                       rating:4.3, appts:12,  color:'#ec4899' },
];

const PROF_COLORS = ['#1a1a1a','#d4a574','#6366f1','#10b981','#f59e0b','#ec4899','#ef4444','#0ea5e9','#8b5cf6','#14b8a6'];

// ── STAFF MODAL ───────────────────────────────────────────────
function StaffModal({ open, onClose, staff, onSave }) {
  const blank = { name:'', role:'Barbeiro', email:'', phone:'', commission:35, status:'active', since:'2026-06-06', specialties:[], color:'#1a1a1a', instagram:'', tiktok:'', facebook:'', bio:'' };
  const [form, setForm] = useState(staff || blank);
  const [saving, setSaving] = useState(false);
  const [newSpec, setNewSpec] = useState('');

  useEffect(() => { setForm(staff || blank); }, [staff]);

  const set = (k,v) => setForm(f => ({ ...f, [k]:v }));
  const addSpec = () => { if (newSpec.trim() && !form.specialties.includes(newSpec.trim())) { set('specialties',[...form.specialties, newSpec.trim()]); setNewSpec(''); } };
  const removeSpec = (s) => set('specialties', form.specialties.filter(x => x !== s));
  const save = () => {
    if (!form.name.trim() || !form.email.trim()) return window.SA_TOAST('Preencha nome e e-mail','error');
    setSaving(true);
    setTimeout(() => { setSaving(false); onSave(form); onClose(); window.SA_TOAST(staff ? 'Funcionário atualizado!' : 'Funcionário cadastrado!', 'success'); }, 700);
  };

  return (
    <Modal open={open} onClose={onClose} size="lg"
      title={staff ? 'Editar Funcionário' : 'Novo Funcionário'}
      subtitle={staff ? `Editando ${staff.name}` : 'Preencha os dados do novo funcionário'}
      footer={<>
        <Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn>
        <Btn size="sm" loading={saving} onClick={save} icon={<Icon name="check" size={14}/>}>
          {staff ? 'Salvar alterações' : 'Cadastrar funcionário'}
        </Btn>
      </>}>
      <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:20 }}>
        {/* Left */}
        <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
          {/* Avatar + photo + Color */}
          <div style={{ display:'flex', flexDirection:'column', gap:10, padding:'16px', background:'var(--sa-surface2)', borderRadius:12, border:'1px solid var(--sa-border)' }}>
            <div style={{ display:'flex', alignItems:'center', gap:16 }}>
              {/* Photo placeholder */}
              <div style={{ position:'relative', flexShrink:0 }}>
                <div style={{ width:72, height:72, borderRadius:'50%', background:`${form.color}20`, border:`2px dashed ${form.color}60`, display:'flex', alignItems:'center', justifyContent:'center', overflow:'hidden', position:'relative' }}>
                  <Avt name={form.name||'?'} size={72} color={form.color}/>
                  <div style={{ position:'absolute', inset:0, background:'rgba(0,0,0,.35)', display:'flex', alignItems:'center', justifyContent:'center', opacity:0, transition:'opacity 150ms', cursor:'pointer' }}
                    onMouseEnter={e=>e.currentTarget.style.opacity='1'} onMouseLeave={e=>e.currentTarget.style.opacity='0'}
                    onClick={()=>window.SA_TOAST('Upload de foto em breve!','info')}>
                    <Icon name="arrowUp" size={18} style={{ color:'#fff' }}/>
                  </div>
                </div>
                <button onClick={()=>window.SA_TOAST('Upload de foto!','info')} style={{ position:'absolute', bottom:-2, right:-2, width:22, height:22, borderRadius:'50%', background:'var(--sa-secondary)', border:'2px solid var(--sa-surface)', display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer' }}>
                  <Icon name="arrowUp" size={10} style={{ color:'#fff' }}/>
                </button>
              </div>
              <div style={{ flex:1 }}>
                <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', marginBottom:8 }}>Foto & Cor</div>
                <div style={{ display:'flex', gap:6, flexWrap:'wrap' }}>
                  {PROF_COLORS.map(c => (
                    <button key={c} onClick={() => set('color', c)} style={{ width:22, height:22, borderRadius:'50%', background:c, border:form.color===c?'3px solid var(--sa-text1)':'2px solid transparent', cursor:'pointer', transition:'border 150ms' }}/>
                  ))}
                </div>
                <div style={{ fontSize:11, color:'var(--sa-text3)', marginTop:6 }}>Passe o mouse na foto para trocar</div>
              </div>
            </div>
          </div>

          <Inp label="Nome completo" value={form.name} onChange={e=>set('name',e.target.value)} required placeholder="Nome do funcionário" icon={<Icon name="user" size={14}/>}/>
          <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:10 }}>
            <Sel label="Cargo / Função" value={form.role} onChange={e=>set('role',e.target.value)} options={STAFF_ROLES.map(r=>({value:r,label:r}))}/>
            <Sel label="Status" value={form.status} onChange={e=>set('status',e.target.value)}
              options={[{value:'active',label:'Ativo'},{value:'inactive',label:'Inativo'},{value:'vacation',label:'Férias'}]}/>
          </div>
          <Inp label="E-mail" value={form.email} onChange={e=>set('email',e.target.value)} type="email" required icon={<Icon name="user" size={14}/>}/>
          <Inp label="WhatsApp" value={form.phone} onChange={e=>set('phone',e.target.value)} icon={<Icon name="phone" size={14}/>}/>
        </div>

        {/* Right */}
        <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
          <Inp label="Data de admissão" value={form.since} onChange={e=>set('since',e.target.value)} type="date"/>
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:6 }}>
              Comissão: <span style={{ color:'var(--sa-secondary)', fontWeight:800 }}>{form.commission}%</span>
            </label>
            <input type="range" min={0} max={70} step={1} value={form.commission} onChange={e=>set('commission',Number(e.target.value))}
              style={{ width:'100%', accentColor:'var(--sa-primary)', cursor:'pointer' }}/>
            <div style={{ display:'flex', justifyContent:'space-between', fontSize:11, color:'var(--sa-text3)', marginTop:3 }}>
              <span>0%</span><span>35%</span><span>70%</span>
            </div>
          </div>

          {/* Specialties */}
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:8 }}>Especialidades</label>
            <div style={{ display:'flex', gap:6, flexWrap:'wrap', marginBottom:8 }}>
              {form.specialties.map(s => (
                <span key={s} style={{ display:'flex', alignItems:'center', gap:5, fontSize:12, fontWeight:600, padding:'4px 10px', borderRadius:20, background:'color-mix(in srgb,var(--sa-primary) 10%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 20%,transparent)', color:'var(--sa-primary)' }}>
                  {s}
                  <button onClick={()=>removeSpec(s)} style={{ background:'none', border:'none', cursor:'pointer', color:'var(--sa-text3)', lineHeight:1, padding:0, display:'flex', alignItems:'center' }}>
                    <Icon name="x" size={11}/>
                  </button>
                </span>
              ))}
              {form.specialties.length === 0 && <span style={{ fontSize:12, color:'var(--sa-text3)', fontStyle:'italic' }}>Nenhuma especialidade</span>}
            </div>
            <div style={{ display:'flex', gap:8 }}>
              <input value={newSpec} onChange={e=>setNewSpec(e.target.value)}
                onKeyDown={e=>e.key==='Enter'&&addSpec()}
                placeholder="Ex: Degradê, Coloração..." style={{ flex:1, padding:'8px 12px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none' }}/>
              <Btn variant="muted" size="sm" onClick={addSpec} icon={<Icon name="plus" size={13}/>}>Add</Btn>
            </div>
          </div>

          {/* Stats (edit mode) */}
          {staff && (
            <div style={{ background:'var(--sa-surface2)', borderRadius:10, padding:'14px 16px', border:'1px solid var(--sa-border)' }}>
              <div style={{ fontSize:12, fontWeight:700, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', marginBottom:10 }}>Estatísticas</div>
              {[
                { l:'Agendamentos',   v:staff.appts },
                { l:'Avaliação',      v:`★ ${staff.rating}` },
                { l:'Cliente desde',  v:SA_FMT.short(staff.since) },
              ].map(({ l, v }) => (
                <div key={l} style={{ display:'flex', justifyContent:'space-between', padding:'6px 0', borderBottom:'1px solid var(--sa-border)' }}>
                  <span style={{ fontSize:12, color:'var(--sa-text3)' }}>{l}</span>
                  <span style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)' }}>{v}</span>
                </div>
              ))}
            </div>
          )}

          {/* Social media */}
          <div style={{ background:'var(--sa-surface2)', borderRadius:10, padding:'14px 16px', border:'1px solid var(--sa-border)' }}>
            <div style={{ fontSize:12, fontWeight:700, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', marginBottom:12 }}>Redes Sociais</div>
            <div style={{ display:'flex', flexDirection:'column', gap:10 }}>
              <Inp label="Instagram" value={form.instagram||''} onChange={e=>set('instagram',e.target.value)} placeholder="@usuario" icon={<Icon name="star" size={13}/>}/>
              <Inp label="TikTok" value={form.tiktok||''} onChange={e=>set('tiktok',e.target.value)} placeholder="@usuario" icon={<Icon name="sparkle" size={13}/>}/>
              <Inp label="Facebook" value={form.facebook||''} onChange={e=>set('facebook',e.target.value)} placeholder="Nome da página" icon={<Icon name="globe" size={13}/>}/>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  );
}

// ── STAFF CARD ────────────────────────────────────────────────
function StaffCard({ s, onEdit, onDelete }) {
  const sc = STATUS_CFG[s.status];
  return (
    <div style={{ background:'color-mix(in srgb,var(--sa-primary) 6%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 12%,transparent)', borderRadius:16, padding:0, overflow:'hidden', position:'relative', transition:'box-shadow 200ms' }}
      onMouseEnter={e=>e.currentTarget.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'}
      onMouseLeave={e=>e.currentTarget.style.boxShadow='none'}>
      {/* Color stripe */}
      <div style={{ height:5, background:s.color, borderRadius:'0' }}/>
      <div style={{ padding:'20px 20px 0' }}>
        {/* Header */}
        <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:16 }}>
          <div style={{ display:'flex', gap:12, alignItems:'center' }}>
            <Avt name={s.name} size={52} color={s.color}/>
            <div>
              <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:16, fontWeight:700, color:'var(--sa-text1)' }}>{s.name}</div>
              <div style={{ fontSize:12, color:'var(--sa-text3)', marginTop:2 }}>{s.role}</div>
            </div>
          </div>
          <span style={{ fontSize:11, fontWeight:700, padding:'3px 10px', borderRadius:20, background:sc.bg, color:sc.color }}>{sc.label}</span>
        </div>

        {/* Stats row */}
        <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:8, marginBottom:14, padding:'10px', background:'var(--sa-surface)', borderRadius:10, border:'1px solid var(--sa-border)' }}>
          {[
            { l:'Agendamentos', v:s.appts },
            { l:'Comissão',     v:`${s.commission}%` },
            { l:'Avaliação',    v:`★ ${s.rating}` },
          ].map(({ l, v }) => (
            <div key={l} style={{ textAlign:'center' }}>
              <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:18, fontWeight:800, color:'var(--sa-text1)' }}>{v}</div>
              <div style={{ fontSize:10, color:'var(--sa-text3)', fontWeight:600, textTransform:'uppercase', letterSpacing:'.5px', marginTop:2 }}>{l}</div>
            </div>
          ))}
        </div>

        {/* Specialties */}
        <div style={{ display:'flex', gap:5, flexWrap:'wrap', marginBottom:16 }}>
          {s.specialties.map(sp => (
            <span key={sp} style={{ fontSize:11, fontWeight:600, padding:'3px 9px', borderRadius:20, background:`${s.color}15`, color:s.color, border:`1px solid ${s.color}30` }}>{sp}</span>
          ))}
        </div>

        {/* Contact */}
        <div style={{ display:'flex', gap:8, fontSize:12, color:'var(--sa-text3)', marginBottom:16 }}>
          <Icon name="phone" size={13} style={{ color:'var(--sa-text3)', flexShrink:0 }}/>{s.phone}
          <span>·</span>
          <Icon name="user" size={13} style={{ color:'var(--sa-text3)', flexShrink:0 }}/>{s.email}
        </div>
      </div>

      {/* Footer actions */}
      <div style={{ padding:'10px 14px', borderTop:'1px solid var(--sa-border)', display:'flex', gap:8 }}>
        <Btn size="sm" variant="muted" fullWidth onClick={() => onEdit(s)} icon={<Icon name="edit" size={13}/>}>Editar</Btn>
        <Btn size="sm" variant="ghost" onClick={() => onDelete(s.id)} icon={<Icon name="trash" size={13}/>}/>
      </div>
    </div>
  );
}

// ── MAIN SCREEN ───────────────────────────────────────────────
function StaffScreen() {
  const [staff, setStaff]       = useState(INIT_STAFF);
  const [modal, setModal]       = useState(false);
  const [editing, setEditing]   = useState(null);
  const [search, setSearch]     = useState('');
  const [filterRole, setFilterRole] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');
  const [view, setView]         = useState('cards'); // 'cards' | 'table'

  const filtered = staff.filter(s => {
    const q = search.toLowerCase();
    if (q && !s.name.toLowerCase().includes(q) && !s.role.toLowerCase().includes(q)) return false;
    if (filterRole !== 'all' && s.role !== filterRole) return false;
    if (filterStatus !== 'all' && s.status !== filterStatus) return false;
    return true;
  });

  const openNew  = () => { setEditing(null); setModal(true); };
  const openEdit = (s) => { setEditing(s); setModal(true); };
  const doDelete = (id) => { setStaff(p => p.filter(x => x.id !== id)); window.SA_TOAST('Funcionário removido','error'); };
  const doSave   = (form) => {
    if (editing) {
      setStaff(p => p.map(x => x.id === editing.id ? { ...x, ...form } : x));
    } else {
      setStaff(p => [...p, { ...form, id: Date.now(), appts:0, rating:5.0 }]);
    }
  };

  const active  = staff.filter(s=>s.status==='active').length;
  const vacation= staff.filter(s=>s.status==='vacation').length;
  const totalComm = Math.round(staff.filter(s=>s.status==='active').reduce((acc,s)=>acc+s.commission,0)/Math.max(active,1));

  const colSt  = { padding:'11px 14px', fontSize:12, fontWeight:600, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', borderBottom:'1px solid var(--sa-border)', background:'var(--sa-surface2)' };
  const cellSt = { padding:'11px 14px', fontSize:13, color:'var(--sa-text1)', borderBottom:'1px solid var(--sa-border)', verticalAlign:'middle' };

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Funcionários" subtitle="Gerencie sua equipe e comissões"
        actions={<Btn onClick={openNew} icon={<Icon name="plus" size={15}/>}>Novo Funcionário</Btn>}/>

      <div style={{ padding:'20px 32px 0' }}>
        {/* Stat cards */}
        <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:16, marginBottom:20 }}>
          {[
            { label:'Total de funcionários', value:staff.length,   icon:'users',    trend:null },
            { label:'Ativos',                value:active,         icon:'check',    trend:null },
            { label:'De férias',             value:vacation,       icon:'clock',    trend:null },
            { label:'Comissão média',        value:`${totalComm}%`,icon:'dollar',   trend:null },
          ].map(c => {
            const num = typeof c.value === 'number' ? c.value : parseFloat(String(c.value)) || 0;
            const counted = useCountUp(num);
            const display = typeof c.value === 'number' ? counted : String(c.value).replace(/\d+/, String(counted));
            return (
              <div key={c.label} style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, padding:'22px 22px 0', position:'relative', overflow:'hidden', minHeight:130, display:'flex', flexDirection:'column' }}>
                <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:10, opacity:.75 }}>{c.label}</div>
                <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:32, fontWeight:800, color:'var(--sa-text1)', lineHeight:1, letterSpacing:'-1px' }}>{display}</div>
                <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.07, pointerEvents:'none' }}>
                  <Icon name={c.icon} size={130} style={{ color:'var(--sa-primary)' }}/>
                </div>
              </div>
            );
          })}
        </div>

        {/* Filter bar */}
        <div style={{ display:'flex', gap:10, alignItems:'center', marginBottom:20, flexWrap:'wrap' }}>
          <div style={{ position:'relative', flex:1, maxWidth:300 }}>
            <Icon name="search" size={14} style={{ position:'absolute', left:11, top:'50%', transform:'translateY(-50%)', color:'var(--sa-text3)', pointerEvents:'none' }}/>
            <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Buscar por nome ou cargo..."
              style={{ width:'100%', padding:'8px 12px 8px 34px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
          </div>
          <select value={filterRole} onChange={e=>setFilterRole(e.target.value)}
            style={{ fontSize:13, padding:'8px 12px', border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', cursor:'pointer' }}>
            <option value="all">Todos os cargos</option>
            {STAFF_ROLES.map(r=><option key={r} value={r}>{r}</option>)}
          </select>
          <select value={filterStatus} onChange={e=>setFilterStatus(e.target.value)}
            style={{ fontSize:13, padding:'8px 12px', border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', cursor:'pointer' }}>
            <option value="all">Todos os status</option>
            <option value="active">Ativo</option>
            <option value="inactive">Inativo</option>
            <option value="vacation">Férias</option>
          </select>
          <div style={{ marginLeft:'auto', display:'flex', gap:0, border:'1px solid var(--sa-border)', borderRadius:8, overflow:'hidden' }}>
            {[['cards','dashboard'],['table','filter']].map(([v,icon])=>(
              <button key={v} onClick={()=>setView(v)}
                style={{ padding:'8px 12px', background:view===v?'var(--sa-primary)':'var(--sa-surface)', border:'none', cursor:'pointer', borderRight:v==='cards'?'1px solid var(--sa-border)':'none', display:'flex', alignItems:'center', color:view===v?'#fff':'var(--sa-text2)' }}>
                <Icon name={icon} size={15}/>
              </button>
            ))}
          </div>
        </div>

        {/* Results */}
        {filtered.length === 0 ? (
          <div style={{ textAlign:'center', padding:'60px', color:'var(--sa-text3)', fontSize:14 }}>
            <Icon name="users" size={40} style={{ margin:'0 auto 16px', display:'block', opacity:.3 }}/>
            Nenhum funcionário encontrado
          </div>
        ) : view === 'cards' ? (
          <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:16 }}>
            {filtered.map(s => <StaffCard key={s.id} s={s} onEdit={openEdit} onDelete={doDelete}/>)}
          </div>
        ) : (
          <Card style={{ padding:0, overflow:'hidden' }}>
            <table style={{ width:'100%', borderCollapse:'collapse' }}>
              <thead>
                <tr>{['Funcionário','Cargo','Status','Comissão','Agendamentos','Avaliação','Ações'].map(h=><th key={h} style={colSt}>{h}</th>)}</tr>
              </thead>
              <tbody>
                {filtered.map(s=>{
                  const sc=STATUS_CFG[s.status];
                  return (
                    <tr key={s.id} onMouseEnter={e=>e.currentTarget.style.background='var(--sa-surface2)'} onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
                      <td style={cellSt}>
                        <div style={{ display:'flex', alignItems:'center', gap:10 }}>
                          <Avt name={s.name} size={34} color={s.color}/>
                          <div>
                            <div style={{ fontWeight:600 }}>{s.name}</div>
                            <div style={{ fontSize:11, color:'var(--sa-text3)' }}>{s.email}</div>
                          </div>
                        </div>
                      </td>
                      <td style={cellSt}>{s.role}</td>
                      <td style={cellSt}><span style={{ fontSize:11, fontWeight:600, padding:'3px 9px', borderRadius:20, background:sc.bg, color:sc.color }}>{sc.label}</span></td>
                      <td style={{ ...cellSt, fontWeight:700 }}>{s.commission}%</td>
                      <td style={cellSt}>{s.appts}</td>
                      <td style={cellSt}>★ {s.rating}</td>
                      <td style={cellSt}>
                        <div style={{ display:'flex', gap:6 }}>
                          <Btn size="sm" variant="muted" onClick={()=>openEdit(s)} icon={<Icon name="edit" size={13}/>}>Editar</Btn>
                          <Btn size="sm" variant="ghost" onClick={()=>doDelete(s.id)} icon={<Icon name="trash" size={13}/>}/>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </Card>
        )}
      </div>

      <StaffModal open={modal} onClose={()=>setModal(false)} staff={editing} onSave={doSave}/>
    </div>
  );
}

Object.assign(window, { StaffScreen });
