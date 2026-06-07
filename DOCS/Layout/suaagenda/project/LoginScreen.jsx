// ============================================================
// suaAgenda.pro — Login & Register Screen
// ============================================================
const { useState } = React;

function LoginScreen({ onLogin }) {
  const [tab, setTab] = useState('login');
  const [form, setForm] = useState({ email:'', password:'', name:'', remember:false });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const validate = () => {
    const e = {};
    if (tab === 'register' && !form.name.trim()) e.name = 'Nome obrigatório';
    if (!form.email.trim()) e.email = 'E-mail obrigatório';
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) e.email = 'E-mail inválido';
    if (!form.password) e.password = 'Senha obrigatória';
    else if (form.password.length < 6) e.password = 'Mínimo 6 caracteres';
    return e;
  };

  const handleSubmit = () => {
    const e = validate();
    if (Object.keys(e).length) { setErrors(e); return; }
    setErrors({});
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      window.SA_TOAST(tab === 'login' ? 'Login realizado com sucesso!' : 'Conta criada! Bem-vindo(a)!', 'success');
      onLogin();
    }, 1200);
  };

  const FEATURES = [
    { icon: 'calendar',  text: 'Agenda inteligente com notificações automáticas' },
    { icon: 'users',     text: 'Gestão de clientes e histórico completo' },
    { icon: 'dollar',    text: 'Relatórios financeiros em tempo real' },
    { icon: 'sparkle',   text: 'Página de agendamento pública personalizada' },
  ];

  return (
    <div style={{ display:'flex', minHeight:'100vh', background:'var(--sa-bg)', fontFamily:"'Inter',sans-serif" }}>
      {/* Left Hero */}
      <div style={{
        flex:1, background:'linear-gradient(145deg,#111 0%,#1a1a1a 40%,#0f0f0f 100%)',
        display:'flex', flexDirection:'column', justifyContent:'center', padding:'60px 56px',
        position:'relative', overflow:'hidden',
      }}>
        {/* Decorative circle */}
        <div style={{ position:'absolute', top:-80, right:-80, width:320, height:320, borderRadius:'50%', background:'var(--sa-secondary)', opacity:.08 }} />
        <div style={{ position:'absolute', bottom:-60, left:-60, width:240, height:240, borderRadius:'50%', background:'var(--sa-secondary)', opacity:.06 }} />

        {/* Logo */}
        <div style={{ display:'flex', alignItems:'center', gap:12, marginBottom:56 }}>
          <div style={{ width:44, height:44, borderRadius:12, background:'var(--sa-secondary)', display:'flex', alignItems:'center', justifyContent:'center' }}>
            <Icon name="scissors" size={22} style={{ color:'#fff' }} />
          </div>
          <div>
            <div style={{ fontFamily:"'Poppins',sans-serif", fontSize:22, fontWeight:700, color:'#fff' }}>suaAgenda<span style={{ color:'var(--sa-secondary)' }}>.pro</span></div>
          </div>
        </div>

        <h2 style={{ fontFamily:"'Poppins',sans-serif", fontSize:36, fontWeight:700, color:'#fff', lineHeight:1.2, margin:'0 0 16px', maxWidth:420 }}>
          O sistema de agendamento que<br />
          <span style={{ color:'var(--sa-secondary)' }}>transforma seu negócio</span>
        </h2>
        <p style={{ fontSize:16, color:'rgba(255,255,255,.6)', margin:'0 0 40px', lineHeight:1.7, maxWidth:380 }}>
          Gestão completa para barbearias, salões e estúdios de beleza.
        </p>

        <div style={{ display:'flex', flexDirection:'column', gap:16 }}>
          {FEATURES.map((f, i) => (
            <div key={i} style={{ display:'flex', alignItems:'center', gap:14 }}>
              <div style={{ width:36, height:36, borderRadius:9, background:'rgba(255,255,255,.08)', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
                <Icon name={f.icon} size={17} style={{ color:'var(--sa-secondary)' }} />
              </div>
              <span style={{ fontSize:14, color:'rgba(255,255,255,.75)', lineHeight:1.5 }}>{f.text}</span>
            </div>
          ))}
        </div>
      </div>

      {/* Right Form */}
      <div style={{ width:480, display:'flex', alignItems:'center', justifyContent:'center', padding:'40px 48px', background:'var(--sa-surface)' }}>
        <div style={{ width:'100%', maxWidth:380 }}>
          {/* Tabs */}
          <div style={{ display:'flex', gap:0, marginBottom:36, background:'var(--sa-surface2)', borderRadius:10, padding:4 }}>
            {[['login','Entrar'],['register','Criar conta']].map(([id,lbl]) => (
              <button key={id} onClick={() => { setTab(id); setErrors({}); }} style={{
                flex:1, padding:'9px 0', borderRadius:8, border:'none', cursor:'pointer', fontSize:14, fontWeight:600,
                background: tab===id ? 'var(--sa-surface)' : 'transparent',
                color: tab===id ? 'var(--sa-text1)' : 'var(--sa-text3)',
                boxShadow: tab===id ? '0 1px 4px rgba(0,0,0,.1)' : 'none',
                transition:'all 200ms ease', fontFamily:"'Inter',sans-serif",
              }}>{lbl}</button>
            ))}
          </div>

          <div>
            <h2 style={{ fontFamily:"'Poppins',sans-serif", fontSize:24, fontWeight:700, color:'var(--sa-text1)', margin:'0 0 6px' }}>
              {tab==='login' ? 'Bem-vindo de volta' : 'Crie sua conta'}
            </h2>
            <p style={{ fontSize:14, color:'var(--sa-text3)', margin:'0 0 28px' }}>
              {tab==='login' ? 'Acesse sua conta para continuar' : 'Comece gratuitamente, sem cartão'}
            </p>

            <div style={{ display:'flex', flexDirection:'column', gap:16 }}>
              {tab === 'register' && (
                <Inp label="Nome completo" value={form.name} onChange={e=>set('name',e.target.value)}
                  placeholder="Seu nome" required error={errors.name}
                  icon={<Icon name="user" size={15}/>} />
              )}
              <Inp label="E-mail" value={form.email} onChange={e=>set('email',e.target.value)}
                placeholder="seu@email.com" type="email" required error={errors.email}
                icon={<Icon name="user" size={15}/>} />
              <Inp label="Senha" value={form.password} onChange={e=>set('password',e.target.value)}
                placeholder={tab==='login'?'Sua senha':'Mínimo 6 caracteres'} type="password" required error={errors.password}
                icon={<Icon name="eye" size={15}/>} />

              {tab === 'login' && (
                <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between' }}>
                  <label style={{ display:'flex', alignItems:'center', gap:8, cursor:'pointer', fontSize:13, color:'var(--sa-text2)' }}>
                    <input type="checkbox" checked={form.remember} onChange={e=>set('remember',e.target.checked)}
                      style={{ width:14, height:14, accentColor:'var(--sa-primary)', cursor:'pointer' }} />
                    Lembrar-me
                  </label>
                  <span style={{ fontSize:13, color:'var(--sa-secondary)', cursor:'pointer', fontWeight:600 }}>Esqueci a senha</span>
                </div>
              )}

              <Btn onClick={handleSubmit} loading={loading} fullWidth size="lg" style={{ marginTop:4 }}>
                {tab==='login' ? 'Entrar' : 'Criar conta'}
              </Btn>

              <Divider label="ou continue com" style={{ margin:'4px 0' }} />

              <div style={{ display:'flex', gap:10 }}>
                {['Google','Apple'].map(p => (
                  <button key={p} style={{
                    flex:1, padding:'10px 0', border:'1.5px solid var(--sa-border)', borderRadius:9,
                    background:'var(--sa-surface)', cursor:'pointer', fontSize:13, fontWeight:600,
                    color:'var(--sa-text2)', fontFamily:"'Inter',sans-serif", transition:'all 180ms ease',
                  }}>{p}</button>
                ))}
              </div>

              <button onClick={onLogin} style={{
                width:'100%', padding:'10px 0', border:'1.5px dashed var(--sa-border)', borderRadius:9,
                background:'transparent', cursor:'pointer', fontSize:13, fontWeight:500, color:'var(--sa-text3)',
                fontFamily:"'Inter',sans-serif", marginTop:4,
              }}>⚡ Entrar como demo (pular login)</button>

              <p style={{ fontSize:13, color:'var(--sa-text3)', textAlign:'center', margin:'8px 0 0' }}>
                {tab==='login' ? 'Não tem conta? ' : 'Já tem conta? '}
                <span onClick={() => setTab(tab==='login'?'register':'login')}
                  style={{ color:'var(--sa-secondary)', cursor:'pointer', fontWeight:600 }}>
                  {tab==='login' ? 'Cadastre-se grátis' : 'Fazer login'}
                </span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { LoginScreen });
