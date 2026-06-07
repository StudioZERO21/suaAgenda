// ============================================================
// suaAgenda.pro — Layout v3: Sidebar + AppShell (Responsive)
// Desktop: full sidebar | Tablet: icons-only | Mobile: bottom nav
// ============================================================
const { useState, useEffect } = React;

// ── DEVICE HOOK ───────────────────────────────────────────────
function useDeviceSize() {
  const [size, setSize] = useState(() => {
    const w = window.innerWidth;
    return w <= 768 ? 'mobile' : w <= 1080 ? 'tablet' : 'desktop';
  });
  useEffect(() => {
    const fn = () => {
      const w = window.innerWidth;
      setSize(w <= 768 ? 'mobile' : w <= 1080 ? 'tablet' : 'desktop');
    };
    window.addEventListener('resize', fn);
    return () => window.removeEventListener('resize', fn);
  }, []);
  return size;
}

// ── NAV ITEMS ─────────────────────────────────────────────────
const NAV = [
  { id:'dashboard',    label:'Dashboard',        icon:'dashboard' },
  { id:'calendar',     label:'Agenda',            icon:'calendar'  },
  { id:'clients',      label:'Clientes',          icon:'users'     },
  { id:'staff',        label:'Funcionários',      icon:'user'      },
  { id:'services',     label:'Serviços',          icon:'scissors'  },
  { id:'products',     label:'Produtos',          icon:'star'      },
  { id:'pos',          label:'PDV',               icon:'dollar'    },
  { id:'financial',    label:'Financeiro',        icon:'chart'     },
  { id:'reports',      label:'Relatórios',        icon:'arrowUp'   },
  { id:'portfolio',    label:'Portfólio',        icon:'eye'       },
  { id:'roles',        label:'Cargos',            icon:'sparkle'   },
  { id:'permissions',  label:'Permissões',       icon:'lock'      },
  { id:'plans',        label:'Planos',            icon:'check'     },
  { id:'site',         label:'Site Público',      icon:'globe'     },
  { id:'settings',     label:'Configurações',     icon:'settings'  },
];

// Mobile bottom nav: 5 priority items
const MOBILE_NAV = [
  { id:'dashboard', label:'Início',    icon:'dashboard' },
  { id:'calendar',  label:'Agenda',    icon:'calendar'  },
  { id:'clients',   label:'Clientes',  icon:'users'     },
  { id:'financial', label:'Financeiro',icon:'dollar'    },
  { id:'settings',  label:'Config.',   icon:'settings'  },
];

// ── DESKTOP SIDEBAR ───────────────────────────────────────────
function Sidebar({ screen, onNavigate, onNewAppt, dark, onToggleDark, onProfileClick, onCompanyClick, collapsed }) {
  const w = collapsed ? 60 : 232;
  return (
    <div style={{ width:w, flexShrink:0, background:'var(--sa-side-bg)', display:'flex', flexDirection:'column', height:'100vh', borderRight:'1px solid rgba(255,255,255,.06)', transition:'width 250ms ease', overflow:'hidden' }}>
      {/* Logo */}
      <div style={{ padding: collapsed?'20px 12px':'24px 20px 20px', borderBottom:'1px solid rgba(255,255,255,.08)', flexShrink:0 }}>
        <div style={{ display:'flex', alignItems:'center', gap:collapsed?0:10 }}>
          <div style={{ width:34, height:34, borderRadius:9, background:'var(--sa-side-accent)', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
            <Icon name="scissors" size={17} style={{ color:'#fff' }}/>
          </div>
          {!collapsed && (
            <div>
              <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:15, fontWeight:700, color:'var(--sa-side-text)', letterSpacing:'-.2px', whiteSpace:'nowrap' }}>suaAgenda</div>
              <div style={{ fontSize:10, color:'var(--sa-side-accent)', fontWeight:600, letterSpacing:'.5px', marginTop:-1 }}>.pro</div>
            </div>
          )}
        </div>
      </div>

      {/* New Appointment CTA */}
      <div style={{ padding: collapsed?'12px 8px 4px':'14px 16px 6px', flexShrink:0 }}>
        <button onClick={onNewAppt} style={{ width:'100%', display:'flex', alignItems:'center', justifyContent:'center', gap:collapsed?0:7, background:'var(--sa-side-accent)', color:'#fff', border:'none', borderRadius:9, padding:'10px 0', cursor:'pointer', fontFamily:"var(--sa-font-body,'Inter',sans-serif)", fontWeight:600, fontSize:13, transition:'all 200ms ease' }}>
          <Icon name="plus" size={15}/>
          {!collapsed && 'Novo Agendamento'}
        </button>
      </div>

      {/* Nav */}
      <nav style={{ flex:1, padding: collapsed?'6px 6px':'8px 10px', display:'flex', flexDirection:'column', gap:2, overflowY:'auto', overflowX:'hidden' }}>
        {NAV.map(item => {
          const active = screen === item.id;
          return (
            <button key={item.id} onClick={()=>onNavigate(item.id)}
              title={collapsed?item.label:undefined}
              style={{ display:'flex', alignItems:'center', gap:collapsed?0:11, padding: collapsed?'10px':'10px 12px', borderRadius:9, border:'none', cursor:'pointer', background: active?'rgba(255,255,255,.1)':'transparent', color: active?'var(--sa-side-accent)':'var(--sa-side-muted)', fontFamily:"var(--sa-font-body,'Inter',sans-serif)", fontWeight: active?600:500, fontSize:14, transition:'all 160ms ease', textAlign:'left', width:'100%', borderLeft: active?'2px solid var(--sa-side-accent)':'2px solid transparent', justifyContent: collapsed?'center':'flex-start' }}>
              <Icon name={item.icon} size={18}/>
              {!collapsed && item.label}
            </button>
          );
        })}
      </nav>

      {/* Company + bottom */}
      {!collapsed && (
        <>
          <div style={{ padding:'0 10px 8px' }}>
            <button onClick={onCompanyClick} style={{ width:'100%', display:'flex', alignItems:'center', gap:10, background:'rgba(255,255,255,.04)', border:'1px solid rgba(255,255,255,.07)', borderRadius:8, padding:'9px 12px', cursor:'pointer', transition:'all 180ms ease' }}>
              <Icon name="globe" size={15} style={{ color:'var(--sa-side-muted)' }}/>
              <span style={{ fontSize:12, color:'var(--sa-side-muted)', fontFamily:"var(--sa-font-body)", fontWeight:500 }}>Configurações da Empresa</span>
            </button>
          </div>
          <div style={{ borderTop:'1px solid rgba(255,255,255,.08)', padding:'14px 16px', flexShrink:0 }}>
            <button onClick={onToggleDark} style={{ width:'100%', display:'flex', alignItems:'center', gap:10, background:'rgba(255,255,255,.06)', border:'none', borderRadius:8, padding:'9px 12px', cursor:'pointer', marginBottom:10, transition:'all 180ms ease' }}>
              <Icon name={dark?'sun':'moon'} size={16} style={{ color:'var(--sa-side-muted)' }}/>
              <span style={{ fontSize:13, color:'var(--sa-side-muted)', fontFamily:"var(--sa-font-body)", fontWeight:500 }}>{dark?'Modo Claro':'Modo Escuro'}</span>
            </button>
            <button onClick={onProfileClick} style={{ width:'100%', display:'flex', alignItems:'center', gap:10, background:'transparent', border:'none', cursor:'pointer', padding:0, borderRadius:8, transition:'background 160ms' }}
              onMouseEnter={e=>e.currentTarget.style.background='rgba(255,255,255,.05)'}
              onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
              <Avt name="Maria Oliveira" size={34} color="var(--sa-side-accent)"/>
              <div style={{ flex:1, minWidth:0, textAlign:'left' }}>
                <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-side-text)', whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis' }}>Maria Oliveira</div>
                <div style={{ fontSize:11, color:'var(--sa-side-muted)' }}>Administradora</div>
              </div>
              <Icon name="edit" size={14} style={{ color:'var(--sa-side-muted)', flexShrink:0 }}/>
            </button>
          </div>
        </>
      )}
      {collapsed && (
        <div style={{ padding:'12px 8px', borderTop:'1px solid rgba(255,255,255,.08)', flexShrink:0, display:'flex', flexDirection:'column', alignItems:'center', gap:8 }}>
          <button onClick={onToggleDark} title={dark?'Modo Claro':'Modo Escuro'} style={{ background:'none', border:'none', cursor:'pointer', color:'var(--sa-side-muted)', padding:8, borderRadius:8, transition:'background 150ms' }}>
            <Icon name={dark?'sun':'moon'} size={18}/>
          </button>
          <Avt name="Maria Oliveira" size={32} color="var(--sa-side-accent)" style={{ cursor:'pointer' }}/>
        </div>
      )}
    </div>
  );
}

// ── MOBILE DRAWER ─────────────────────────────────────────────
function MobileDrawer({ open, onClose, screen, onNavigate, dark, onToggleDark, onProfileClick, onCompanyClick }) {
  if (!open) return null;
  return (
    <>
      <div onClick={onClose} style={{ position:'fixed', inset:0, background:'rgba(0,0,0,.5)', zIndex:300 }}/>
      <div style={{ position:'fixed', left:0, top:0, bottom:0, width:260, background:'var(--sa-side-bg)', zIndex:301, display:'flex', flexDirection:'column', overflow:'hidden' }}>
        <div style={{ padding:'20px 16px 12px', borderBottom:'1px solid rgba(255,255,255,.08)', display:'flex', alignItems:'center', justifyContent:'space-between' }}>
          <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:16, fontWeight:700, color:'var(--sa-side-text)' }}>suaAgenda<span style={{ color:'var(--sa-side-accent)' }}>.pro</span></div>
          <button onClick={onClose} style={{ background:'none', border:'none', cursor:'pointer', color:'var(--sa-side-muted)', padding:4 }}><Icon name="x" size={20}/></button>
        </div>
        <nav style={{ flex:1, padding:'8px 10px', overflowY:'auto', display:'flex', flexDirection:'column', gap:2 }}>
          {NAV.map(item => {
            const active = screen===item.id;
            return (
              <button key={item.id} onClick={()=>{ onNavigate(item.id); onClose(); }}
                style={{ display:'flex', alignItems:'center', gap:11, padding:'11px 12px', borderRadius:9, border:'none', cursor:'pointer', background:active?'rgba(255,255,255,.1)':'transparent', color:active?'var(--sa-side-accent)':'var(--sa-side-muted)', fontFamily:"var(--sa-font-body,'Inter',sans-serif)", fontWeight:active?600:500, fontSize:14, textAlign:'left', width:'100%', borderLeft:active?`2px solid var(--sa-side-accent)`:'2px solid transparent' }}>
                <Icon name={item.icon} size={18}/>{item.label}
              </button>
            );
          })}
        </nav>
        <div style={{ borderTop:'1px solid rgba(255,255,255,.08)', padding:'12px 16px' }}>
          <button onClick={onToggleDark} style={{ width:'100%', display:'flex', alignItems:'center', gap:10, background:'rgba(255,255,255,.06)', border:'none', borderRadius:8, padding:'9px 12px', cursor:'pointer', marginBottom:8 }}>
            <Icon name={dark?'sun':'moon'} size={16} style={{ color:'var(--sa-side-muted)' }}/>
            <span style={{ fontSize:13, color:'var(--sa-side-muted)', fontFamily:"var(--sa-font-body)" }}>{dark?'Modo Claro':'Modo Escuro'}</span>
          </button>
          <button onClick={()=>{ onProfileClick(); onClose(); }} style={{ width:'100%', display:'flex', alignItems:'center', gap:10, background:'transparent', border:'none', cursor:'pointer', padding:'8px 0', borderRadius:8 }}>
            <Avt name="Maria Oliveira" size={34} color="var(--sa-side-accent)"/>
            <div style={{ textAlign:'left' }}>
              <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-side-text)' }}>Maria Oliveira</div>
              <div style={{ fontSize:11, color:'var(--sa-side-muted)' }}>Administradora</div>
            </div>
          </button>
        </div>
      </div>
    </>
  );
}

// ── BOTTOM TAB BAR (mobile) ───────────────────────────────────
function BottomTabBar({ screen, onNavigate, onNewAppt }) {
  return (
    <div style={{ position:'fixed', bottom:0, left:0, right:0, height:64, background:'var(--sa-surface)', borderTop:'1px solid var(--sa-border)', display:'flex', alignItems:'stretch', zIndex:200, paddingBottom:'env(safe-area-inset-bottom,0)' }}>
      {MOBILE_NAV.slice(0,2).map(item => {
        const active = screen===item.id;
        return (
          <button key={item.id} onClick={()=>onNavigate(item.id)} style={{ flex:1, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:3, background:'none', border:'none', cursor:'pointer', color:active?'var(--sa-primary)':'var(--sa-text3)', transition:'color 150ms' }}>
            <Icon name={item.icon} size={22}/>
            <span style={{ fontSize:10, fontWeight:active?700:500 }}>{item.label}</span>
          </button>
        );
      })}
      {/* Center FAB */}
      <div style={{ width:56, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
        <button onClick={onNewAppt} style={{ width:48, height:48, borderRadius:'50%', background:'var(--sa-primary)', border:'none', cursor:'pointer', display:'flex', alignItems:'center', justifyContent:'center', boxShadow:'0 4px 14px rgba(0,0,0,.2)' }}>
          <Icon name="plus" size={22} style={{ color:'#fff' }}/>
        </button>
      </div>
      {MOBILE_NAV.slice(2,4).map(item => {
        const active = screen===item.id;
        return (
          <button key={item.id} onClick={()=>onNavigate(item.id)} style={{ flex:1, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:3, background:'none', border:'none', cursor:'pointer', color:active?'var(--sa-primary)':'var(--sa-text3)', transition:'color 150ms' }}>
            <Icon name={item.icon} size={22}/>
            <span style={{ fontSize:10, fontWeight:active?700:500 }}>{item.label}</span>
          </button>
        );
      })}
      <button onClick={()=>onNavigate('settings')} style={{ flex:1, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:3, background:'none', border:'none', cursor:'pointer', color:screen==='settings'?'var(--sa-primary)':'var(--sa-text3)', transition:'color 150ms' }}>
        <Icon name="settings" size={22}/>
        <span style={{ fontSize:10, fontWeight:screen==='settings'?700:500 }}>Config.</span>
      </button>
    </div>
  );
}

// ── APP HEADER ────────────────────────────────────────────────
function AppHeader({ title, subtitle, actions }) {
  return (
    <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', padding:'24px 32px 0', flexShrink:0 }}>
      <div>
        <h1 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:22, fontWeight:700, color:'var(--sa-text1)', margin:0, lineHeight:1.2 }}>{title}</h1>
        {subtitle && <p style={{ fontSize:14, color:'var(--sa-text3)', margin:'4px 0 0' }}>{subtitle}</p>}
      </div>
      {actions && <div style={{ display:'flex', gap:10, alignItems:'center' }}>{actions}</div>}
    </div>
  );
}

// ── APP SHELL ─────────────────────────────────────────────────
function AppShell({ children, screen, onNavigate, dark, onToggleDark, onNewAppt, onProfileClick, onCompanyClick }) {
  const autoDevice = useDeviceSize();
  const [deviceOverride, setDeviceOverride] = useState(null);
  const [notifOpen, setNotifOpen]   = useState(false);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [sideCollapsed, setSideCollapsed] = useState(false);
  const unreadCount = 3;

  const device = deviceOverride || autoDevice;
  const isMobile = device === 'mobile';
  const isTablet = device === 'tablet';

  return (
    <div style={{ display:'flex', height:'100vh', background:'var(--sa-bg)', overflow:'hidden' }}>
      {/* Sidebar — desktop + tablet */}
      {!isMobile && (
        <Sidebar
          screen={screen} onNavigate={onNavigate} onNewAppt={onNewAppt}
          dark={dark} onToggleDark={onToggleDark}
          onProfileClick={onProfileClick} onCompanyClick={onCompanyClick}
          collapsed={isTablet || sideCollapsed}
        />
      )}

      {/* Mobile drawer */}
      <MobileDrawer open={drawerOpen} onClose={()=>setDrawerOpen(false)}
        screen={screen} onNavigate={onNavigate} dark={dark} onToggleDark={onToggleDark}
        onProfileClick={onProfileClick} onCompanyClick={onCompanyClick}/>

      {/* Main content */}
      <div style={{ flex:1, minWidth:0, overflowY:'auto', display:'flex', flexDirection:'column', position:'relative', paddingBottom: isMobile?64:0 }}>
        {/* Top bar */}
        <div style={{ position:'sticky', top:0, zIndex:200, display:'flex', justifyContent:'space-between', alignItems:'center', padding:'8px 20px', background:'var(--sa-surface)', borderBottom:'1px solid var(--sa-border)', flexShrink:0, gap:8 }}>
          {/* Left: hamburger (mobile) or collapse toggle (desktop) */}
          <div style={{ display:'flex', alignItems:'center', gap:8 }}>
            {isMobile && (
              <button onClick={()=>setDrawerOpen(true)} style={{ background:'none', border:'1px solid var(--sa-border)', borderRadius:8, cursor:'pointer', padding:'6px 9px', display:'flex', alignItems:'center', color:'var(--sa-text2)' }}>
                <Icon name="filter" size={17}/>
              </button>
            )}
            {!isMobile && !isTablet && (
              <button onClick={()=>setSideCollapsed(s=>!s)} title="Colapsar menu" style={{ background:'none', border:'1px solid var(--sa-border)', borderRadius:8, cursor:'pointer', padding:'6px 9px', display:'flex', alignItems:'center', color:'var(--sa-text3)', transition:'all 150ms' }}>
                <Icon name="filter" size={16}/>
              </button>
            )}
            <span style={{ fontSize:12, color:'var(--sa-text3)' }}>{new Date().toLocaleDateString('pt-BR',{weekday:'long',day:'numeric',month:'long'})}</span>
          </div>

          {/* Right: device toggle + notif */}
          <div style={{ display:'flex', gap:6, alignItems:'center' }}>
            {/* Device size preview toggle */}
            <div style={{ display:'flex', background:'var(--sa-surface2)', border:'1px solid var(--sa-border)', borderRadius:8, overflow:'hidden' }}>
              {[['mobile','📱','375'],['tablet','🖥','768'],['desktop','💻','Full']].map(([d,em,lbl])=>(
                <button key={d} onClick={()=>setDeviceOverride(device===d&&deviceOverride?null:d)}
                  title={`Simular ${d} (${lbl}px)`}
                  style={{ padding:'5px 10px', border:'none', background:device===d&&deviceOverride?'var(--sa-primary)':'transparent', color:device===d&&deviceOverride?'#fff':'var(--sa-text3)', cursor:'pointer', fontSize:12, fontWeight:device===d&&deviceOverride?700:400, fontFamily:'var(--sa-font-body)', transition:'all 150ms', borderRight:d!=='desktop'?'1px solid var(--sa-border)':'none' }}>
                  {em} {!isMobile && lbl}
                </button>
              ))}
            </div>

            {/* Notification bell */}
            <div style={{ position:'relative' }}>
              <button onClick={()=>setNotifOpen(o=>!o)} style={{ position:'relative', background:'none', border:'1px solid var(--sa-border)', borderRadius:9, cursor:'pointer', padding:'7px 10px', display:'flex', alignItems:'center', color:'var(--sa-text2)', transition:'all 160ms' }}>
                <Icon name="bell" size={18}/>
                {unreadCount>0&&<span style={{ position:'absolute', top:4, right:4, width:16, height:16, borderRadius:'50%', background:'#ef4444', color:'#fff', fontSize:9, fontWeight:800, display:'flex', alignItems:'center', justifyContent:'center', border:'2px solid var(--sa-surface)' }}>{unreadCount}</span>}
              </button>
              {notifOpen&&<div style={{ position:'absolute', top:'calc(100% + 8px)', right:0, zIndex:500 }}><NotificationsPanel onClose={()=>setNotifOpen(false)}/></div>}
            </div>
          </div>
        </div>

        {/* Responsive content wrapper */}
        <div style={{ flex:1, display:'flex', flexDirection:'column', maxWidth: isMobile?'100%':isTablet?768:undefined, width:'100%', margin:'0 auto', minHeight:0 }}>
          {children}
        </div>
      </div>

      {/* Mobile bottom tab bar */}
      {isMobile && <BottomTabBar screen={screen} onNavigate={onNavigate} onNewAppt={onNewAppt}/>}
    </div>
  );
}

Object.assign(window, { Sidebar, AppHeader, AppShell });
