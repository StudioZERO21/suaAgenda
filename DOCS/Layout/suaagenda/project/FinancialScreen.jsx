// ============================================================
// suaAgenda.pro — Financial Screen v2 (filters + count-up cards)
// ============================================================
const { useState, useMemo, useEffect } = React;

const FIN_METHODS = [
  { label:'Pix',            pct:42, color:'#10b981' },
  { label:'Cartão Crédito', pct:28, color:'#6366f1' },
  { label:'Cartão Débito',  pct:20, color:'#f59e0b' },
  { label:'Dinheiro',       pct:10, color:'#ef4444' },
];

const REV_DATA = [380,520,310,690,450,540,80,620,580,720,490,810,640,90,550,430,760,620,700,530,70,680,590,840,760,920,710,95,730,650];

const ALL_TX = [
  { id:1,  date:'2026-06-06', client:'Miguel Santos',  service:'Corte + Barba',  prof:'João Silva',    method:'Pix',            amount:75,  type:'receita', status:'paid'    },
  { id:2,  date:'2026-06-06', client:'Bruno Lima',     service:'Corte',          prof:'Carlos Mendes', method:'Cartão Débito',  amount:45,  type:'receita', status:'paid'    },
  { id:3,  date:'2026-06-06', client:'Pedro Oliveira', service:'Coloração',      prof:'Ana Costa',     method:'Pix',            amount:180, type:'receita', status:'paid'    },
  { id:4,  date:'2026-06-06', client:'Lucas Ferreira', service:'Barba',          prof:'João Silva',    method:'Dinheiro',       amount:35,  type:'receita', status:'pending' },
  { id:5,  date:'2026-06-05', client:'Rodrigo Alves',  service:'Corte + Barba',  prof:'Carlos Mendes', method:'Pix',            amount:75,  type:'receita', status:'paid'    },
  { id:6,  date:'2026-06-05', client:'Felipe Rocha',   service:'Hidratação',     prof:'Ana Costa',     method:'Cartão Crédito', amount:90,  type:'receita', status:'paid'    },
  { id:7,  date:'2026-06-05', client:'Aluguel espaço', service:'Despesa fixa',   prof:'João Silva',    method:'Pix',            amount:800, type:'despesa', status:'paid'    },
  { id:8,  date:'2026-06-04', client:'Gabriel Souza',  service:'Corte',          prof:'João Silva',    method:'Pix',            amount:45,  type:'receita', status:'paid'    },
  { id:9,  date:'2026-06-04', client:'Eduardo Pinto',  service:'Barba + Bigode', prof:'Carlos Mendes', method:'Cartão Débito',  amount:50,  type:'receita', status:'paid'    },
  { id:10, date:'2026-06-03', client:'Henrique Nunes', service:'Coloração',      prof:'Ana Costa',     method:'Pix',            amount:180, type:'receita', status:'paid'    },
  { id:11, date:'2026-06-03', client:'Thiago Cardoso', service:'Corte',          prof:'João Silva',    method:'Dinheiro',       amount:45,  type:'receita', status:'refunded'},
  { id:12, date:'2026-06-02', client:'Produtos',       service:'Insumos/Produtos',prof:'Carlos Mendes', method:'Pix',           amount:320, type:'despesa', status:'paid'    },
];

// ── REVENUE LINE CHART ────────────────────────────────────────
function RevenueChart({ data }) {
  const [animated, setAnimated] = useState(false);
  useEffect(() => { const t = setTimeout(() => setAnimated(true), 80); return () => clearTimeout(t); }, []);
  const W=560, H=130, max=Math.max(...data), rng=max;
  const pts = data.map((v,i) => ({ x:(i/(data.length-1))*W, y:H-((v/rng)*(H-16))-8 }));
  const line = pts.map((p,i) => `${i?'L':'M'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ');
  const area = `${line}L${W},${H}L0,${H}Z`;
  return (
    <div style={{ width:'100%' }}>
      <svg width="100%" viewBox={`0 0 ${W} ${H}`} preserveAspectRatio="none" style={{ display:'block', overflow:'visible' }}>
        <defs>
          <linearGradient id="fg2" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor="var(--sa-secondary)" stopOpacity=".2"/>
            <stop offset="100%" stopColor="var(--sa-secondary)" stopOpacity=".01"/>
          </linearGradient>
          <clipPath id="fc2"><rect x="0" y="0" width={animated?W:0} height={H} style={{transition:'width 1.2s cubic-bezier(.4,0,.2,1)'}}/></clipPath>
        </defs>
        {[.25,.5,.75,1].map(f=><line key={f} x1={0} y1={H-f*(H-16)-8} x2={W} y2={H-f*(H-16)-8} stroke="var(--sa-border)" strokeWidth={1} strokeDasharray="4 4" opacity={.5}/>)}
        <path d={area} fill="url(#fg2)" clipPath="url(#fc2)"/>
        <path d={line} fill="none" stroke="var(--sa-secondary)" strokeWidth="2.5" strokeLinecap="round" clipPath="url(#fc2)"/>
        {animated && <circle cx={pts[pts.length-1].x} cy={pts[pts.length-1].y} r={4.5} fill="var(--sa-secondary)" stroke="var(--sa-surface)" strokeWidth={2.5}/>}
      </svg>
      <div style={{ display:'flex', justifyContent:'space-between', marginTop:6 }}>
        {['1 Jun','8 Jun','15 Jun','22 Jun','30 Jun'].map(l=><span key={l} style={{ fontSize:10, color:'var(--sa-text3)', fontWeight:500 }}>{l}</span>)}
      </div>
    </div>
  );
}

// ── PAYMENT METHODS ───────────────────────────────────────────
function PaymentMethods({ data, animated }) {
  return (
    <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
      {data.map((m,i) => (
        <div key={m.label}>
          <div style={{ display:'flex', justifyContent:'space-between', marginBottom:5 }}>
            <div style={{ display:'flex', alignItems:'center', gap:8 }}>
              <div style={{ width:8, height:8, borderRadius:'50%', background:m.color }}/>
              <span style={{ fontSize:13, color:'var(--sa-text2)', fontWeight:500 }}>{m.label}</span>
            </div>
            <span style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)' }}>{m.pct}%</span>
          </div>
          <div style={{ height:5, borderRadius:3, background:'var(--sa-surface2)', overflow:'hidden' }}>
            <div style={{ height:'100%', borderRadius:3, background:m.color, width:animated?`${m.pct}%`:'0%', transition:`width 800ms cubic-bezier(.4,0,.2,1) ${i*100}ms`}}/>
          </div>
        </div>
      ))}
    </div>
  );
}

// ── PROF COMMISSION ───────────────────────────────────────────
function ProfCommission({ prof, total, pct, animated }) {
  const earned = Math.round(total*pct);
  return (
    <div style={{ display:'flex', alignItems:'center', gap:14 }}>
      <Avt name={prof.name} size={36} color={prof.color}/>
      <div style={{ flex:1, minWidth:0 }}>
        <div style={{ display:'flex', justifyContent:'space-between', marginBottom:5 }}>
          <span style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>{prof.name}</span>
          <span style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)' }}>{SA_FMT.currency(earned)}</span>
        </div>
        <div style={{ height:5, borderRadius:3, background:'var(--sa-surface2)', overflow:'hidden' }}>
          <div style={{ height:'100%', borderRadius:3, background:prof.color, width:animated?`${pct*100}%`:'0%', transition:'width 900ms cubic-bezier(.4,0,.2,1)'}}/>
        </div>
        <div style={{ fontSize:11, color:'var(--sa-text3)', marginTop:3 }}>{Math.round(pct*100)}% da receita</div>
      </div>
    </div>
  );
}

// ── FILTER BAR ─────────────────────────────────────────────────
function FinFilterBar({ filters, onChange }) {
  const chips = [
    { id:'type',   label:'Tipo',    opts:[{v:'all',l:'Todos'},{v:'receita',l:'Receita'},{v:'despesa',l:'Despesa'}] },
    { id:'status', label:'Status',  opts:[{v:'all',l:'Todos'},{v:'paid',l:'Pago'},{v:'pending',l:'Pendente'},{v:'refunded',l:'Reembolsado'}] },
    { id:'prof',   label:'Profissional', opts:[{v:'all',l:'Todos'},...SA_PROFESSIONALS.map(p=>({v:p.name.split(' ')[0],l:p.name.split(' ')[0]}))] },
    { id:'method', label:'Método',  opts:[{v:'all',l:'Todos'},{v:'Pix',l:'Pix'},{v:'Cartão Crédito',l:'Crédito'},{v:'Cartão Débito',l:'Débito'},{v:'Dinheiro',l:'Dinheiro'}] },
  ];
  const active = Object.values(filters).some(v=>v!=='all');
  return (
    <div style={{ display:'flex', gap:10, alignItems:'center', padding:'10px 14px', background:'var(--sa-surface)', borderRadius:10, border:'1px solid var(--sa-border)', marginBottom:20, flexWrap:'wrap' }}>
      <Icon name="filter" size={14} style={{ color:'var(--sa-text3)', flexShrink:0 }}/>
      {chips.map(c => (
        <div key={c.id} style={{ display:'flex', alignItems:'center', gap:5 }}>
          <span style={{ fontSize:11, fontWeight:600, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.4px' }}>{c.label}</span>
          <select value={filters[c.id]} onChange={e=>onChange(c.id,e.target.value)}
            style={{ fontSize:12, padding:'4px 9px', border:'1px solid var(--sa-border)', borderRadius:7, fontFamily:'var(--sa-font-body)', cursor:'pointer',
              background: filters[c.id]!=='all'?'color-mix(in srgb,var(--sa-primary) 9%,transparent)':'var(--sa-surface2)',
              color: filters[c.id]!=='all'?'var(--sa-primary)':'var(--sa-text2)',
              fontWeight: filters[c.id]!=='all'?600:400,
            }}>
            {c.opts.map(o=><option key={o.v} value={o.v}>{o.l}</option>)}
          </select>
        </div>
      ))}
      {/* Date range */}
      <div style={{ display:'flex', alignItems:'center', gap:5, marginLeft:'auto' }}>
        <span style={{ fontSize:11, fontWeight:600, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.4px' }}>De</span>
        <input type="date" defaultValue="2026-06-01"
          style={{ fontSize:12, padding:'4px 8px', border:'1px solid var(--sa-border)', borderRadius:7, background:'var(--sa-surface2)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)' }}/>
        <span style={{ fontSize:11, color:'var(--sa-text3)' }}>a</span>
        <input type="date" defaultValue="2026-06-30"
          style={{ fontSize:12, padding:'4px 8px', border:'1px solid var(--sa-border)', borderRadius:7, background:'var(--sa-surface2)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)' }}/>
      </div>
      {active && (
        <button onClick={()=>onChange('reset','all')}
          style={{ fontSize:11, fontWeight:600, color:'#ef4444', background:'rgba(239,68,68,.08)', border:'1px solid rgba(239,68,68,.2)', borderRadius:6, padding:'4px 10px', cursor:'pointer', fontFamily:'var(--sa-font-body)' }}>
          ✕ Limpar
        </button>
      )}
    </div>
  );
}

// ── STAT CARD WITH COUNT-UP ───────────────────────────────────
function FinCard({ label, value, trend, positive, icon, sub }) {
  const num = parseFloat(String(value).replace(/[^0-9.]/g,'')) || 0;
  const counted = useCountUp(num);
  const prefix = String(value).match(/^[^0-9]*/)?.[0] || '';
  const suffix = String(value).match(/[^0-9%]*$/)?.[0] || '';
  const display = num ? `${prefix}${counted.toLocaleString('pt-BR', { minimumFractionDigits: prefix.includes('R')? 2:0 })}${suffix}` : value;
  return (
    <div style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, padding:'22px 22px 0', position:'relative', overflow:'hidden', minHeight:148, display:'flex', flexDirection:'column' }}>
      <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:12, opacity:.75 }}>{label}</div>
      <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:32, fontWeight:800, color:'var(--sa-text1)', lineHeight:1, letterSpacing:'-1px' }}>{display}</div>
      {trend && <div style={{ display:'flex', alignItems:'center', gap:4, marginTop:10, fontSize:12, fontWeight:600, color:positive?'#10b981':'#ef4444' }}><Icon name={positive?'trendUp':'trendDown'} size={12}/>{trend} vs. mês anterior</div>}
      {sub && <div style={{ fontSize:12, color:'var(--sa-text3)', marginTop:6 }}>{sub}</div>}
      <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.07, pointerEvents:'none' }}>
        <Icon name={icon} size={130} style={{ color:'var(--sa-primary)' }}/>
      </div>
    </div>
  );
}

// ── MAIN SCREEN ───────────────────────────────────────────────
function FinancialScreen() {
  const [period, setPeriod]   = useState('month');
  const [animated, setAnimated] = useState(false);
  const [filters, setFilters] = useState({ type:'all', status:'all', prof:'all', method:'all' });
  useEffect(() => { const t = setTimeout(()=>setAnimated(true), 100); return ()=>clearTimeout(t); }, []);

  const handleFilter = (id, val) => {
    if (id==='reset') setFilters({ type:'all', status:'all', prof:'all', method:'all' });
    else setFilters(f => ({ ...f, [id]: val }));
  };

  const filtered = useMemo(() => ALL_TX.filter(tx => {
    if (filters.type   !== 'all' && tx.type   !== filters.type)   return false;
    if (filters.status !== 'all' && tx.status !== filters.status)  return false;
    if (filters.prof   !== 'all' && !tx.prof.startsWith(filters.prof)) return false;
    if (filters.method !== 'all' && tx.method !== filters.method)  return false;
    return true;
  }), [filters]);

  const totalRev   = REV_DATA.reduce((s,v)=>s+v, 0);
  const avgTicket  = Math.round(totalRev / ALL_TX.filter(t=>t.status==='paid'&&t.type==='receita').length);
  const commission = Math.round(totalRev * 0.30);
  const toReceive  = ALL_TX.filter(t=>t.status==='pending').reduce((s,t)=>s+t.amount, 0);

  const STATUS_CFG = {
    paid:     { label:'Pago',        bg:'rgba(16,185,129,.1)',  color:'#059669' },
    pending:  { label:'Pendente',    bg:'rgba(245,158,11,.1)',  color:'#d97706' },
    refunded: { label:'Reembolsado', bg:'rgba(239,68,68,.08)', color:'#dc2626' },
  };

  const colSt  = { padding:'12px 14px', fontSize:12, fontWeight:600, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', borderBottom:'1px solid var(--sa-border)', background:'var(--sa-surface2)' };
  const cellSt = { padding:'12px 14px', fontSize:13, color:'var(--sa-text1)', borderBottom:'1px solid var(--sa-border)', verticalAlign:'middle' };

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Financeiro" subtitle="Receita, comissões e pagamentos"
        actions={
          <div style={{ display:'flex', gap:8 }}>
            {[['month','Este mês'],['quarter','Trimestre'],['year','Este ano']].map(([id,lbl])=>(
              <Btn key={id} size="sm" variant={period===id?'primary':'muted'} onClick={()=>setPeriod(id)}>{lbl}</Btn>
            ))}
          </div>
        }/>

      <div style={{ padding:'24px 32px 0' }}>
        {/* Stat Cards */}
        <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:16, marginBottom:20 }}>
          <FinCard label="Receita Total"    value={SA_FMT.currency(totalRev)}  trend="+14%" positive icon="dollar"  sub="Junho 2026"/>
          <FinCard label="Ticket Médio"     value={SA_FMT.currency(avgTicket)} trend="+5%"  positive icon="arrowUp" sub={`${ALL_TX.filter(t=>t.status==='paid'&&t.type==='receita').length} atend.`}/>
          <FinCard label="Comissões"        value={SA_FMT.currency(commission)} trend="30%" positive={false} icon="users" sub="da receita total"/>
          <FinCard label="A Receber"        value={SA_FMT.currency(toReceive)} trend={null} positive icon="clock"  sub={`${ALL_TX.filter(t=>t.status==='pending').length} pendentes`}/>
        </div>

        {/* Filter bar */}
        <FinFilterBar filters={filters} onChange={handleFilter}/>

        <div style={{ display:'grid', gridTemplateColumns:'1fr 300px', gap:20, marginBottom:20 }}>
          {/* Revenue chart */}
          <Card style={{ padding:24 }}>
            <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:20 }}>
              <div>
                <h3 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:0 }}>Receita Diária</h3>
                <p style={{ fontSize:12, color:'var(--sa-text3)', margin:'4px 0 0' }}>Junho 2026</p>
              </div>
              <div style={{ textAlign:'right' }}>
                <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:22, fontWeight:800, color:'var(--sa-secondary)' }}>{SA_FMT.currency(totalRev)}</div>
                <div style={{ fontSize:12, color:'#10b981', display:'flex', alignItems:'center', gap:4, justifyContent:'flex-end' }}>
                  <Icon name="trendUp" size={11}/> +14% vs. maio
                </div>
              </div>
            </div>
            <RevenueChart data={REV_DATA}/>
          </Card>
          {/* Payment methods */}
          <Card style={{ padding:22 }}>
            <h3 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 20px' }}>Formas de Pagamento</h3>
            <PaymentMethods data={FIN_METHODS} animated={animated}/>
          </Card>
        </div>

        <div style={{ display:'grid', gridTemplateColumns:'1fr 300px', gap:20 }}>
          {/* Transactions */}
          <Card style={{ padding:0, overflow:'hidden' }}>
            <div style={{ padding:'18px 20px 0', display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:4 }}>
              <div>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:0 }}>Transações</h3>
                <span style={{ fontSize:12, color:'var(--sa-text3)' }}>{filtered.length} resultado{filtered.length!==1?'s':''}</span>
              </div>
              <Btn variant="ghost" size="sm" onClick={()=>window.SA_TOAST('Exportando CSV…','info')} icon={<Icon name="arrowDown" size={13}/>}>Exportar</Btn>
            </div>
            <div style={{ overflowX:'auto' }}>
              <table style={{ width:'100%', borderCollapse:'collapse' }}>
                <thead>
                  <tr>{['Data','Cliente','Serviço','Profissional','Tipo','Método','Valor','Status'].map(h=><th key={h} style={colSt}>{h}</th>)}</tr>
                </thead>
                <tbody>
                  {filtered.length === 0
                    ? <tr><td colSpan={8} style={{ padding:'32px', textAlign:'center', color:'var(--sa-text3)', fontSize:14 }}>Nenhuma transação encontrada</td></tr>
                    : filtered.map(tx => {
                      const sc = STATUS_CFG[tx.status] || STATUS_CFG.paid;
                      return (
                        <tr key={tx.id}
                          onMouseEnter={e=>e.currentTarget.style.background='var(--sa-surface2)'}
                          onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
                          <td style={cellSt}>{SA_FMT.short(tx.date)}</td>
                          <td style={{ ...cellSt, fontWeight:600 }}>{tx.client}</td>
                          <td style={{ ...cellSt, color:'var(--sa-text2)' }}>{tx.service}</td>
                          <td style={{ ...cellSt, color:'var(--sa-text2)' }}>{tx.prof.split(' ')[0]}</td>
                          <td style={cellSt}>
                            <span style={{ fontSize:11, fontWeight:600, padding:'2px 8px', borderRadius:20, background:tx.type==='receita'?'rgba(16,185,129,.1)':'rgba(239,68,68,.08)', color:tx.type==='receita'?'#059669':'#dc2626' }}>{tx.type}</span>
                          </td>
                          <td style={cellSt}>
                            <span style={{ fontSize:11, fontWeight:600, padding:'3px 8px', borderRadius:6, background:'var(--sa-surface2)', color:'var(--sa-text2)' }}>{tx.method}</span>
                          </td>
                          <td style={{ ...cellSt, fontWeight:700 }}>{SA_FMT.currency(tx.amount)}</td>
                          <td style={cellSt}>
                            <span style={{ fontSize:11, fontWeight:600, padding:'3px 9px', borderRadius:20, background:sc.bg, color:sc.color }}>{sc.label}</span>
                          </td>
                        </tr>
                      );
                    })
                  }
                </tbody>
              </table>
            </div>
          </Card>

          {/* Commissions */}
          <Card style={{ padding:22 }}>
            <h3 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 20px' }}>Comissões por Profissional</h3>
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              {SA_PROFESSIONALS.map((p,i) => (
                <ProfCommission key={p.id} prof={p} total={totalRev} pct={[.42,.35,.23][i]} animated={animated}/>
              ))}
            </div>
            <div style={{ marginTop:20, paddingTop:16, borderTop:'1px solid var(--sa-border)', display:'flex', justifyContent:'space-between', alignItems:'center' }}>
              <span style={{ fontSize:13, color:'var(--sa-text2)' }}>Total comissões</span>
              <span style={{ fontSize:16, fontWeight:800, color:'var(--sa-text1)', fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)" }}>{SA_FMT.currency(commission)}</span>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { FinancialScreen });
