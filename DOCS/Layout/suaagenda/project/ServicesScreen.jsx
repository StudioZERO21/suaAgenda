// ============================================================
// suaAgenda.pro — Services Screen (Cadastro de Serviços)
// ============================================================
const { useState } = React;

const SVC_COLORS = ['#1a1a1a','#d4a574','#6366f1','#10b981','#f59e0b','#ec4899','#ef4444','#0ea5e9','#8b5cf6','#14b8a6'];
const SVC_ICONS_LIST = ['scissors','star','sparkle','check','user','eye','dollar','clock','phone','globe'];

const INIT_SVCS = [
  { id:1,  name:'Corte',         price:45,  duration:30,  color:'#1a1a1a', active:true,  profIds:[1,2], desc:'Corte personalizado com técnicas modernas.' },
  { id:2,  name:'Barba',         price:35,  duration:30,  color:'#d4a574', active:true,  profIds:[1,2], desc:'Modelagem e acabamento de barba.' },
  { id:3,  name:'Corte + Barba', price:75,  duration:60,  color:'#6366f1', active:true,  profIds:[1,2], desc:'Combo completo corte e barba.' },
  { id:4,  name:'Coloração',     price:180, duration:120, color:'#ec4899', active:true,  profIds:[3],   desc:'Coloração com produtos premium.' },
  { id:5,  name:'Hidratação',    price:90,  duration:60,  color:'#10b981', active:true,  profIds:[3],   desc:'Tratamento intensivo para cabelos.' },
  { id:6,  name:'Barba + Bigode',price:50,  duration:45,  color:'#f59e0b', active:true,  profIds:[1,2], desc:'Barba e bigode aparados com precisão.' },
];

function SvcModal({ open, onClose, svc, onSave }) {
  const blank = { name:'', price:0, duration:30, color:'#1a1a1a', active:true, profIds:[], desc:'' };
  const [form, setForm] = useState(svc || blank);
  const [saving, setSaving] = useState(false);

  React.useEffect(() => { setForm(svc || blank); }, [svc]);
  const set = (k,v) => setForm(f => ({ ...f, [k]:v }));
  const toggleProf = (id) => set('profIds', form.profIds.includes(id) ? form.profIds.filter(x=>x!==id) : [...form.profIds, id]);

  const save = () => {
    if (!form.name.trim() || form.price <= 0) return window.SA_TOAST('Preencha nome e preço', 'error');
    setSaving(true);
    setTimeout(() => { setSaving(false); onSave(form); onClose(); window.SA_TOAST(svc ? 'Serviço atualizado!' : 'Serviço criado!', 'success'); }, 600);
  };

  return (
    <Modal open={open} onClose={onClose} size="md"
      title={svc ? 'Editar Serviço' : 'Novo Serviço'}
      subtitle="Configure o serviço oferecido"
      footer={<>
        <Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn>
        <Btn size="sm" loading={saving} onClick={save} icon={<Icon name="check" size={14}/>}>{svc ? 'Salvar' : 'Criar Serviço'}</Btn>
      </>}>
      <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
        {/* Preview */}
        <div style={{ display:'flex', alignItems:'center', gap:14, padding:14, background:'var(--sa-surface2)', borderRadius:12, border:'1px solid var(--sa-border)' }}>
          <div style={{ width:52, height:52, borderRadius:13, background:`${form.color}20`, border:`2px solid ${form.color}40`, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
            <Icon name="scissors" size={22} style={{ color:form.color }}/>
          </div>
          <div>
            <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:17, fontWeight:700, color:'var(--sa-text1)' }}>{form.name || 'Nome do serviço'}</div>
            <div style={{ fontSize:13, color:'var(--sa-text3)' }}>{form.duration}min · {SA_FMT.currency(form.price)}</div>
          </div>
        </div>

        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
          <Inp label="Nome do serviço" value={form.name} onChange={e=>set('name',e.target.value)} required placeholder="Ex: Corte degradê"/>
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:6 }}>Preço</label>
            <div style={{ position:'relative' }}>
              <span style={{ position:'absolute', left:12, top:'50%', transform:'translateY(-50%)', fontSize:13, color:'var(--sa-text3)', pointerEvents:'none' }}>R$</span>
              <input type="number" value={form.price} onChange={e=>set('price',Number(e.target.value))} min={0} step={5}
                style={{ width:'100%', padding:'9px 12px 9px 36px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
            </div>
          </div>
        </div>

        {/* Duration slider */}
        <div>
          <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:6 }}>
            Duração: <span style={{ color:'var(--sa-secondary)', fontWeight:800 }}>{form.duration >= 60 ? `${Math.floor(form.duration/60)}h${form.duration%60?` ${form.duration%60}min`:''}` : `${form.duration}min`}</span>
          </label>
          <input type="range" min={15} max={240} step={15} value={form.duration} onChange={e=>set('duration',Number(e.target.value))}
            style={{ width:'100%', accentColor:'var(--sa-primary)', cursor:'pointer' }}/>
          <div style={{ display:'flex', justifyContent:'space-between', fontSize:11, color:'var(--sa-text3)', marginTop:2 }}>
            <span>15min</span><span>1h</span><span>2h</span><span>4h</span>
          </div>
        </div>

        {/* Color */}
        <div>
          <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:8 }}>Cor do serviço</label>
          <div style={{ display:'flex', gap:8, flexWrap:'wrap' }}>
            {SVC_COLORS.map(c => (
              <button key={c} onClick={() => set('color', c)} style={{ width:28, height:28, borderRadius:'50%', background:c, border:form.color===c?'3px solid var(--sa-text1)':'2px solid transparent', cursor:'pointer', transition:'border 150ms' }}/>
            ))}
          </div>
        </div>

        {/* Professionals */}
        <div>
          <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:8 }}>Profissionais que realizam</label>
          <div style={{ display:'flex', gap:8 }}>
            {SA_PROFESSIONALS.map(p => (
              <button key={p.id} onClick={() => toggleProf(p.id)}
                style={{ display:'flex', alignItems:'center', gap:8, padding:'8px 14px', borderRadius:10, border:`1.5px solid ${form.profIds.includes(p.id)?p.color:'var(--sa-border)'}`, background:form.profIds.includes(p.id)?`${p.color}12`:'var(--sa-surface)', cursor:'pointer', fontFamily:'var(--sa-font-body)', transition:'all 160ms' }}>
                <Avt name={p.name} size={22} color={p.color}/>
                <span style={{ fontSize:12, fontWeight:600, color:form.profIds.includes(p.id)?p.color:'var(--sa-text2)' }}>{p.name.split(' ')[0]}</span>
              </button>
            ))}
          </div>
        </div>

        <Txta label="Descrição" value={form.desc} onChange={e=>set('desc',e.target.value)} placeholder="Descreva o serviço..." rows={2}/>

        {/* Active toggle */}
        <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'12px 16px', background:'var(--sa-surface2)', borderRadius:10, border:'1px solid var(--sa-border)' }}>
          <div>
            <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>Serviço ativo</div>
            <div style={{ fontSize:12, color:'var(--sa-text3)' }}>Aparece para agendamento online e no sistema</div>
          </div>
          <button onClick={()=>set('active',!form.active)}
            style={{ width:42, height:24, borderRadius:12, border:'none', cursor:'pointer', background:form.active?'var(--sa-primary)':'var(--sa-border)', transition:'all 200ms', position:'relative', padding:0 }}>
            <div style={{ position:'absolute', top:3, left:form.active?20:3, width:18, height:18, borderRadius:'50%', background:'#fff', transition:'left 200ms', boxShadow:'0 1px 4px rgba(0,0,0,.2)' }}/>
          </button>
        </div>
      </div>
    </Modal>
  );
}

function ServicesScreen() {
  const [svcs, setSvcs]     = useState(INIT_SVCS);
  const [modal, setModal]   = useState(false);
  const [editing, setEditing] = useState(null);
  const [search, setSearch] = useState('');

  const filtered = svcs.filter(s => !search || s.name.toLowerCase().includes(search.toLowerCase()));
  const openNew  = () => { setEditing(null); setModal(true); };
  const openEdit = (s) => { setEditing(s); setModal(true); };
  const doDelete = (id) => { setSvcs(p => p.filter(x => x.id!==id)); window.SA_TOAST('Serviço removido','error'); };
  const doToggle = (id) => { setSvcs(p => p.map(x => x.id===id ? {...x,active:!x.active} : x)); };
  const doSave   = (form) => {
    if (editing) setSvcs(p => p.map(x => x.id===editing.id ? {...x,...form} : x));
    else setSvcs(p => [...p, {...form, id:Date.now()}]);
  };

  const totalRev  = svcs.reduce((s,x) => s+x.price, 0);
  const avgDur    = Math.round(svcs.reduce((s,x)=>s+x.duration,0)/Math.max(svcs.length,1));
  const colSt = { padding:'11px 14px', fontSize:12, fontWeight:600, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', borderBottom:'1px solid var(--sa-border)', background:'var(--sa-surface2)' };
  const cellSt= { padding:'12px 14px', fontSize:13, color:'var(--sa-text1)', borderBottom:'1px solid var(--sa-border)', verticalAlign:'middle' };

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Serviços" subtitle="Gerencie os serviços oferecidos"
        actions={<Btn onClick={openNew} icon={<Icon name="plus" size={15}/>}>Novo Serviço</Btn>}/>
      <div style={{ padding:'20px 32px 0' }}>
        {/* Stat cards */}
        <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:16, marginBottom:20 }}>
          {[
            { label:'Total de serviços', value:svcs.length, icon:'scissors' },
            { label:'Ativos',            value:svcs.filter(s=>s.active).length, icon:'check' },
            { label:'Ticket médio',      value:SA_FMT.currency(Math.round(totalRev/Math.max(svcs.length,1))), icon:'dollar' },
            { label:'Duração média',     value:`${avgDur}min`, icon:'clock' },
          ].map(c => {
            const num = parseFloat(String(c.value).replace(/[^0-9.]/g,''))||0;
            const counted = useCountUp(num);
            const prefix = String(c.value).match(/^[^0-9]*/)?.[0]||'';
            const suffix = String(c.value).match(/[^0-9]*$/)?.[0]||'';
            const display = num ? `${prefix}${counted}${suffix}` : c.value;
            return (
              <div key={c.label} style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, padding:'22px 22px 0', position:'relative', overflow:'hidden', minHeight:128, display:'flex', flexDirection:'column' }}>
                <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:10, opacity:.75 }}>{c.label}</div>
                <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:30, fontWeight:800, color:'var(--sa-text1)', lineHeight:1 }}>{display}</div>
                <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.07 }}><Icon name={c.icon} size={130} style={{ color:'var(--sa-primary)' }}/></div>
              </div>
            );
          })}
        </div>

        {/* Search */}
        <div style={{ display:'flex', gap:10, marginBottom:16 }}>
          <div style={{ position:'relative', flex:1, maxWidth:320 }}>
            <Icon name="search" size={14} style={{ position:'absolute', left:11, top:'50%', transform:'translateY(-50%)', color:'var(--sa-text3)', pointerEvents:'none' }}/>
            <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Buscar serviço..."
              style={{ width:'100%', padding:'8px 12px 8px 34px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
          </div>
        </div>

        {/* Services table */}
        <Card style={{ padding:0, overflow:'hidden' }}>
          <table style={{ width:'100%', borderCollapse:'collapse' }}>
            <thead>
              <tr>{['Serviço','Preço','Duração','Profissionais','Status','Ações'].map(h=><th key={h} style={colSt}>{h}</th>)}</tr>
            </thead>
            <tbody>
              {filtered.map(svc => (
                <tr key={svc.id} onMouseEnter={e=>e.currentTarget.style.background='var(--sa-surface2)'} onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
                  <td style={cellSt}>
                    <div style={{ display:'flex', alignItems:'center', gap:12 }}>
                      <div style={{ width:36, height:36, borderRadius:9, background:`${svc.color}18`, border:`1px solid ${svc.color}30`, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
                        <Icon name="scissors" size={16} style={{ color:svc.color }}/>
                      </div>
                      <div>
                        <div style={{ fontWeight:700 }}>{svc.name}</div>
                        {svc.desc && <div style={{ fontSize:11, color:'var(--sa-text3)' }}>{svc.desc.slice(0,40)}{svc.desc.length>40?'…':''}</div>}
                      </div>
                    </div>
                  </td>
                  <td style={{ ...cellSt, fontWeight:700 }}>{SA_FMT.currency(svc.price)}</td>
                  <td style={cellSt}>{svc.duration >= 60 ? `${Math.floor(svc.duration/60)}h${svc.duration%60?` ${svc.duration%60}min`:''}` : `${svc.duration}min`}</td>
                  <td style={cellSt}>
                    <div style={{ display:'flex', gap:4 }}>
                      {svc.profIds.map(pid => {
                        const p = SA_PROFESSIONALS.find(x=>x.id===pid);
                        return p ? <Avt key={pid} name={p.name} size={24} color={p.color}/> : null;
                      })}
                    </div>
                  </td>
                  <td style={cellSt}>
                    <button onClick={()=>doToggle(svc.id)} style={{ position:'relative', width:38, height:22, borderRadius:11, border:'none', cursor:'pointer', background:svc.active?'var(--sa-primary)':'var(--sa-border)', transition:'all 200ms', padding:0 }}>
                      <div style={{ position:'absolute', top:2, left:svc.active?18:2, width:18, height:18, borderRadius:'50%', background:'#fff', transition:'left 200ms', boxShadow:'0 1px 3px rgba(0,0,0,.2)' }}/>
                    </button>
                  </td>
                  <td style={cellSt}>
                    <div style={{ display:'flex', gap:6 }}>
                      <Btn size="sm" variant="muted" onClick={()=>openEdit(svc)} icon={<Icon name="edit" size={13}/>}>Editar</Btn>
                      <Btn size="sm" variant="ghost" onClick={()=>doDelete(svc.id)} icon={<Icon name="trash" size={13}/>}/>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </Card>
      </div>
      <SvcModal open={modal} onClose={()=>setModal(false)} svc={editing} onSave={doSave}/>
    </div>
  );
}

Object.assign(window, { ServicesScreen });
