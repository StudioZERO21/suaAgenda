// ============================================================
// suaAgenda.pro — Modals v3: Notifications, Company (tabs), Profile
// ============================================================
const { useState, useEffect, useRef } = React;

// ── NOTIFICATION DATA ─────────────────────────────────────────
const NOTIF_DATA = [
  { id:1, type:'booking',  title:'Novo agendamento',       msg:'Miguel Santos agendou Corte + Barba — 14:00',           time:'5min',  read:false },
  { id:2, type:'cancel',   title:'Cancelamento',           msg:'Rafael Costa cancelou agendamento das 10:30 de amanhã', time:'1h',    read:false },
  { id:3, type:'pending',  title:'Confirmação pendente',   msg:'Bruno Lima aguarda confirmação para amanhã às 9:00',     time:'2h',    read:false },
  { id:4, type:'system',   title:'Limite de WhatsApp',     msg:'Você usou 71% do limite mensal de mensagens',           time:'3h',    read:true  },
  { id:5, type:'booking',  title:'Agendamento confirmado', msg:'Pedro Oliveira confirmou Coloração com Ana Costa',      time:'4h',    read:true  },
  { id:6, type:'review',   title:'Nova avaliação',         msg:'Carlos Mendes recebeu ★★★★★ de Rodrigo Alves',        time:'6h',    read:true  },
];
const NOTIF_ICONS  = { booking:'calendar', cancel:'x', pending:'clock', system:'bell', review:'star' };
const NOTIF_COLORS = { booking:'#10b981',  cancel:'#ef4444', pending:'#f59e0b', system:'#6366f1', review:'#d4a574' };

function NotificationsPanel({ onClose }) {
  const [notifs, setNotifs] = useState(NOTIF_DATA);
  const ref = useRef(null);
  useEffect(() => {
    const fn = e => { if (ref.current && !ref.current.contains(e.target)) onClose(); };
    setTimeout(() => document.addEventListener('mousedown', fn), 0);
    return () => document.removeEventListener('mousedown', fn);
  }, [onClose]);
  const unread = notifs.filter(n => !n.read).length;
  const markAll = () => setNotifs(n => n.map(x => ({ ...x, read:true })));
  return (
    <div ref={ref} style={{ position:'absolute', top:56, right:24, width:360, background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:14, boxShadow:'0 12px 40px rgba(0,0,0,.15)', zIndex:500, animation:'sa-modal-in 200ms ease', overflow:'hidden' }}>
      <div style={{ padding:'16px 18px 12px', display:'flex', justifyContent:'space-between', alignItems:'center', borderBottom:'1px solid var(--sa-border)' }}>
        <div style={{ display:'flex', alignItems:'center', gap:8 }}>
          <span style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:14, fontWeight:700, color:'var(--sa-text1)' }}>Notificações</span>
          {unread > 0 && <span style={{ fontSize:10, fontWeight:700, color:'#fff', background:'#ef4444', borderRadius:20, padding:'2px 7px' }}>{unread}</span>}
        </div>
        {unread > 0 && <button onClick={markAll} style={{ fontSize:12, color:'var(--sa-secondary)', fontWeight:600, background:'none', border:'none', cursor:'pointer', fontFamily:"var(--sa-font-body)" }}>Marcar todas como lidas</button>}
      </div>
      <div style={{ maxHeight:380, overflowY:'auto' }}>
        {notifs.map((n,i) => (
          <div key={n.id}
            onClick={() => setNotifs(prev => prev.map(x => x.id===n.id?{...x,read:true}:x))}
            style={{ display:'flex', gap:12, padding:'12px 18px', cursor:'pointer', background:n.read?'transparent':'color-mix(in srgb,var(--sa-primary) 4%,transparent)', borderBottom:i<notifs.length-1?'1px solid var(--sa-border)':'none', transition:'background 150ms' }}
            onMouseEnter={e=>e.currentTarget.style.background='var(--sa-surface2)'}
            onMouseLeave={e=>e.currentTarget.style.background=n.read?'transparent':'color-mix(in srgb,var(--sa-primary) 4%,transparent)'}>
            <div style={{ width:36, height:36, borderRadius:'50%', background:`${NOTIF_COLORS[n.type]}18`, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0, marginTop:1 }}>
              <Icon name={NOTIF_ICONS[n.type]} size={15} style={{ color:NOTIF_COLORS[n.type] }}/>
            </div>
            <div style={{ flex:1, minWidth:0 }}>
              <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', gap:8 }}>
                <span style={{ fontSize:13, fontWeight:n.read?500:700, color:'var(--sa-text1)', lineHeight:1.3 }}>{n.title}</span>
                <span style={{ fontSize:11, color:'var(--sa-text3)', whiteSpace:'nowrap', marginTop:1 }}>{n.time}</span>
              </div>
              <p style={{ fontSize:12, color:'var(--sa-text3)', margin:'3px 0 0', lineHeight:1.5, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' }}>{n.msg}</p>
            </div>
            {!n.read && <div style={{ width:7, height:7, borderRadius:'50%', background:'var(--sa-secondary)', flexShrink:0, marginTop:6 }}/>}
          </div>
        ))}
      </div>
      <div style={{ padding:'10px 18px', borderTop:'1px solid var(--sa-border)', textAlign:'center' }}>
        <span style={{ fontSize:12, color:'var(--sa-secondary)', fontWeight:600, cursor:'pointer' }}>Ver todas as notificações →</span>
      </div>
    </div>
  );
}

// ── TAB BAR helper ────────────────────────────────────────────
function ModalTabs({ tabs, active, onChange }) {
  return (
    <div style={{ display:'flex', gap:0, borderBottom:'1px solid var(--sa-border)', marginBottom:20 }}>
      {tabs.map(t => (
        <button key={t.id} onClick={() => onChange(t.id)} style={{
          display:'flex', alignItems:'center', gap:7,
          padding:'10px 16px', border:'none', cursor:'pointer',
          background:'transparent', fontFamily:"var(--sa-font-body,'Inter',sans-serif)",
          fontSize:13, fontWeight:active===t.id?600:500,
          color:active===t.id?'var(--sa-primary)':'var(--sa-text3)',
          borderBottom:active===t.id?'2px solid var(--sa-primary)':'2px solid transparent',
          marginBottom:-1, transition:'all 160ms',
        }}>
          <Icon name={t.icon} size={14}/>{t.label}
        </button>
      ))}
    </div>
  );
}

// ── COMPANY MODAL (Tabbed) ─────────────────────────────────────
const SEGMENTS = ['Barbearia','Salão de Beleza','Clínica Estética','Tatuagem','Personal Trainer','Nail Designer','Cabeleireiro','Manicure','Outra'];
const WEEKDAYS = [['seg','Segunda'],['ter','Terça'],['qua','Quarta'],['qui','Quinta'],['sex','Sexta'],['sab','Sábado']];

function CompanyModal({ open, onClose }) {
  const [tab, setTab] = useState('dados');
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({
    name:'Barbearia Style', segment:'Barbearia',
    phone:'(11) 99999-0000', email:'contato@barbearia.com',
    address:'Rua das Flores, 123 — Jardim Paulista, SP',
    description:'Barbearia premium com os melhores profissionais. Atendimento personalizado desde 2018.',
    instagram:'@barbeariastyle', whatsapp:'(11) 99999-0000',
    slug:'barbearia-style',
    hours:{ seg:['08:00','20:00',true], ter:['08:00','20:00',true], qua:['08:00','20:00',true],
             qui:['08:00','20:00',true], sex:['08:00','20:00',true], sab:['08:00','16:00',true] },
    // advanced
    lgpd:true, confirmReq:false, autoReminder:true, reminderH:24,
    cancelPolicy:'Cancelamentos com menos de 2h de antecedência não são reembolsados.',
    maxAdvanceDays:60, minAdvanceMins:30,
  });
  const set = (k,v) => setForm(f => ({ ...f, [k]:v }));
  const setHour = (day,idx,v) => setForm(f => ({ ...f, hours:{ ...f.hours, [day]:f.hours[day].map((x,i)=>i===idx?v:x) } }));

  const save = () => {
    setSaving(true);
    setTimeout(() => { setSaving(false); onClose(); window.SA_TOAST('Configurações salvas!','success'); }, 900);
  };

  const TABS = [
    { id:'dados',   label:'Dados',       icon:'user'    },
    { id:'hours',   label:'Horários',    icon:'clock'   },
    { id:'link',    label:'Link Público',icon:'globe'   },
    { id:'advanced',label:'Avançado',    icon:'settings'},
  ];

  return (
    <Modal open={open} onClose={onClose} title="Configurações da Empresa" size="lg"
      footer={<>
        <Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn>
        <Btn size="sm" loading={saving} onClick={save} icon={<Icon name="check" size={14}/>}>Salvar</Btn>
      </>}>
      <ModalTabs tabs={TABS} active={tab} onChange={setTab}/>

      {/* ── TAB: DADOS ──────────────────────────────────── */}
      {tab === 'dados' && (
        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:20 }}>
          <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
            {/* Logo */}
            <div>
              <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:8 }}>Logo</label>
              <div style={{ display:'flex', alignItems:'center', gap:14 }}>
                <div style={{ width:64, height:64, borderRadius:14, background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'2px dashed var(--sa-border)', display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer', flexShrink:0 }}>
                  <Icon name="scissors" size={20} style={{ color:'var(--sa-secondary)' }}/>
                </div>
                <div>
                  <Btn variant="muted" size="sm" onClick={()=>window.SA_TOAST('Upload em breve!','info')} icon={<Icon name="arrowUp" size={13}/>}>Upload</Btn>
                  <p style={{ fontSize:11, color:'var(--sa-text3)', margin:'5px 0 0' }}>PNG/JPG · máx 2MB · 400×400px</p>
                </div>
              </div>
            </div>
            <Inp label="Nome da Empresa" value={form.name} onChange={e=>set('name',e.target.value)} required/>
            <Sel label="Segmento" value={form.segment} onChange={e=>set('segment',e.target.value)}
              options={SEGMENTS.map(s=>({value:s,label:s}))}/>
            <Inp label="Endereço" value={form.address} onChange={e=>set('address',e.target.value)} icon={<Icon name="map" size={14}/>}/>
          {/* Social media */}
          <div style={{ marginTop:4 }}>
            <label style={{ fontSize:12, fontWeight:700, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', display:'block', marginBottom:10 }}>Redes Sociais</label>
            <div style={{ display:'flex', flexDirection:'column', gap:9 }}>
              <Inp label="WhatsApp" value={form.whatsapp||''} onChange={e=>set('whatsapp',e.target.value)} placeholder="(11) 99999-0000" icon={<Icon name="phone" size={13}/>}/>
              <Inp label="Instagram" value={form.instagram||''} onChange={e=>set('instagram',e.target.value)} placeholder="@barbearia" icon={<Icon name="star" size={13}/>}/>
              <Inp label="Facebook" value={form.facebook||''} onChange={e=>set('facebook',e.target.value)} placeholder="Nome da página" icon={<Icon name="globe" size={13}/>}/>
              <Inp label="TikTok" value={form.tiktok||''} onChange={e=>set('tiktok',e.target.value)} placeholder="@barbearia" icon={<Icon name="sparkle" size={13}/>}/>
              <Inp label="YouTube" value={form.youtube||''} onChange={e=>set('youtube',e.target.value)} placeholder="@canal" icon={<Icon name="eye" size={13}/>}/>
            </div>
          </div>
          </div>
          <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
            <Inp label="Telefone / WhatsApp" value={form.phone} onChange={e=>set('phone',e.target.value)} icon={<Icon name="phone" size={14}/>}/>
            <Inp label="E-mail" value={form.email} onChange={e=>set('email',e.target.value)} type="email" icon={<Icon name="user" size={14}/>}/>
            <Inp label="Instagram" value={form.instagram} onChange={e=>set('instagram',e.target.value)} placeholder="@usuario"/>
            <Txta label="Descrição / Bio" value={form.description} onChange={e=>set('description',e.target.value)} rows={4}
              helper={`${form.description.length}/300`}/>
          </div>
        </div>
      )}

      {/* ── TAB: HORÁRIOS ───────────────────────────────── */}
      {tab === 'hours' && (
        <div>
          <p style={{ fontSize:13, color:'var(--sa-text3)', marginBottom:16 }}>Defina os dias e horários de atendimento. Horários fechados não aparecerão no link de agendamento.</p>
          <div style={{ display:'flex', flexDirection:'column', gap:8 }}>
            {WEEKDAYS.map(([day,label]) => {
              const [open,close,active] = form.hours[day] || ['08:00','20:00',false];
              return (
                <div key={day} style={{ display:'flex', alignItems:'center', gap:12, padding:'12px 16px', borderRadius:10, background:active?'color-mix(in srgb,var(--sa-primary) 5%,transparent)':'var(--sa-surface2)', border:`1px solid ${active?'color-mix(in srgb,var(--sa-primary) 15%,transparent)':'var(--sa-border)'}`, transition:'all 180ms' }}>
                  <input type="checkbox" checked={active} onChange={e=>setHour(day,2,e.target.checked)}
                    style={{ width:16, height:16, accentColor:'var(--sa-primary)', cursor:'pointer', flexShrink:0 }}/>
                  <span style={{ fontSize:13, fontWeight:600, color:active?'var(--sa-text1)':'var(--sa-text3)', width:72, flexShrink:0 }}>{label}</span>
                  {active ? (
                    <div style={{ display:'flex', alignItems:'center', gap:8, flex:1 }}>
                      <input type="time" value={open} onChange={e=>setHour(day,0,e.target.value)}
                        style={{ fontSize:13, border:'1px solid var(--sa-border)', borderRadius:7, padding:'5px 9px', background:'var(--sa-surface)', color:'var(--sa-text1)', flex:1 }}/>
                      <span style={{ fontSize:12, color:'var(--sa-text3)' }}>às</span>
                      <input type="time" value={close} onChange={e=>setHour(day,1,e.target.value)}
                        style={{ fontSize:13, border:'1px solid var(--sa-border)', borderRadius:7, padding:'5px 9px', background:'var(--sa-surface)', color:'var(--sa-text1)', flex:1 }}/>
                    </div>
                  ) : (
                    <span style={{ fontSize:12, color:'var(--sa-text3)', fontStyle:'italic' }}>Fechado</span>
                  )}
                </div>
              );
            })}
            <div style={{ padding:'12px 16px', borderRadius:10, background:'var(--sa-surface2)', border:'1px solid var(--sa-border)', display:'flex', alignItems:'center', gap:12 }}>
              <input type="checkbox" disabled style={{ width:16, height:16, opacity:.3 }}/>
              <span style={{ fontSize:13, color:'var(--sa-text3)', fontStyle:'italic' }}>Domingo — Fechado</span>
            </div>
          </div>
        </div>
      )}

      {/* ── TAB: LINK PÚBLICO ───────────────────────────── */}
      {tab === 'link' && (
        <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
          {/* URL */}
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:8 }}>Link de Agendamento</label>
            <div style={{ display:'flex', gap:8 }}>
              <div style={{ flex:1, display:'flex', alignItems:'center', gap:0, border:'1px solid var(--sa-border)', borderRadius:8, overflow:'hidden', background:'var(--sa-surface2)' }}>
                <span style={{ padding:'10px 12px', fontSize:12, color:'var(--sa-text3)', background:'var(--sa-surface2)', borderRight:'1px solid var(--sa-border)', whiteSpace:'nowrap' }}>suaagenda.pro/</span>
                <input value={form.slug} onChange={e=>set('slug',e.target.value.toLowerCase().replace(/\s+/g,'-').replace(/[^a-z0-9-]/g,''))}
                  style={{ flex:1, padding:'10px 12px', border:'none', background:'var(--sa-surface)', fontSize:13, color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none' }}/>
              </div>
              <Btn variant="muted" size="sm" onClick={()=>window.SA_TOAST('Link copiado!','success')} icon={<Icon name="link" size={13}/>}>Copiar</Btn>
            </div>
            <p style={{ fontSize:12, color:'var(--sa-text3)', marginTop:6 }}>Este é o link que seus clientes usarão para agendar online.</p>
          </div>

          {/* QR code preview */}
          <div style={{ display:'flex', gap:16, padding:'20px', background:'var(--sa-surface2)', borderRadius:12, border:'1px solid var(--sa-border)', alignItems:'center' }}>
            <div style={{ width:80, height:80, background:'var(--sa-border)', borderRadius:8, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
              <svg width="60" height="60" viewBox="0 0 100 100" style={{ opacity:.3 }}>
                <rect x="0"  y="0"  width="40" height="40" fill="var(--sa-primary)"/>
                <rect x="60" y="0"  width="40" height="40" fill="var(--sa-primary)"/>
                <rect x="0"  y="60" width="40" height="40" fill="var(--sa-primary)"/>
                <rect x="10" y="10" width="20" height="20" fill="var(--sa-surface)"/>
                <rect x="70" y="10" width="20" height="20" fill="var(--sa-surface)"/>
                <rect x="10" y="70" width="20" height="20" fill="var(--sa-surface)"/>
                <rect x="60" y="60" width="8"  height="8"  fill="var(--sa-primary)"/>
                <rect x="52" y="52" width="6"  height="6"  fill="var(--sa-primary)"/>
                <rect x="70" y="70" width="10" height="10" fill="var(--sa-primary)"/>
              </svg>
            </div>
            <div>
              <div style={{ fontSize:14, fontWeight:600, color:'var(--sa-text1)', marginBottom:4 }}>QR Code do seu negócio</div>
              <p style={{ fontSize:12, color:'var(--sa-text3)', margin:'0 0 10px', lineHeight:1.6 }}>Imprima e disponibilize no balcão para facilitar o agendamento dos seus clientes.</p>
              <Btn variant="muted" size="sm" onClick={()=>window.SA_TOAST('Download do QR Code em breve!','info')} icon={<Icon name="arrowDown" size={13}/>}>Baixar QR Code</Btn>
            </div>
          </div>

          {/* Social share */}
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:10 }}>Compartilhar</label>
            <div style={{ display:'flex', gap:8 }}>
              {[
                { label:'WhatsApp', color:'#25D366', icon:'phone' },
                { label:'Instagram', color:'#E1306C', icon:'star' },
                { label:'Copiar link', color:'var(--sa-primary)', icon:'link' },
              ].map(s => (
                <button key={s.label} onClick={()=>window.SA_TOAST(`Compartilhando via ${s.label}!`,'info')}
                  style={{ display:'flex', alignItems:'center', gap:7, padding:'8px 16px', borderRadius:8, border:`1.5px solid ${s.color}20`, background:`${s.color}10`, color:s.color, cursor:'pointer', fontSize:13, fontWeight:600, fontFamily:"var(--sa-font-body)" }}>
                  <Icon name={s.icon} size={14}/>{s.label}
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ── TAB: AVANÇADO ───────────────────────────────── */}
      {tab === 'advanced' && (
        <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
          {/* Booking rules */}
          <Card style={{ padding:18 }}>
            <h4 style={{ fontSize:14, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 14px', fontFamily:"var(--sa-font-heading)" }}>Regras de Agendamento</h4>
            <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
              <Sel label="Antecedência mínima" value={String(form.minAdvanceMins)}
                onChange={e=>set('minAdvanceMins',Number(e.target.value))}
                options={[{value:'0',label:'Sem restrição'},{value:'30',label:'30 minutos'},{value:'60',label:'1 hora'},{value:'120',label:'2 horas'},{value:'1440',label:'1 dia'}]}/>
              <Sel label="Agendamento máximo" value={String(form.maxAdvanceDays)}
                onChange={e=>set('maxAdvanceDays',Number(e.target.value))}
                options={[{value:'7',label:'7 dias'},{value:'15',label:'15 dias'},{value:'30',label:'30 dias'},{value:'60',label:'60 dias'},{value:'90',label:'90 dias'}]}/>
            </div>
          </Card>
          {/* Notifications & confirmation */}
          <Card style={{ padding:18 }}>
            <h4 style={{ fontSize:14, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 14px', fontFamily:"var(--sa-font-heading)" }}>Confirmações & Lembretes</h4>
            {[
              { key:'confirmReq', label:'Exigir confirmação do cliente', sub:'O agendamento só é confirmado após resposta do cliente' },
              { key:'autoReminder', label:'Lembrete automático', sub:'Enviar WhatsApp/SMS antes do horário' },
            ].map(opt => (
              <div key={opt.key} style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'10px 0', borderBottom:'1px solid var(--sa-border)' }}>
                <div>
                  <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>{opt.label}</div>
                  <div style={{ fontSize:12, color:'var(--sa-text3)' }}>{opt.sub}</div>
                </div>
                <button onClick={()=>set(opt.key,!form[opt.key])}
                  style={{ width:42, height:24, borderRadius:12, border:'none', cursor:'pointer', background:form[opt.key]?'var(--sa-primary)':'var(--sa-border)', transition:'all 200ms', position:'relative', flexShrink:0 }}>
                  <div style={{ position:'absolute', top:3, left:form[opt.key]?20:3, width:18, height:18, borderRadius:'50%', background:'#fff', transition:'left 200ms' }}/>
                </button>
              </div>
            ))}
            {form.autoReminder && (
              <div style={{ marginTop:12 }}>
                <Sel label="Enviar lembrete" value={String(form.reminderH)}
                  onChange={e=>set('reminderH',Number(e.target.value))}
                  options={[{value:'1',label:'1 hora antes'},{value:'2',label:'2 horas antes'},{value:'24',label:'24 horas antes'},{value:'48',label:'48 horas antes'}]}/>
              </div>
            )}
          </Card>
          {/* Cancellation policy */}
          <Card style={{ padding:18 }}>
            <h4 style={{ fontSize:14, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 10px', fontFamily:"var(--sa-font-heading)" }}>Política de Cancelamento</h4>
            <Txta label="" value={form.cancelPolicy} onChange={e=>set('cancelPolicy',e.target.value)} rows={3} placeholder="Descreva sua política..."/>
            <p style={{ fontSize:11, color:'var(--sa-text3)', marginTop:6 }}>Exibida na página pública de agendamento.</p>
          </Card>
          {/* LGPD */}
          <Card style={{ padding:18 }}>
            <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center' }}>
              <div>
                <h4 style={{ fontSize:14, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px', fontFamily:"var(--sa-font-heading)" }}>Conformidade LGPD</h4>
                <p style={{ fontSize:12, color:'var(--sa-text3)', margin:0 }}>Exibir checkbox de consentimento no formulário público</p>
              </div>
              <button onClick={()=>set('lgpd',!form.lgpd)}
                style={{ width:42, height:24, borderRadius:12, border:'none', cursor:'pointer', background:form.lgpd?'var(--sa-primary)':'var(--sa-border)', transition:'all 200ms', position:'relative', flexShrink:0 }}>
                <div style={{ position:'absolute', top:3, left:form.lgpd?20:3, width:18, height:18, borderRadius:'50%', background:'#fff', transition:'left 200ms' }}/>
              </button>
            </div>
          </Card>
        </div>
      )}
    </Modal>
  );
}

// ── PROFILE MODAL ─────────────────────────────────────────────
function ProfileModal({ open, onClose }) {
  const [form, setForm] = useState({ name:'Maria Oliveira', email:'maria@barbearia.com', phone:'(11) 91234-5678', role:'Administradora' });
  const [pwd, setPwd]   = useState({ current:'', new_:'', confirm:'' });
  const [showPwd, setShowPwd] = useState(false);
  const [saving, setSaving]   = useState(false);
  const set   = (k,v) => setForm(f => ({ ...f, [k]:v }));
  const setPw = (k,v) => setPwd(p => ({ ...p, [k]:v }));
  const save = () => { setSaving(true); setTimeout(()=>{ setSaving(false); onClose(); window.SA_TOAST('Perfil atualizado!','success'); },900); };
  const ROLES = ['Administradora','Recepcionista','Gerente','Profissional'];

  return (
    <Modal open={open} onClose={onClose} title="Meu Perfil" subtitle="Configurações da sua conta" size="md"
      footer={<><Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn><Btn size="sm" loading={saving} onClick={save} icon={<Icon name="check" size={14}/>}>Salvar</Btn></>}>
      <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
        {/* Avatar */}
        <div style={{ display:'flex', alignItems:'center', gap:20 }}>
          <div style={{ position:'relative' }}>
            <Avt name={form.name} size={72} color="var(--sa-primary)"/>
            <button onClick={()=>window.SA_TOAST('Upload em breve!','info')}
              style={{ position:'absolute', bottom:0, right:0, width:24, height:24, borderRadius:'50%', background:'var(--sa-secondary)', border:'2px solid var(--sa-surface)', display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer' }}>
              <Icon name="edit" size={11} style={{ color:'#fff' }}/>
            </button>
          </div>
          <div>
            <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:18, fontWeight:700, color:'var(--sa-text1)' }}>{form.name}</div>
            <div style={{ fontSize:13, color:'var(--sa-text3)' }}>{form.role} · Plano Crescimento</div>
            <div style={{ fontSize:12, color:'#10b981', marginTop:3, fontWeight:600 }}>● Online</div>
          </div>
        </div>
        <div style={{ height:1, background:'var(--sa-border)' }}/>
        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:14 }}>
          <Inp label="Nome Completo" value={form.name}  onChange={e=>set('name',e.target.value)} required icon={<Icon name="user" size={14}/>}/>
          <Inp label="E-mail"        value={form.email} onChange={e=>set('email',e.target.value)} type="email" icon={<Icon name="user" size={14}/>}/>
          <Inp label="WhatsApp"      value={form.phone} onChange={e=>set('phone',e.target.value)} icon={<Icon name="phone" size={14}/>}/>
          <Sel label="Cargo / Função" value={form.role} onChange={e=>set('role',e.target.value)} options={ROLES.map(r=>({value:r,label:r}))}/>
        </div>
        <div style={{ height:1, background:'var(--sa-border)' }}/>
        {/* Password */}
        <div>
          <button onClick={()=>setShowPwd(s=>!s)}
            style={{ display:'flex', alignItems:'center', gap:8, background:'none', border:'none', cursor:'pointer', padding:0, fontFamily:"var(--sa-font-body)" }}>
            <Icon name={showPwd?'chevD':'chevR'} size={14} style={{ color:'var(--sa-text3)' }}/>
            <span style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>Alterar Senha</span>
          </button>
          {showPwd && (
            <div style={{ display:'flex', flexDirection:'column', gap:12, marginTop:14 }}>
              <Inp label="Senha Atual"          value={pwd.current}  onChange={e=>setPw('current',e.target.value)}  type="password" placeholder="••••••••"/>
              <Inp label="Nova Senha"           value={pwd.new_}     onChange={e=>setPw('new_',e.target.value)}     type="password" placeholder="Mínimo 6 caracteres"/>
              <Inp label="Confirmar Nova Senha" value={pwd.confirm}  onChange={e=>setPw('confirm',e.target.value)}  type="password" placeholder="Repita a senha"/>
            </div>
          )}
        </div>
        {/* Danger zone */}
        <div style={{ background:'rgba(239,68,68,.05)', border:'1px solid rgba(239,68,68,.15)', borderRadius:10, padding:'14px 16px' }}>
          <div style={{ fontSize:13, fontWeight:700, color:'#dc2626', marginBottom:6 }}>Zona de Perigo</div>
          <p style={{ fontSize:12, color:'var(--sa-text3)', margin:'0 0 10px', lineHeight:1.6 }}>Ao sair você precisará fazer login novamente.</p>
          <Btn variant="danger" size="sm" onClick={()=>window.SA_TOAST('Sessão encerrada','error')}>Sair da Conta</Btn>
        </div>
      </div>
    </Modal>
  );
}

Object.assign(window, { NotificationsPanel, CompanyModal, ProfileModal });
