// ============================================================
// suaAgenda.pro — UI Primitives
// ============================================================
const { useState, useEffect, useRef } = React;

// ── ICONS ────────────────────────────────────────────────────
const IC = {
  dashboard: `<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>`,
  calendar:  `<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>`,
  users:     `<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>`,
  dollar:    `<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>`,
  settings:  `<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>`,
  bell:      `<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>`,
  search:    `<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>`,
  plus:      `<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>`,
  logout:    `<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>`,
  chevL:     `<polyline points="15 18 9 12 15 6"/>`,
  chevR:     `<polyline points="9 18 15 12 9 6"/>`,
  chevD:     `<polyline points="6 9 12 15 18 9"/>`,
  arrowUp:   `<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>`,
  arrowDown: `<line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>`,
  trendUp:   `<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>`,
  trendDown: `<polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>`,
  x:         `<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>`,
  check:     `<polyline points="20 6 9 17 4 12"/>`,
  edit:      `<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>`,
  trash:     `<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>`,
  filter:    `<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>`,
  eye:       `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`,
  globe:     `<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>`,
  sun:       `<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>`,
  moon:      `<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>`,
  sparkle:   `<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/>`,
  map:       `<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>`,
  phone:     `<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.5 2 2 0 0 1 3.59 1.3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6.13 6.13l1.02-.93a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>`,
  star:      `<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>`,
  clock:     `<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>`,
  user:      `<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>`,
  scissors:  `<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>`,
  refresh:   `<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>`,
  link:       `<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>`,
  lock:       `<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>`,
  code:       `<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>`,
  chart:      `<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>`,
};

function Icon({ name, size=20, style={} }) {
  const d = IC[name];
  if (!d) return null;
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none"
      stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
      style={style} dangerouslySetInnerHTML={{__html: d}} />
  );
}

// ── BUTTON ───────────────────────────────────────────────────
function Btn({ children, variant='primary', size='md', onClick, disabled, loading, icon, style={}, fullWidth }) {
  const sizes = { sm:{fontSize:13,padding:'7px 14px',height:32}, md:{fontSize:14,padding:'9px 18px',height:40}, lg:{fontSize:15,padding:'12px 28px',height:48} };
  const variants = {
    primary:   { background:'var(--sa-primary)',    color:'#fff',                  border:'none' },
    secondary: { background:'transparent',          color:'var(--sa-primary)',      border:'1.5px solid var(--sa-primary)' },
    ghost:     { background:'transparent',          color:'var(--sa-text2)',        border:'none' },
    danger:    { background:'#ef4444',              color:'#fff',                  border:'none' },
    accent:    { background:'var(--sa-secondary)',  color:'#fff',                  border:'none' },
    muted:     { background:'var(--sa-surface2)',   color:'var(--sa-text2)',        border:'1px solid var(--sa-border)' },
  };
  const [hover, setHover] = useState(false);
  return (
    <button
      onClick={disabled||loading ? undefined : onClick}
      disabled={disabled||loading}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      style={{
        display:'inline-flex', alignItems:'center', justifyContent:'center', gap:6,
        fontFamily:"'Inter',sans-serif", fontWeight:600, borderRadius:8, cursor: disabled||loading?'not-allowed':'pointer',
        transition:'all 200ms ease', opacity: disabled?0.45:1,
        width: fullWidth?'100%':undefined,
        filter: hover&&!disabled&&!loading ? 'brightness(1.1)' : undefined,
        ...sizes[size], ...variants[variant], ...style,
      }}>
      {loading ? <Spinner size={13} color="currentColor" /> : (icon && <span style={{display:'flex'}}>{icon}</span>)}
      {children}
    </button>
  );
}

// ── INPUT ────────────────────────────────────────────────────
function Inp({ label, value, onChange, placeholder, type='text', error, icon, required, disabled, style={}, autoFocus }) {
  const [focused, setFocused] = useState(false);
  return (
    <div style={{display:'flex',flexDirection:'column',gap:5,...style}}>
      {label && <label style={{fontSize:13,fontWeight:600,color:'var(--sa-text1)',letterSpacing:'.2px'}}>{label}{required&&<span style={{color:'#ef4444',marginLeft:2}}>*</span>}</label>}
      <div style={{position:'relative'}}>
        {icon && <span style={{position:'absolute',left:11,top:'50%',transform:'translateY(-50%)',color:'var(--sa-text3)',display:'flex',pointerEvents:'none'}}>{icon}</span>}
        <input type={type} value={value} onChange={onChange} placeholder={placeholder} disabled={disabled} autoFocus={autoFocus}
          onFocus={()=>setFocused(true)} onBlur={()=>setFocused(false)}
          style={{
            width:'100%', padding: icon?'10px 13px 10px 36px':'10px 13px',
            fontSize:14, fontFamily:"'Inter',sans-serif",
            border:`1.5px solid ${error?'#ef4444':focused?'var(--sa-primary)':'var(--sa-border)'}`,
            borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)',
            outline: focused?`3px solid ${error?'rgba(239,68,68,.12)':'rgba(0,0,0,.06)'}`:undefined,
            transition:'all 180ms ease', boxSizing:'border-box',
          }} />
      </div>
      {error && <span style={{fontSize:12,color:'#ef4444'}}>{error}</span>}
    </div>
  );
}

// ── SELECT ───────────────────────────────────────────────────
function Sel({ label, value, onChange, options, placeholder, error, required, disabled, style={} }) {
  return (
    <div style={{display:'flex',flexDirection:'column',gap:5,...style}}>
      {label && <label style={{fontSize:13,fontWeight:600,color:'var(--sa-text1)'}}>{label}{required&&<span style={{color:'#ef4444',marginLeft:2}}>*</span>}</label>}
      <select value={value} onChange={onChange} disabled={disabled}
        style={{
          width:'100%', padding:'10px 36px 10px 13px', fontSize:14,
          fontFamily:"'Inter',sans-serif", border:`1.5px solid ${error?'#ef4444':'var(--sa-border)'}`,
          borderRadius:8, background:'var(--sa-surface)', color: value?'var(--sa-text1)':'var(--sa-text3)',
          cursor:'pointer', appearance:'none',
          backgroundImage:`url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e")`,
          backgroundRepeat:'no-repeat', backgroundPosition:'right 10px center', backgroundSize:16,
        }}>
        {placeholder && <option value="">{placeholder}</option>}
        {options.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
      </select>
      {error && <span style={{fontSize:12,color:'#ef4444'}}>{error}</span>}
    </div>
  );
}

// ── TEXTAREA ─────────────────────────────────────────────────
function Txta({ label, value, onChange, placeholder, rows=3, error, required, style={} }) {
  return (
    <div style={{display:'flex',flexDirection:'column',gap:5,...style}}>
      {label && <label style={{fontSize:13,fontWeight:600,color:'var(--sa-text1)'}}>{label}{required&&<span style={{color:'#ef4444',marginLeft:2}}>*</span>}</label>}
      <textarea value={value} onChange={onChange} placeholder={placeholder} rows={rows}
        style={{
          width:'100%', padding:'10px 13px', fontSize:14,
          fontFamily:"'Inter',sans-serif", border:'1.5px solid var(--sa-border)',
          borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)',
          resize:'vertical', outline:'none', boxSizing:'border-box',
          transition:'border 180ms ease',
        }} />
      {error && <span style={{fontSize:12,color:'#ef4444'}}>{error}</span>}
    </div>
  );
}

// ── BADGE ────────────────────────────────────────────────────
const BADGE_CFG = {
  confirmed: { bg:'rgba(16,185,129,.12)', color:'#059669', label:'Confirmado' },
  pending:   { bg:'rgba(245,158,11,.12)', color:'#d97706', label:'Pendente' },
  cancelled: { bg:'rgba(239,68,68,.1)',   color:'#dc2626', label:'Cancelado' },
  active:    { bg:'rgba(16,185,129,.12)', color:'#059669', label:'Ativo' },
  inactive:  { bg:'rgba(107,114,128,.12)',color:'#6b7280', label:'Inativo' },
};

function Badge({ status, label }) {
  const cfg = BADGE_CFG[status] || { bg:'rgba(0,0,0,.06)', color:'var(--sa-text2)', label: status };
  return (
    <span style={{
      display:'inline-flex', alignItems:'center', gap:5, padding:'3px 10px',
      borderRadius:20, fontSize:12, fontWeight:600,
      background:cfg.bg, color:cfg.color, whiteSpace:'nowrap',
    }}>
      <span style={{width:5,height:5,borderRadius:'50%',background:'currentColor',flexShrink:0}} />
      {label || cfg.label}
    </span>
  );
}

// ── AVATAR ───────────────────────────────────────────────────
function Avt({ name, initials, size=36, color='var(--sa-primary)' }) {
  const text = initials || (name ? name.split(' ').slice(0,2).map(n=>n[0]).join('').toUpperCase() : '?');
  const isDark = color === '#1a1a1a' || color === '#000000' || color === '#6366f1';
  return (
    <div style={{
      width:size, height:size, borderRadius:'50%', background:color,
      color: isDark ? '#fff' : '#1a1a1a',
      display:'flex', alignItems:'center', justifyContent:'center',
      fontSize:size*.38, fontWeight:700, fontFamily:"'Inter',sans-serif", flexShrink:0,
    }}>{text}</div>
  );
}

// ── SPINNER ──────────────────────────────────────────────────
function Spinner({ size=20, color='var(--sa-primary)' }) {
  return <div style={{ width:size, height:size, border:`2px solid transparent`, borderTopColor:color, borderRadius:'50%', animation:'sa-spin 600ms linear infinite', flexShrink:0 }} />;
}

// ── CARD ─────────────────────────────────────────────────────
function Card({ children, style={}, onClick, p=24 }) {
  const [h, setH] = useState(false);
  return (
    <div onClick={onClick} onMouseEnter={()=>onClick&&setH(true)} onMouseLeave={()=>setH(false)}
      style={{ background:'var(--sa-surface)', borderRadius:12, border:'1px solid var(--sa-border)', padding:p,
        boxShadow: h&&onClick?'0 6px 20px rgba(0,0,0,.1)':'0 1px 3px rgba(0,0,0,.05)',
        transition:'all 200ms ease', cursor:onClick?'pointer':undefined, ...style }}>
      {children}
    </div>
  );
}

// ── MODAL ────────────────────────────────────────────────────
function Modal({ open, onClose, title, subtitle, children, footer, size='md' }) {
  useEffect(() => {
    if (open) document.body.style.overflow = 'hidden';
    else document.body.style.overflow = '';
    return () => { document.body.style.overflow = ''; };
  }, [open]);

  useEffect(() => {
    const fn = e => { if(e.key==='Escape') onClose(); };
    if(open) document.addEventListener('keydown', fn);
    return () => document.removeEventListener('keydown', fn);
  }, [open, onClose]);

  if (!open) return null;
  const mw = { sm:460, md:600, lg:820 }[size];

  return (
    <div onClick={e => e.target===e.currentTarget&&onClose()}
      style={{ position:'fixed', inset:0, background:'rgba(0,0,0,.5)', display:'flex', alignItems:'center', justifyContent:'center', zIndex:1000, padding:20 }}>
      <div style={{ background:'var(--sa-surface)', borderRadius:16, width:'100%', maxWidth:mw, maxHeight:'90vh',
        display:'flex', flexDirection:'column', boxShadow:'0 24px 64px rgba(0,0,0,.2)', animation:'sa-modal-in 250ms ease' }}>
        <div style={{ padding:'24px 28px 0', display:'flex', justifyContent:'space-between', alignItems:'flex-start', flexShrink:0 }}>
          <div>
            <h3 style={{fontFamily:"'Poppins',sans-serif",fontSize:18,fontWeight:600,color:'var(--sa-text1)',margin:0}}>{title}</h3>
            {subtitle && <p style={{fontSize:13,color:'var(--sa-text3)',margin:'4px 0 0'}}>{subtitle}</p>}
          </div>
          <button onClick={onClose} style={{ background:'none', border:'none', cursor:'pointer', color:'var(--sa-text3)', padding:4, display:'flex', alignItems:'center', borderRadius:6 }}>
            <Icon name="x" size={18} />
          </button>
        </div>
        <div style={{ padding:'20px 28px', overflowY:'auto', flex:1 }}>{children}</div>
        {footer && <div style={{ padding:'16px 28px 24px', borderTop:'1px solid var(--sa-border)', display:'flex', gap:10, justifyContent:'flex-end', flexShrink:0 }}>{footer}</div>}
      </div>
    </div>
  );
}

// ── TOAST ────────────────────────────────────────────────────
let _toastList = [];
let _toastSetter = null;
window.SA_TOAST = (msg, type='success') => {
  const id = Date.now() + Math.random();
  _toastList = [..._toastList, {id, msg, type}];
  if (_toastSetter) _toastSetter([..._toastList]);
  setTimeout(() => {
    _toastList = _toastList.filter(t=>t.id!==id);
    if (_toastSetter) _toastSetter([..._toastList]);
  }, 3600);
};

function ToastCont() {
  const [toasts, setToasts] = useState([]);
  useEffect(() => { _toastSetter = setToasts; return () => { _toastSetter = null; }; }, []);
  const colors = { success:'#10b981', error:'#ef4444', warning:'#f59e0b', info:'#3b82f6' };
  return (
    <div style={{position:'fixed',bottom:24,right:24,display:'flex',flexDirection:'column',gap:8,zIndex:9999}}>
      {toasts.map(t => (
        <div key={t.id} style={{
          background:'var(--sa-surface)', borderLeft:`4px solid ${colors[t.type]||colors.success}`,
          border:`1px solid ${colors[t.type]||colors.success}`, borderRadius:10,
          padding:'12px 18px', fontSize:14, fontWeight:500, color:'var(--sa-text1)',
          boxShadow:'0 4px 16px rgba(0,0,0,.12)', animation:'sa-slide-up 300ms ease',
          minWidth:240, maxWidth:380,
        }}>{t.msg}</div>
      ))}
    </div>
  );
}

// ── DIVIDER ──────────────────────────────────────────────────
function Divider({ label, style={} }) {
  return (
    <div style={{display:'flex',alignItems:'center',gap:12,...style}}>
      <div style={{flex:1,height:1,background:'var(--sa-border)'}} />
      {label && <span style={{fontSize:12,color:'var(--sa-text3)',fontWeight:500}}>{label}</span>}
      <div style={{flex:1,height:1,background:'var(--sa-border)'}} />
    </div>
  );
}

// ── SKELETON ─────────────────────────────────────────────────
function Skeleton({ w='100%', h=18, r=6, style={} }) {
  return <div style={{ width:w, height:h, borderRadius:r,
    background:'linear-gradient(90deg,var(--sa-surface2) 25%,var(--sa-border) 50%,var(--sa-surface2) 75%)',
    backgroundSize:'400% 100%', animation:'sa-skeleton 1.4s ease-in-out infinite', flexShrink:0, ...style }} />;
}

function SkeletonCard({ icon=true, style={} }) {
  return (
    <div style={{ background:'color-mix(in srgb,var(--sa-primary) 6%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 12%,transparent)', borderRadius:16, padding:'22px 22px 0', minHeight:148, overflow:'hidden', position:'relative', ...style }}>
      <Skeleton w={70} h={10} r={4} style={{ marginBottom:14 }} />
      <Skeleton w={110} h={32} r={7} style={{ marginBottom:10 }} />
      <Skeleton w={85} h={10} r={4} />
      <div style={{ position:'absolute', bottom:-20, right:-16, opacity:.06 }}>
        <div style={{ width:100, height:100, borderRadius:12, background:'var(--sa-primary)' }} />
      </div>
    </div>
  );
}

// ── USE-LOADING HOOK ──────────────────────────────────────────
function useLoading(ms=750) {
  const [loading, setLoading] = React.useState(true);
  React.useEffect(() => { const t = setTimeout(() => setLoading(false), ms); return () => clearTimeout(t); }, []);
  return loading;
}

// ── USE-COUNT-UP (number animation) ───────────────────────────
function useCountUp(target, duration=1100) {
  const [val, setVal] = React.useState(0);
  React.useEffect(() => {
    if (target === 0) return;
    let start = null;
    const num = typeof target === 'number' ? target : parseFloat(String(target).replace(/[^0-9.]/g,'')) || 0;
    const step = ts => {
      if (!start) start = ts;
      const p = Math.min((ts - start) / duration, 1);
      const ease = 1 - Math.pow(1 - p, 3);
      setVal(Math.round(ease * num));
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }, [target]);
  return val;
}

// ── TINT CARD (primary color bg + bleeding icon + count-up) ───
function TintCard({ label, value, sub, trend, positive, icon, tintColor, prefix='', suffix='' }) {
  const numVal = typeof value === 'number' ? value : parseFloat(String(value).replace(/[^0-9.]/g,'')) || 0;
  const counted = useCountUp(numVal);
  // Rebuild display string with counted value
  const display = typeof value === 'number'
    ? `${prefix}${counted.toLocaleString('pt-BR')}${suffix}`
    : String(value).replace(/[\d]+/, counted.toLocaleString('pt-BR'));
  return (
    <div style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, padding:'22px 22px 0', position:'relative', overflow:'hidden', minHeight:148, display:'flex', flexDirection:'column' }}>
      <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:12, opacity:.75 }}>{label}</div>
      <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:32, fontWeight:800, color:'var(--sa-text1)', lineHeight:1, letterSpacing:'-1px' }}>{display}</div>
      {trend && <div style={{ display:'flex', alignItems:'center', gap:4, marginTop:10, fontSize:12, fontWeight:600, color:positive?'#10b981':'#ef4444' }}><Icon name={positive?'trendUp':'trendDown'} size={12}/>{trend}</div>}
      {sub && <div style={{ fontSize:12, color:'var(--sa-text3)', marginTop:6 }}>{sub}</div>}
      <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.08, pointerEvents:'none' }}>
        <Icon name={icon} size={130} style={{ color:'var(--sa-primary)' }} />
      </div>
    </div>
  );
}

// Export all
Object.assign(window, { IC, Icon, Btn, Inp, Sel, Txta, Badge, Avt, Spinner, Card, Modal, ToastCont, Divider, BADGE_CFG, Skeleton, SkeletonCard, TintCard, useLoading });
