// ============================================================
// suaAgenda.pro — Recover Account Screen (3 steps)
// ============================================================
const { useState } = React;

function RecoverScreen({ onBack }) {
  const [step, setStep] = useState(1); // 1=email, 2=code, 3=new password
  const [email, setEmail] = useState('');
  const [code, setCode] = useState(['','','','','','']);
  const [pwd, setPwd] = useState({ new_:'', confirm:'' });
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  const submitEmail = () => {
    if (!email.includes('@')) return window.SA_TOAST('E-mail inválido','error');
    setLoading(true);
    setTimeout(() => { setLoading(false); setStep(2); window.SA_TOAST('Código enviado para seu e-mail!','success'); }, 900);
  };

  const submitCode = () => {
    const fullCode = code.join('');
    if (fullCode.length < 6) return window.SA_TOAST('Digite o código completo','error');
    setLoading(true);
    setTimeout(() => { setLoading(false); setStep(3); }, 700);
  };

  const submitPwd = () => {
    if (pwd.new_.length < 6) return window.SA_TOAST('Senha muito curta (mínimo 6 caracteres)','error');
    if (pwd.new_ !== pwd.confirm) return window.SA_TOAST('Senhas não coincidem','error');
    setLoading(true);
    setTimeout(() => { setLoading(false); setDone(true); }, 800);
  };

  const handleCode = (i, v) => {
    const newCode = [...code];
    newCode[i] = v.slice(-1);
    setCode(newCode);
    if (v && i < 5) document.getElementById(`code-${i+1}`)?.focus();
  };

  const strength = pwd.new_.length >= 8 && /[A-Z]/.test(pwd.new_) && /[0-9]/.test(pwd.new_) ? 'forte' :
                   pwd.new_.length >= 6 ? 'média' : pwd.new_.length > 0 ? 'fraca' : '';
  const strengthColor = { forte:'#10b981', média:'#f59e0b', fraca:'#ef4444' }[strength] || 'transparent';
  const strengthPct   = { forte:100, média:60, fraca:30 }[strength] || 0;

  return (
    <div style={{ minHeight:'100vh', background:'var(--sa-bg)', display:'flex', alignItems:'center', justifyContent:'center', padding:24 }}>
      <div style={{ width:'100%', maxWidth:440 }}>
        {/* Logo */}
        <div style={{ textAlign:'center', marginBottom:32 }}>
          <div style={{ width:52, height:52, borderRadius:14, background:'var(--sa-primary)', display:'flex', alignItems:'center', justifyContent:'center', margin:'0 auto 14px' }}>
            <Icon name="lock" size={22} style={{ color:'var(--sa-secondary)' }}/>
          </div>
          <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:22, fontWeight:800, color:'var(--sa-text1)' }}>suaAgenda.pro</div>
        </div>

        {/* Step indicator */}
        <div style={{ display:'flex', alignItems:'center', justifyContent:'center', gap:8, marginBottom:28 }}>
          {[1,2,3].map((s,i)=>(
            <React.Fragment key={s}>
              <div style={{ width:28, height:28, borderRadius:'50%', display:'flex', alignItems:'center', justifyContent:'center', fontSize:12, fontWeight:700,
                background: step>s?'var(--sa-secondary)':step===s?'var(--sa-primary)':'var(--sa-surface2)',
                color: step>=s?'#fff':'var(--sa-text3)',
                border: `2px solid ${step>s?'var(--sa-secondary)':step===s?'var(--sa-primary)':'var(--sa-border)'}`,
                transition:'all 300ms',
              }}>{step>s?'✓':s}</div>
              {i<2&&<div style={{ width:40, height:2, borderRadius:1, background:step>s+1?'var(--sa-secondary)':step===s+1?'var(--sa-primary)':'var(--sa-border)', transition:'background 300ms' }}/>}
            </React.Fragment>
          ))}
        </div>

        {/* Card */}
        {!done ? (
          <div style={{ background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:18, padding:32, boxShadow:'0 8px 32px rgba(0,0,0,.08)' }}>

            {/* STEP 1: Email */}
            {step===1&&(
              <>
                <h2 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:20, fontWeight:700, color:'var(--sa-text1)', margin:'0 0 6px' }}>Recuperar conta</h2>
                <p style={{ fontSize:14, color:'var(--sa-text3)', margin:'0 0 24px', lineHeight:1.6 }}>Digite o e-mail cadastrado. Enviaremos um código de verificação.</p>
                <Inp label="E-mail" value={email} onChange={e=>setEmail(e.target.value)} type="email" placeholder="seu@email.com" icon={<Icon name="user" size={14}/>} required/>
                <Btn size="lg" fullWidth loading={loading} onClick={submitEmail} style={{ marginTop:20 }}>Enviar código</Btn>
                <button onClick={onBack} style={{ display:'block', textAlign:'center', width:'100%', marginTop:14, fontSize:13, color:'var(--sa-text3)', background:'none', border:'none', cursor:'pointer', fontFamily:'var(--sa-font-body)' }}>
                  ← Voltar ao login
                </button>
              </>
            )}

            {/* STEP 2: Code */}
            {step===2&&(
              <>
                <h2 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:20, fontWeight:700, color:'var(--sa-text1)', margin:'0 0 6px' }}>Verifique seu e-mail</h2>
                <p style={{ fontSize:14, color:'var(--sa-text3)', margin:'0 0 8px', lineHeight:1.6 }}>Enviamos um código de 6 dígitos para:</p>
                <div style={{ fontSize:14, fontWeight:700, color:'var(--sa-secondary)', marginBottom:24 }}>{email}</div>

                {/* Code inputs */}
                <div style={{ display:'flex', gap:10, justifyContent:'center', marginBottom:24 }}>
                  {code.map((c,i)=>(
                    <input key={i} id={`code-${i}`} value={c} onChange={e=>handleCode(i,e.target.value)}
                      maxLength={1} type="text" pattern="[0-9]*" inputMode="numeric"
                      style={{ width:44, height:52, textAlign:'center', fontSize:22, fontWeight:800, border:`2px solid ${c?'var(--sa-primary)':'var(--sa-border)'}`, borderRadius:10, background:'var(--sa-surface2)', color:'var(--sa-text1)', fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", outline:'none', transition:'border 150ms' }}/>
                  ))}
                </div>

                <Btn size="lg" fullWidth loading={loading} onClick={submitCode} disabled={code.join('').length<6}>Verificar código</Btn>
                <div style={{ textAlign:'center', marginTop:16, fontSize:13, color:'var(--sa-text3)' }}>
                  Não recebeu?{' '}
                  <button onClick={()=>window.SA_TOAST('Código reenviado!','success')} style={{ background:'none', border:'none', cursor:'pointer', color:'var(--sa-secondary)', fontWeight:600, fontSize:13, fontFamily:'var(--sa-font-body)' }}>Reenviar</button>
                </div>
              </>
            )}

            {/* STEP 3: New password */}
            {step===3&&(
              <>
                <h2 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:20, fontWeight:700, color:'var(--sa-text1)', margin:'0 0 6px' }}>Nova senha</h2>
                <p style={{ fontSize:14, color:'var(--sa-text3)', margin:'0 0 24px', lineHeight:1.6 }}>Crie uma senha forte para proteger sua conta.</p>
                <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
                  <Inp label="Nova senha" value={pwd.new_} onChange={e=>setPwd(p=>({...p,new_:e.target.value}))} type="password" placeholder="Mínimo 6 caracteres" required/>
                  {/* Strength */}
                  {pwd.new_.length>0&&(
                    <div style={{ marginTop:-8 }}>
                      <div style={{ height:4, borderRadius:2, background:'var(--sa-surface2)', overflow:'hidden' }}>
                        <div style={{ height:'100%', width:`${strengthPct}%`, background:strengthColor, borderRadius:2, transition:'all 300ms' }}/>
                      </div>
                      <div style={{ fontSize:11, color:strengthColor, marginTop:4, fontWeight:600 }}>Senha {strength}</div>
                    </div>
                  )}
                  <Inp label="Confirmar nova senha" value={pwd.confirm} onChange={e=>setPwd(p=>({...p,confirm:e.target.value}))} type="password" placeholder="Repita a senha" required/>
                  {pwd.confirm&&pwd.new_!==pwd.confirm&&<div style={{ fontSize:12, color:'#ef4444', marginTop:-8 }}>Senhas não coincidem</div>}
                </div>
                <Btn size="lg" fullWidth loading={loading} onClick={submitPwd} style={{ marginTop:20 }} disabled={pwd.new_!==pwd.confirm||pwd.new_.length<6}>
                  Redefinir senha
                </Btn>
              </>
            )}
          </div>
        ) : (
          /* SUCCESS */
          <div style={{ background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:18, padding:40, textAlign:'center', boxShadow:'0 8px 32px rgba(0,0,0,.08)' }}>
            <div style={{ width:64, height:64, borderRadius:'50%', background:'rgba(16,185,129,.12)', display:'flex', alignItems:'center', justifyContent:'center', margin:'0 auto 20px' }}>
              <Icon name="check" size={28} style={{ color:'#10b981' }}/>
            </div>
            <h2 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:20, fontWeight:700, color:'var(--sa-text1)', margin:'0 0 10px' }}>Senha redefinida!</h2>
            <p style={{ fontSize:14, color:'var(--sa-text3)', margin:'0 0 28px', lineHeight:1.7 }}>Sua senha foi atualizada com sucesso. Agora você pode fazer login com a nova senha.</p>
            <Btn size="lg" fullWidth onClick={onBack} icon={<Icon name="chevR" size={15}/>}>Ir para o login</Btn>
          </div>
        )}
      </div>
    </div>
  );
}

Object.assign(window, { RecoverScreen });
