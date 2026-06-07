// ============================================================
// suaAgenda.pro — Settings Screen v3
// Tabs: Tema, Tipografia, Segurança, Contatos, API, Notificações
// ============================================================
const { useState, useEffect } = React;

// ── MINI PREVIEW ──────────────────────────────────────────────
function MiniPreview({ palette, dark }) {
  const p = SA_PALETTES[palette];
  const c = dark ? p.dark : p.light;
  return (
    <div style={{ borderRadius:12, overflow:'hidden', border:'1px solid var(--sa-border)', boxShadow:'0 4px 16px rgba(0,0,0,.08)', userSelect:'none', pointerEvents:'none' }}>
      <div style={{ background:'#e0e0e0', padding:'6px 10px', display:'flex', gap:5, alignItems:'center' }}>
        {['#ff5f57','#febc2e','#28c840'].map((col,i)=><div key={i} style={{ width:9, height:9, borderRadius:'50%', background:col }}/>)}
      </div>
      <div style={{ display:'flex', height:180 }}>
        <div style={{ width:60, background:dark?c.surface:'#111', padding:'8px 5px', display:'flex', flexDirection:'column', gap:3 }}>
          <div style={{ display:'flex', alignItems:'center', gap:3, marginBottom:6 }}>
            <div style={{ width:14, height:14, borderRadius:3, background:c.secondary }}/>
            <div style={{ width:22, height:5, borderRadius:2, background:'rgba(255,255,255,.3)' }}/>
          </div>
          {[1,0,0,0].map((active,i)=>(
            <div key={i} style={{ display:'flex', alignItems:'center', gap:3, padding:'4px 3px', borderRadius:3, background:active?'rgba(255,255,255,.1)':'transparent', borderLeft:active?`2px solid ${c.secondary}`:'2px solid transparent' }}>
              <div style={{ width:6, height:6, borderRadius:1, background:active?c.secondary:'rgba(255,255,255,.3)' }}/>
              <div style={{ width:18, height:3, borderRadius:1, background:active?'rgba(255,255,255,.7)':'rgba(255,255,255,.3)' }}/>
            </div>
          ))}
        </div>
        <div style={{ flex:1, background:c.bg, padding:10, overflow:'hidden' }}>
          <div style={{ marginBottom:8 }}>
            <div style={{ width:70, height:7, borderRadius:3, background:c.text1, marginBottom:3 }}/>
            <div style={{ width:44, height:4, borderRadius:2, background:c.text3 }}/>
          </div>
          <div style={{ display:'grid', gridTemplateColumns:'repeat(2,1fr)', gap:5, marginBottom:8 }}>
            {[c.secondary,c.primary].map((col,i)=>(
              <div key={i} style={{ background:c.surface, borderRadius:5, padding:'5px 6px', border:`1px solid ${c.border}`, position:'relative', overflow:'hidden' }}>
                <div style={{ width:'60%', height:3, borderRadius:1, background:col, opacity:.5, marginBottom:3 }}/>
                <div style={{ width:'80%', height:8, borderRadius:2, background:c.text1 }}/>
                <div style={{ position:'absolute', bottom:-8, right:-6, width:32, height:32, borderRadius:6, background:col, opacity:.08 }}/>
              </div>
            ))}
          </div>
          <div style={{ background:c.surface, borderRadius:5, padding:'5px 6px', border:`1px solid ${c.border}` }}>
            {[.7,.5,.4].map((op,i)=>(
              <div key={i} style={{ height:3, borderRadius:1, background:c.text2, opacity:op, marginBottom:i<2?3:0, width:`${80-i*15}%` }}/>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

// ── PALETTE CARD ──────────────────────────────────────────────
function PaletteCard({ p, selected, onClick }) {
  const desc = { A:'Barbearia premium', B:'Corporativo & sóbrio', C:'Moderno & tech', D:'Natural & wellness', E:'Beauty & feminino', F:'Areia & terracota', G:'Branco & índigo', H:'Creme & âmbar', I:'Verde Harmony', J:'Preto & laranja', K:'Preto & roxo', L:'Branco & roxo' };
  return (
    <div onClick={onClick} style={{ border:`2px solid ${selected?'var(--sa-primary)':'var(--sa-border)'}`, borderRadius:12, padding:14, cursor:'pointer', background:selected?'color-mix(in srgb,var(--sa-primary) 5%,transparent)':'var(--sa-surface)', transition:'all 200ms', position:'relative', overflow:'hidden' }}>
      {selected && (
        <div style={{ position:'absolute', top:10, right:10, width:18, height:18, borderRadius:'50%', background:'var(--sa-primary)', display:'flex', alignItems:'center', justifyContent:'center' }}>
          <Icon name="check" size={10} style={{ color:'#fff' }}/>
        </div>
      )}
      <div style={{ display:'flex', gap:5, marginBottom:10 }}>
        {p.swatches.map((col,i)=><div key={i} style={{ width:24, height:24, borderRadius:6, background:col, border:'1px solid rgba(0,0,0,.08)' }}/>)}
        <div style={{ flex:1, height:24, borderRadius:6, background:p.light.bg, border:'1px solid rgba(0,0,0,.06)' }}/>
      </div>
      <div style={{ fontSize:12, fontWeight:600, color:'var(--sa-text1)' }}>{p.name}</div>
      <div style={{ fontSize:11, color:'var(--sa-text3)', marginTop:2 }}>{desc[p.id]||''}</div>
    </div>
  );
}

// ── SETTINGS TABS ─────────────────────────────────────────────
const SET_TABS = [
  { id:'tema',           label:'Tema',          icon:'sparkle'  },
  { id:'tipografia',     label:'Tipografia',    icon:'edit'     },
  { id:'seguranca',      label:'Segurança',     icon:'lock'     },
  { id:'contatos',       label:'Contatos',      icon:'phone'    },
  { id:'api',            label:'API & Webhooks',icon:'code'     },
  { id:'notificacoes',   label:'Notificações',  icon:'bell'     },
];

// Toggle helper
function Toggle({ value, onChange }) {
  return (
    <button onClick={()=>onChange(!value)} style={{ width:42, height:24, borderRadius:12, border:'none', cursor:'pointer', background:value?'var(--sa-primary)':'var(--sa-border)', transition:'all 200ms', position:'relative', flexShrink:0, padding:0 }}>
      <div style={{ position:'absolute', top:3, left:value?20:3, width:18, height:18, borderRadius:'50%', background:'#fff', transition:'left 200ms', boxShadow:'0 1px 4px rgba(0,0,0,.2)' }}/>
    </button>
  );
}

function SettingRow({ label, sub, children }) {
  return (
    <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'12px 0', borderBottom:'1px solid var(--sa-border)' }}>
      <div style={{ flex:1, paddingRight:20 }}>
        <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>{label}</div>
        {sub && <div style={{ fontSize:12, color:'var(--sa-text3)', marginTop:2, lineHeight:1.5 }}>{sub}</div>}
      </div>
      {children}
    </div>
  );
}

// ── MAIN SETTINGS SCREEN ──────────────────────────────────────
function SettingsScreen({ palette, dark, onPaletteChange, onDarkChange }) {
  const [tab,          setTab]          = useState('tema');
  const [headingFontId,setHeadingFontId] = useState('poppins');
  const [bodyFontId,   setBodyFontId]   = useState('inter');

  // Security state
  const [sec, setSec] = useState({ twofa:false, sessionTimeout:30, loginsEmail:true, apiAccess:true });
  // Notification state
  const [notif, setNotif] = useState({ newBooking:true, cancelled:true, reminder:true, noShow:false, dailySummary:true, weeklyReport:false, channel:'whatsapp' });
  // API state
  const [apiKey] = useState('sk_live_saagenda_' + 'xxxxxxxxxxxx'.replace(/x/g, ()=>Math.random().toString(36)[2]));
  const [webhooks, setWebhooks] = useState([
    { id:1, url:'https://minha-api.com/webhook/booking',   events:['new_booking'], active:true  },
    { id:2, url:'https://minha-api.com/webhook/cancelled', events:['cancelled'],   active:false },
  ]);
  const [contacts, setContacts] = useState({ support:'(11) 99999-0000', billing:'financeiro@barbearia.com', instagram:'@barbeariastyle', facebook:'BarbeariaStyle', youtube:'' });

  const HEAD_MAP = { poppins:"'Poppins'", montserrat:"'Montserrat'", jakarta:"'Plus Jakarta Sans'", 'dm-serif':"'DM Serif Display'" };
  const BODY_MAP = { inter:"'Inter',-apple-system", 'dm-sans':"'DM Sans'", nunito:"'Nunito'", lato:"'Lato'" };

  const applyFonts = (hId, bId) => {
    document.documentElement.style.setProperty('--sa-font-heading', `${HEAD_MAP[hId]||"'Poppins'"}, sans-serif`);
    document.documentElement.style.setProperty('--sa-font-body',    `${BODY_MAP[bId]||"'Inter'"},-apple-system, sans-serif`);
  };

  const setSN = (k,v) => setNotif(n=>({...n,[k]:v}));

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Configurações" subtitle="Personalize seu sistema suaAgenda.pro"
        actions={<Btn onClick={()=>window.SA_TOAST('Configurações salvas!','success')} icon={<Icon name="check" size={15}/>}>Salvar</Btn>}/>

      <div style={{ padding:'20px 32px 0', display:'flex', gap:24 }}>
        {/* Vertical tab nav */}
        <div style={{ width:200, flexShrink:0 }}>
          <div style={{ display:'flex', flexDirection:'column', gap:2 }}>
            {SET_TABS.map(t=>(
              <button key={t.id} onClick={()=>setTab(t.id)} style={{
                display:'flex', alignItems:'center', gap:10, padding:'10px 12px', borderRadius:9,
                border:'none', cursor:'pointer', textAlign:'left', width:'100%',
                background:tab===t.id?'color-mix(in srgb,var(--sa-primary) 8%,transparent)':'transparent',
                color:tab===t.id?'var(--sa-primary)':'var(--sa-text2)',
                fontWeight:tab===t.id?600:500, fontSize:13, fontFamily:"var(--sa-font-body)",
                borderLeft:tab===t.id?'2px solid var(--sa-primary)':'2px solid transparent',
                transition:'all 150ms',
              }}>
                <Icon name={t.icon} size={16}/>{t.label}
              </button>
            ))}
          </div>
        </div>

        {/* Content */}
        <div style={{ flex:1, minWidth:0 }}>

          {/* ── TEMA ─────────────────────────────────────── */}
          {tab==='tema' && (
            <div style={{ display:'grid', gridTemplateColumns:'1fr 280px', gap:24 }}>
              <div>
                {/* Dark mode toggle */}
                <Card style={{ padding:20, marginBottom:20 }}>
                  <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center' }}>
                    <div>
                      <div style={{ fontSize:15, fontWeight:600, color:'var(--sa-text1)', fontFamily:"var(--sa-font-heading)" }}>Modo Escuro</div>
                      <div style={{ fontSize:13, color:'var(--sa-text3)', marginTop:3 }}>Aplica em todas as telas do sistema</div>
                    </div>
                    <Toggle value={dark} onChange={v=>{ onDarkChange(v); window.SA_TOAST(`Modo ${v?'escuro':'claro'} ativado!`,'success'); }}/>
                  </div>
                </Card>

                {/* Palettes grid */}
                <Card style={{ padding:20 }}>
                  <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Paleta de Cores</h3>
                  <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:10 }}>
                    {Object.values(SA_PALETTES).map(p=>(
                      <PaletteCard key={p.id} p={p} selected={palette===p.id}
                        onClick={()=>{ onPaletteChange(p.id); window.SA_TOAST(`Paleta "${p.name}" aplicada!`,'success'); }}/>
                    ))}
                  </div>
                </Card>
              </div>

              {/* Live preview */}
              <div>
                <Card style={{ padding:20 }}>
                  <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:14, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 14px' }}>Pré-visualização</h3>
                  <MiniPreview palette={palette} dark={dark}/>
                  <div style={{ marginTop:14 }}>
                    {/* Palette colors */}
                    {[
                      { label:'Primária',   col:SA_PALETTES[palette][dark?'dark':'light'].primary },
                      { label:'Secundária', col:SA_PALETTES[palette][dark?'dark':'light'].secondary },
                      { label:'Fundo',      col:SA_PALETTES[palette][dark?'dark':'light'].bg },
                    ].map(item=>(
                      <div key={item.label} style={{ display:'flex', alignItems:'center', justifyContent:'space-between', padding:'7px 0', borderBottom:'1px solid var(--sa-border)' }}>
                        <span style={{ fontSize:12, color:'var(--sa-text2)' }}>{item.label}</span>
                        <div style={{ display:'flex', alignItems:'center', gap:8 }}>
                          <div style={{ width:20, height:20, borderRadius:5, background:item.col, border:'1px solid rgba(0,0,0,.1)' }}/>
                          <span style={{ fontSize:11, fontFamily:'monospace', color:'var(--sa-text3)' }}>{item.col}</span>
                        </div>
                      </div>
                    ))}
                  </div>
                </Card>
              </div>
            </div>
          )}

          {/* ── TIPOGRAFIA ───────────────────────────────── */}
          {tab==='tipografia' && (
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Seleção de Fontes</h3>
                <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:14, marginBottom:20 }}>
                  <Sel label="Fonte para Títulos (H1–H3)" value={headingFontId}
                    onChange={e=>{ setHeadingFontId(e.target.value); applyFonts(e.target.value, bodyFontId); }}
                    options={[{value:'poppins',label:'Poppins — moderno'},{value:'montserrat',label:'Montserrat — forte'},{value:'jakarta',label:'Plus Jakarta Sans — limpo'},{value:'dm-serif',label:'DM Serif Display — elegante'}]}/>
                  <Sel label="Fonte para Corpo & UI" value={bodyFontId}
                    onChange={e=>{ setBodyFontId(e.target.value); applyFonts(headingFontId, e.target.value); }}
                    options={[{value:'inter',label:'Inter — padrão'},{value:'dm-sans',label:'DM Sans — geométrico'},{value:'nunito',label:'Nunito — amigável'},{value:'lato',label:'Lato — clássico'}]}/>
                </div>
                {/* Live preview */}
                <div style={{ padding:18, background:'var(--sa-surface2)', borderRadius:12, border:'1px solid var(--sa-border)', display:'flex', flexDirection:'column', gap:10 }}>
                  <div style={{ fontFamily:'var(--sa-font-heading)', fontSize:26, fontWeight:800, color:'var(--sa-text1)', lineHeight:1.15, letterSpacing:'-.5px' }}>Bem-vindo ao suaAgenda.pro</div>
                  <div style={{ fontFamily:'var(--sa-font-heading)', fontSize:18, fontWeight:600, color:'var(--sa-text1)' }}>Gestão completa de agendamentos</div>
                  <div style={{ height:1, background:'var(--sa-border)' }}/>
                  <div style={{ fontFamily:'var(--sa-font-body)', fontSize:14, color:'var(--sa-text2)', lineHeight:1.7 }}>Texto de corpo — Legível, confortável e profissional para leitura de descrições e informações extensas do sistema.</div>
                  <div style={{ fontFamily:'var(--sa-font-body)', fontSize:12, color:'var(--sa-text3)' }}>Caption · Metadados · 06/06/2026 · João Silva</div>
                  <div style={{ display:'flex', gap:6 }}>
                    {['Primário','Secundário','Ghost'].map((t,i)=>(
                      <span key={t} style={{ fontFamily:'var(--sa-font-body)', fontSize:12, fontWeight:600, padding:'5px 12px', borderRadius:8,
                        background:i===0?'var(--sa-primary)':i===1?'transparent':'var(--sa-surface)',
                        color:i===0?'#fff':i===1?'var(--sa-primary)':'var(--sa-text2)',
                        border:i===1?'1.5px solid var(--sa-primary)':'1px solid var(--sa-border)' }}>{t}</span>
                    ))}
                  </div>
                </div>
              </Card>

              {/* Type scale */}
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 14px' }}>Escala Tipográfica</h3>
                {[
                  { label:'H1', px:48, w:800, lh:'1.1', font:'heading', ls:'-1px'    },
                  { label:'H2', px:32, w:700, lh:'1.2', font:'heading', ls:'-.5px'   },
                  { label:'H3', px:24, w:600, lh:'1.3', font:'heading', ls:'0'       },
                  { label:'H4', px:18, w:600, lh:'1.4', font:'heading', ls:'0'       },
                  { label:'Body', px:16, w:400, lh:'1.6', font:'body',  ls:'0'       },
                  { label:'Small', px:13, w:400, lh:'1.5', font:'body', ls:'.1px'    },
                  { label:'Caption', px:11, w:500, lh:'1.4', font:'body', ls:'.3px'  },
                  { label:'Overline', px:10, w:700, lh:'1.4', font:'body', ls:'1px', upper:true },
                ].map(s=>(
                  <div key={s.label} style={{ display:'flex', alignItems:'center', gap:14, padding:'8px 0', borderBottom:'1px solid var(--sa-border)' }}>
                    <span style={{ fontSize:10, fontWeight:700, color:'var(--sa-secondary)', width:52, flexShrink:0 }}>{s.label}</span>
                    <span style={{ fontSize:11, color:'var(--sa-text3)', width:40, flexShrink:0 }}>{s.px}px</span>
                    <span style={{ fontSize:11, color:'var(--sa-text3)', width:44, flexShrink:0 }}>w{s.w}</span>
                    <span style={{ fontSize:11, color:'var(--sa-text3)', width:32, flexShrink:0 }}>lh{s.lh}</span>
                    <div style={{ flex:1, fontFamily:`var(--sa-font-${s.font})`, fontSize:Math.min(s.px,20), fontWeight:s.w, color:'var(--sa-text1)', letterSpacing:s.ls, textTransform:s.upper?'uppercase':'none' }}>
                      Barbearia Style {s.upper?'PRO':''}
                    </div>
                  </div>
                ))}
              </Card>
            </div>
          )}

          {/* ── SEGURANÇA ─────────────────────────────────── */}
          {tab==='seguranca' && (
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Autenticação</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 16px' }}>Configurações de acesso à conta</p>
                <SettingRow label="Autenticação em dois fatores (2FA)" sub="Adiciona uma camada extra de segurança com SMS ou app autenticador">
                  <Toggle value={sec.twofa} onChange={v=>setSec(s=>({...s,twofa:v}))}/>
                </SettingRow>
                <SettingRow label="Notificar por e-mail em novo login" sub="Receba um aviso sempre que sua conta for acessada">
                  <Toggle value={sec.loginsEmail} onChange={v=>setSec(s=>({...s,loginsEmail:v}))}/>
                </SettingRow>
                <SettingRow label="Tempo de sessão (minutos)" sub="Desconectar automaticamente após inatividade">
                  <select value={sec.sessionTimeout} onChange={e=>setSec(s=>({...s,sessionTimeout:Number(e.target.value)}))}
                    style={{ fontSize:13, padding:'6px 10px', border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)' }}>
                    {[15,30,60,120,480].map(v=><option key={v} value={v}>{v===480?'8 horas':`${v} min`}</option>)}
                  </select>
                </SettingRow>
              </Card>

              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Senha</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 16px' }}>Atualize sua senha periodicamente</p>
                <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
                  <Inp label="Senha atual" type="password" placeholder="••••••••"/>
                  <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
                    <Inp label="Nova senha" type="password" placeholder="Mínimo 8 caracteres"/>
                    <Inp label="Confirmar nova senha" type="password" placeholder="Repita a senha"/>
                  </div>
                  <div style={{ display:'flex', gap:6, alignItems:'center' }}>
                    {['8+ caracteres','Letra maiúscula','Número','Símbolo'].map((r,i)=>(
                      <span key={r} style={{ fontSize:11, padding:'3px 8px', borderRadius:20, background:i<2?'rgba(16,185,129,.1)':'var(--sa-surface2)', color:i<2?'#059669':'var(--sa-text3)', border:'1px solid',borderColor:i<2?'rgba(16,185,129,.2)':'var(--sa-border)' }}>{i<2?'✓ ':''}{r}</span>
                    ))}
                  </div>
                  <div><Btn size="sm" onClick={()=>window.SA_TOAST('Senha alterada!','success')}>Alterar Senha</Btn></div>
                </div>
              </Card>

              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Sessões Ativas</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 14px' }}>Dispositivos com acesso à sua conta</p>
                {[
                  { device:'Chrome — Windows 11', loc:'São Paulo, SP', time:'Agora', current:true },
                  { device:'Safari — iPhone 15',  loc:'São Paulo, SP', time:'2h atrás', current:false },
                ].map((s,i)=>(
                  <div key={i} style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'10px 0', borderBottom:i===0?'1px solid var(--sa-border)':'none' }}>
                    <div style={{ display:'flex', gap:10, alignItems:'center' }}>
                      <div style={{ width:36, height:36, borderRadius:9, background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', display:'flex', alignItems:'center', justifyContent:'center' }}>
                        <Icon name="globe" size={16} style={{ color:'var(--sa-primary)' }}/>
                      </div>
                      <div>
                        <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>{s.device} {s.current&&<span style={{ fontSize:11, color:'#10b981', marginLeft:6 }}>● Atual</span>}</div>
                        <div style={{ fontSize:12, color:'var(--sa-text3)' }}>{s.loc} · {s.time}</div>
                      </div>
                    </div>
                    {!s.current&&<Btn variant="ghost" size="sm" onClick={()=>window.SA_TOAST('Sessão encerrada','success')}>Encerrar</Btn>}
                  </div>
                ))}
              </Card>
            </div>
          )}

          {/* ── CONTATOS ──────────────────────────────────── */}
          {tab==='contatos' && (
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Canais de Contato</h3>
                <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
                  <Inp label="WhatsApp de Atendimento" value={contacts.support} onChange={e=>setContacts(c=>({...c,support:e.target.value}))} icon={<Icon name="phone" size={14}/>} helper="Exibido na página pública"/>
                  <Inp label="E-mail Financeiro / NF" value={contacts.billing} onChange={e=>setContacts(c=>({...c,billing:e.target.value}))} type="email" icon={<Icon name="user" size={14}/>}/>
                </div>
              </Card>

              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Redes Sociais</h3>
                <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
                  {[
                    { key:'instagram', label:'Instagram',  placeholder:'@usuario', icon:'star'  },
                    { key:'facebook',  label:'Facebook',   placeholder:'Nome da página', icon:'globe' },
                    { key:'youtube',   label:'YouTube',    placeholder:'@canal', icon:'globe' },
                  ].map(f=>(
                    <Inp key={f.key} label={f.label} value={contacts[f.key]} onChange={e=>setContacts(c=>({...c,[f.key]:e.target.value}))} placeholder={f.placeholder} icon={<Icon name={f.icon} size={14}/>}/>
                  ))}
                </div>
              </Card>

              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 10px' }}>Suporte suaAgenda.pro</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 14px', lineHeight:1.6 }}>Precisa de ajuda? Nossa equipe está disponível de segunda a sexta, das 8h às 20h.</p>
                <div style={{ display:'flex', gap:8 }}>
                  <Btn variant="secondary" size="sm" icon={<Icon name="phone" size={14}/>} onClick={()=>window.SA_TOAST('Abrindo WhatsApp…','info')}>WhatsApp</Btn>
                  <Btn variant="muted" size="sm" onClick={()=>window.SA_TOAST('Abrindo chat…','info')}>Chat Online</Btn>
                  <Btn variant="ghost" size="sm" onClick={()=>window.SA_TOAST('Abrindo Central de Ajuda…','info')}>Central de Ajuda</Btn>
                </div>
              </Card>
            </div>
          )}

          {/* ── API ───────────────────────────────────────── */}
          {tab==='api' && (
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              <Card style={{ padding:22 }}>
                <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:16 }}>
                  <div>
                    <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Chave de API</h3>
                    <p style={{ fontSize:13, color:'var(--sa-text3)', margin:0 }}>Use para integrar suaAgenda.pro com sistemas externos</p>
                  </div>
                  <SettingRow label="" sub="">
                    <Toggle value={sec.apiAccess} onChange={v=>setSec(s=>({...s,apiAccess:v}))}/>
                  </SettingRow>
                </div>
                {sec.apiAccess ? (
                  <>
                    <div style={{ display:'flex', gap:8, alignItems:'center', padding:'10px 14px', background:'var(--sa-surface2)', border:'1px solid var(--sa-border)', borderRadius:9, marginBottom:10 }}>
                      <span style={{ flex:1, fontFamily:'monospace', fontSize:12, color:'var(--sa-text2)', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' }}>{apiKey}</span>
                      <Btn variant="muted" size="sm" onClick={()=>window.SA_TOAST('Chave copiada!','success')} icon={<Icon name="link" size={12}/>}>Copiar</Btn>
                    </div>
                    <div style={{ display:'flex', gap:8 }}>
                      <Btn variant="secondary" size="sm" onClick={()=>window.SA_TOAST('Nova chave gerada!','success')}>Regenerar chave</Btn>
                      <Btn variant="ghost" size="sm" onClick={()=>window.SA_TOAST('Abrindo documentação…','info')}>Documentação API →</Btn>
                    </div>
                  </>
                ) : (
                  <div style={{ padding:'20px', textAlign:'center', color:'var(--sa-text3)', fontSize:13 }}>API desativada. Ative o toggle acima para gerar sua chave.</div>
                )}
              </Card>

              <Card style={{ padding:22 }}>
                <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:16 }}>
                  <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:0 }}>Webhooks</h3>
                  <Btn size="sm" variant="muted" onClick={()=>window.SA_TOAST('Em breve!','info')} icon={<Icon name="plus" size={13}/>}>Adicionar</Btn>
                </div>
                {webhooks.map(wh=>(
                  <div key={wh.id} style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'12px 0', borderBottom:'1px solid var(--sa-border)' }}>
                    <div style={{ flex:1, minWidth:0 }}>
                      <div style={{ fontFamily:'monospace', fontSize:12, color:'var(--sa-text1)', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap', marginBottom:4 }}>{wh.url}</div>
                      <div style={{ display:'flex', gap:6 }}>
                        {wh.events.map(e=><span key={e} style={{ fontSize:11, fontWeight:600, padding:'2px 7px', borderRadius:20, background:'var(--sa-surface2)', color:'var(--sa-text2)', border:'1px solid var(--sa-border)' }}>{e}</span>)}
                      </div>
                    </div>
                    <div style={{ display:'flex', gap:8, alignItems:'center', marginLeft:14 }}>
                      <Toggle value={wh.active} onChange={v=>setWebhooks(w=>w.map(x=>x.id===wh.id?{...x,active:v}:x))}/>
                      <Btn variant="ghost" size="sm" onClick={()=>window.SA_TOAST('Webhook removido','error')}><Icon name="trash" size={14}/></Btn>
                    </div>
                  </div>
                ))}
                {webhooks.length===0&&<p style={{ fontSize:13, color:'var(--sa-text3)', textAlign:'center', padding:'20px 0' }}>Nenhum webhook configurado</p>}
              </Card>

              {/* Available events */}
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 12px' }}>Eventos Disponíveis</h3>
                <div style={{ display:'grid', gridTemplateColumns:'repeat(2,1fr)', gap:8 }}>
                  {[
                    { ev:'new_booking',    desc:'Novo agendamento criado'       },
                    { ev:'cancelled',      desc:'Agendamento cancelado'          },
                    { ev:'confirmed',      desc:'Agendamento confirmado'         },
                    { ev:'no_show',        desc:'Cliente não compareceu'         },
                    { ev:'new_client',     desc:'Novo cliente cadastrado'        },
                    { ev:'payment',        desc:'Pagamento processado'           },
                  ].map(e=>(
                    <div key={e.ev} style={{ padding:'8px 12px', borderRadius:8, background:'var(--sa-surface2)', border:'1px solid var(--sa-border)' }}>
                      <div style={{ fontFamily:'monospace', fontSize:11, color:'var(--sa-secondary)', marginBottom:2 }}>{e.ev}</div>
                      <div style={{ fontSize:12, color:'var(--sa-text3)' }}>{e.desc}</div>
                    </div>
                  ))}
                </div>
              </Card>
            </div>
          )}

          {/* ── NOTIFICAÇÕES ──────────────────────────────── */}
          {tab==='notificacoes' && (
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Canal Principal</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 14px' }}>Como você quer receber notificações do sistema</p>
                <div style={{ display:'flex', gap:8 }}>
                  {[['whatsapp','WhatsApp','#25D366'],['sms','SMS','#6366f1'],['email','E-mail','var(--sa-secondary)']].map(([v,l,col])=>(
                    <button key={v} onClick={()=>setSN('channel',v)}
                      style={{ flex:1, padding:'10px', borderRadius:10, border:`2px solid ${notif.channel===v?col:'var(--sa-border)'}`, background:notif.channel===v?`${col}12`:'var(--sa-surface)', cursor:'pointer', fontFamily:"var(--sa-font-body)", fontWeight:notif.channel===v?700:500, fontSize:13, color:notif.channel===v?col:'var(--sa-text2)', transition:'all 180ms' }}>
                      {l}
                    </button>
                  ))}
                </div>
              </Card>

              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Notificações de Agendamento</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 4px' }}>Atividade de clientes e agenda</p>
                {[
                  { key:'newBooking',   label:'Novo agendamento',    sub:'Aviso quando um cliente cria um agendamento' },
                  { key:'cancelled',    label:'Cancelamentos',       sub:'Aviso quando um agendamento é cancelado' },
                  { key:'reminder',     label:'Lembretes antes',     sub:'Receber lembrete antes do próximo atendimento' },
                  { key:'noShow',       label:'No-show detectado',   sub:'Cliente não compareceu ao horário marcado' },
                ].map(opt=>(
                  <SettingRow key={opt.key} label={opt.label} sub={opt.sub}>
                    <Toggle value={notif[opt.key]} onChange={v=>setSN(opt.key,v)}/>
                  </SettingRow>
                ))}
              </Card>

              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Relatórios Automáticos</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 4px' }}>Resumos periódicos do seu negócio</p>
                {[
                  { key:'dailySummary',  label:'Resumo diário',   sub:'Receber ao final de cada dia de trabalho' },
                  { key:'weeklyReport',  label:'Relatório semanal',sub:'Visão geral toda segunda-feira de manhã' },
                ].map(opt=>(
                  <SettingRow key={opt.key} label={opt.label} sub={opt.sub}>
                    <Toggle value={notif[opt.key]} onChange={v=>setSN(opt.key,v)}/>
                  </SettingRow>
                ))}
              </Card>

              {/* Test notification */}
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 8px' }}>Testar Notificações</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 14px', lineHeight:1.6 }}>Envie uma notificação de teste para confirmar que tudo está configurado corretamente.</p>
                <Btn size="sm" onClick={()=>window.SA_TOAST(`Notificação de teste enviada via ${notif.channel.toUpperCase()}!`,'success')} icon={<Icon name="bell" size={14}/>}>
                  Enviar notificação de teste
                </Btn>
              </Card>
            </div>
          )}

        </div>
      </div>
    </div>
  );
}

Object.assign(window, { SettingsScreen });
