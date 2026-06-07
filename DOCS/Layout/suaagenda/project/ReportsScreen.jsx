// ============================================================
// suaAgenda.pro — Reports Screen v2 (date range + comparison)
// ============================================================
const { useState, useMemo, useEffect } = React;

// ── SAMPLE DATA ───────────────────────────────────────────────
const MONTHS = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

const MONTHLY_DATA = [
  { month:'Jan', revenue:8420,  expenses:3100, appointments:142, newClients:18 },
  { month:'Fev', revenue:7980,  expenses:2900, appointments:131, newClients:14 },
  { month:'Mar', revenue:9200,  expenses:3400, appointments:156, newClients:22 },
  { month:'Abr', revenue:8750,  expenses:3000, appointments:148, newClients:19 },
  { month:'Mai', revenue:10100, expenses:3600, appointments:172, newClients:27 },
  { month:'Jun', revenue:12450, expenses:4100, appointments:198, newClients:31 },
];

const SVC_REVENUE = [
  { name:'Corte',         rev:3200, count:71 },
  { name:'Corte+Barba',   rev:4125, count:55 },
  { name:'Barba',         rev:1750, count:50 },
  { name:'Coloração',     rev:1980, count:11 },
  { name:'Hidratação',    rev:900,  count:10 },
  { name:'Barba+Bigode',  rev:495,  count:9  },
];

const PEAK_HOURS = [
  { hour:'08:00', mon:3, tue:2, wed:4, thu:3, fri:5, sat:6 },
  { hour:'09:00', mon:5, tue:4, wed:6, thu:5, fri:7, sat:8 },
  { hour:'10:00', mon:7, tue:6, wed:8, thu:7, fri:9, sat:10 },
  { hour:'11:00', mon:6, tue:7, wed:7, thu:8, fri:8, sat:9  },
  { hour:'12:00', mon:4, tue:3, wed:5, thu:4, fri:6, sat:7  },
  { hour:'13:00', mon:3, tue:2, wed:3, thu:3, fri:5, sat:6  },
  { hour:'14:00', mon:6, tue:5, wed:6, thu:6, fri:7, sat:8  },
  { hour:'15:00', mon:8, tue:7, wed:8, thu:9, fri:10,sat:9  },
  { hour:'16:00', mon:7, tue:8, wed:7, thu:8, fri:9, sat:7  },
  { hour:'17:00', mon:5, tue:6, wed:6, thu:7, fri:8, sat:5  },
  { hour:'18:00', mon:4, tue:4, wed:5, thu:5, fri:6, sat:3  },
  { hour:'19:00', mon:2, tue:3, wed:3, thu:4, fri:4, sat:1  },
];

// ── MINI LINE CHART ───────────────────────────────────────────
function MiniLine({ data, color='var(--sa-secondary)', w=80, h=44 }) {
  const [anim, setAnim] = useState(false);
  useEffect(() => { const t = setTimeout(()=>setAnim(true),80); return ()=>clearTimeout(t); },[]);
  if (!data||data.length<2) return null;
  const max=Math.max(...data), min=Math.min(...data), rng=max-min||1;
  const pts=data.map((v,i)=>({ x:(i/(data.length-1))*w, y:h-((v-min)/rng)*(h-8)-4 }));
  const line=pts.map((p,i)=>`${i?'L':'M'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ');
  return (
    <svg width={w} height={h} style={{ display:'block', overflow:'visible' }}>
      <defs><clipPath id={`mc${w}${h}`}><rect x="0" y="0" width={anim?w:0} height={h} style={{transition:'width 1s ease'}}/></clipPath></defs>
      <path d={line} fill="none" stroke={color} strokeWidth="2" strokeLinecap="round" clipPath={`url(#mc${w}${h})`}/>
      {anim&&<circle cx={pts[pts.length-1].x} cy={pts[pts.length-1].y} r={3} fill={color} stroke="var(--sa-surface)" strokeWidth={2}/>}
    </svg>
  );
}

// ── COMPARISON BAR CHART ──────────────────────────────────────
function ComparisonChart({ data }) {
  const [anim, setAnim] = useState(false);
  useEffect(()=>{ const t=setTimeout(()=>setAnim(true),120); return()=>clearTimeout(t); },[data]);
  const maxVal = Math.max(...data.map(d=>Math.max(d.revenue,d.expenses)));
  const W=560, H=160, barW=28, gap=8;
  const groupW = barW*2+gap+20;
  return (
    <div style={{ width:'100%' }}>
      <svg width="100%" viewBox={`0 0 ${W} ${H+30}`} preserveAspectRatio="none" style={{ display:'block' }}>
        {/* Grid lines */}
        {[0.25,0.5,0.75,1].map(f=>(
          <line key={f} x1={40} y1={H-f*H} x2={W} y2={H-f*H} stroke="var(--sa-border)" strokeWidth={1} strokeDasharray="4 3" opacity={.6}/>
        ))}
        {/* Y labels */}
        {[0.25,0.5,0.75,1].map(f=>(
          <text key={f} x={36} y={H-f*H+4} textAnchor="end" fontSize={9} fill="var(--sa-text3)">{`R$${Math.round(maxVal*f/1000)}k`}</text>
        ))}
        {/* Bars */}
        {data.map((d,i)=>{
          const cx = 50 + i*groupW + groupW/2;
          const revH = (d.revenue/maxVal)*H;
          const expH = (d.expenses/maxVal)*H;
          const profit = d.revenue - d.expenses;
          return (
            <g key={d.month}>
              {/* Revenue bar */}
              <rect x={cx-barW-gap/2} y={H-(anim?revH:0)} width={barW} height={anim?revH:0} rx={3} fill="var(--sa-secondary)" opacity={.85} style={{transition:`height 700ms ease ${i*80}ms, y 700ms ease ${i*80}ms`}}/>
              {/* Expense bar */}
              <rect x={cx+gap/2} y={H-(anim?expH:0)} width={barW} height={anim?expH:0} rx={3} fill="#ef4444" opacity={.7} style={{transition:`height 700ms ease ${i*80}ms, y 700ms ease ${i*80}ms`}}/>
              {/* Month label */}
              <text x={cx} y={H+16} textAnchor="middle" fontSize={10} fill="var(--sa-text3)">{d.month}</text>
              {/* Profit indicator */}
              <text x={cx} y={H-Math.max(revH,expH)-6} textAnchor="middle" fontSize={8} fill={profit>0?'#10b981':'#ef4444'} fontWeight="700">{profit>0?'+':''}{Math.round(profit/1000)}k</text>
            </g>
          );
        })}
      </svg>
      {/* Legend */}
      <div style={{ display:'flex', gap:16, justifyContent:'center', marginTop:4 }}>
        {[['Receita','var(--sa-secondary)'],['Despesas','#ef4444']].map(([l,c])=>(
          <div key={l} style={{ display:'flex', alignItems:'center', gap:6 }}>
            <div style={{ width:10, height:10, borderRadius:2, background:c }}/>
            <span style={{ fontSize:11, color:'var(--sa-text3)' }}>{l}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

// ── HEATMAP ───────────────────────────────────────────────────
function Heatmap({ data }) {
  const [anim, setAnim] = useState(false);
  useEffect(()=>{ const t=setTimeout(()=>setAnim(true),100); return()=>clearTimeout(t); },[]);
  const days = [['mon','Seg'],['tue','Ter'],['wed','Qua'],['thu','Qui'],['fri','Sex'],['sat','Sáb']];
  const allVals = data.flatMap(r => days.map(([k])=>r[k]));
  const max = Math.max(...allVals);
  return (
    <div style={{ overflowX:'auto' }}>
      <table style={{ borderCollapse:'separate', borderSpacing:3, tableLayout:'fixed' }}>
        <thead>
          <tr>
            <th style={{ width:52, fontSize:10, color:'var(--sa-text3)', fontWeight:500 }}></th>
            {days.map(([,l])=><th key={l} style={{ width:42, fontSize:10, color:'var(--sa-text3)', fontWeight:600, textAlign:'center', paddingBottom:4 }}>{l}</th>)}
          </tr>
        </thead>
        <tbody>
          {data.map(row=>(
            <tr key={row.hour}>
              <td style={{ fontSize:10, color:'var(--sa-text3)', textAlign:'right', paddingRight:6, fontWeight:500 }}>{row.hour}</td>
              {days.map(([key])=>{
                const val = row[key];
                const intensity = val/max;
                return (
                  <td key={key} title={`${val} agendamentos`}
                    style={{ width:42, height:24, borderRadius:4, background:anim?`color-mix(in srgb, var(--sa-secondary) ${Math.round(intensity*85)}%, var(--sa-surface2))`:'var(--sa-surface2)', transition:`background 600ms ease`, textAlign:'center', verticalAlign:'middle', cursor:'default' }}>
                    {val>=7&&<span style={{ fontSize:9, fontWeight:700, color:intensity>0.6?'#fff':'var(--sa-text1)' }}>{val}</span>}
                  </td>
                );
              })}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ── METRIC CARD ───────────────────────────────────────────────
function MetricCard({ label, value, sub, trend, positive, sparkData, color, icon }) {
  const num     = parseFloat(String(value).replace(/[^0-9.]/g,'')) || 0;
  const counted = useCountUp(num);
  const prefix  = String(value).match(/^[^0-9]*/)?.[0]  || '';
  const suffix  = String(value).match(/[^0-9%]*$/)?.[0] || '';
  const display = num ? `${prefix}${counted.toLocaleString('pt-BR',{minimumFractionDigits:prefix.includes('R')?2:0})}${suffix}` : value;
  return (
    <div style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, padding:'22px 22px 0', position:'relative', overflow:'hidden', minHeight:148, display:'flex', flexDirection:'column' }}>
      <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:12, opacity:.75 }}>{label}</div>
      <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start' }}>
        <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:32, fontWeight:800, color:'var(--sa-text1)', lineHeight:1, letterSpacing:'-1px' }}>{display}</div>
        {sparkData&&<MiniLine data={sparkData} color={color||'var(--sa-secondary)'} w={72} h={38}/>}
      </div>
      {sub&&<div style={{ fontSize:12, color:'var(--sa-text3)', marginTop:6 }}>{sub}</div>}
      {trend&&<div style={{ display:'flex', alignItems:'center', gap:4, marginTop:10, fontSize:12, fontWeight:600, color:positive?'#10b981':'#ef4444' }}><Icon name={positive?'trendUp':'trendDown'} size={12}/>{trend}</div>}
      {icon&&<div style={{ position:'absolute', bottom:-32, right:-26, opacity:.07, pointerEvents:'none' }}><Icon name={icon} size={130} style={{ color:'var(--sa-primary)' }}/></div>}
    </div>
  );
}

// ── DATE RANGE FILTER ─────────────────────────────────────────
function DateRangeBar({ from, to, preset, onFrom, onTo, onPreset }) {
  const PRESETS = [['7d','7 dias'],['30d','30 dias'],['3m','3 meses'],['6m','6 meses'],['year','Este ano'],['custom','Personalizado']];
  return (
    <div style={{ display:'flex', gap:10, alignItems:'center', padding:'10px 16px', background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:10, marginBottom:20, flexWrap:'wrap' }}>
      <Icon name="calendar" size={14} style={{ color:'var(--sa-text3)', flexShrink:0 }}/>
      <div style={{ display:'flex', gap:4 }}>
        {PRESETS.map(([v,l])=>(
          <button key={v} onClick={()=>onPreset(v)} style={{ padding:'5px 11px', borderRadius:7, border:'none', fontSize:12, fontWeight:preset===v?700:500, background:preset===v?'var(--sa-primary)':'var(--sa-surface2)', color:preset===v?'#fff':'var(--sa-text2)', cursor:'pointer', fontFamily:'var(--sa-font-body)', transition:'all 150ms' }}>{l}</button>
        ))}
      </div>
      {preset==='custom'&&(
        <div style={{ display:'flex', alignItems:'center', gap:6, marginLeft:'auto' }}>
          <input type="date" value={from} onChange={e=>onFrom(e.target.value)} style={{ fontSize:12, padding:'4px 8px', border:'1px solid var(--sa-border)', borderRadius:7, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)' }}/>
          <span style={{ fontSize:11, color:'var(--sa-text3)' }}>a</span>
          <input type="date" value={to} onChange={e=>onTo(e.target.value)} style={{ fontSize:12, padding:'4px 8px', border:'1px solid var(--sa-border)', borderRadius:7, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)' }}/>
        </div>
      )}
    </div>
  );
}

// ── ANIMATED BAR ─────────────────────────────────────────────
function AnimBar({ label, value, max, color='var(--sa-secondary)', sub }) {
  const [anim, setAnim] = useState(false);
  useEffect(()=>{ const t=setTimeout(()=>setAnim(true),100); return()=>clearTimeout(t); },[]);
  const pct = Math.min(value/max*100, 100);
  return (
    <div style={{ marginBottom:12 }}>
      <div style={{ display:'flex', justifyContent:'space-between', marginBottom:5 }}>
        <span style={{ fontSize:13, color:'var(--sa-text1)', fontWeight:500 }}>{label}</span>
        <div style={{ textAlign:'right' }}>
          <span style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)' }}>{SA_FMT.currency(value)}</span>
          {sub&&<span style={{ fontSize:11, color:'var(--sa-text3)', marginLeft:6 }}>{sub}</span>}
        </div>
      </div>
      <div style={{ height:8, borderRadius:4, background:'var(--sa-surface2)', overflow:'hidden' }}>
        <div style={{ height:'100%', borderRadius:4, background:color, width:anim?`${pct}%`:'0%', transition:'width 800ms cubic-bezier(.4,0,.2,1)' }}/>
      </div>
    </div>
  );
}

// ── MAIN REPORTS SCREEN ───────────────────────────────────────
function ReportsScreen() {
  const [tab,    setTab]    = useState('overview');
  const [preset, setPreset] = useState('6m');
  const [from,   setFrom]   = useState('2026-01-01');
  const [to,     setTo]     = useState('2026-06-30');

  const handlePreset = (p) => {
    setPreset(p);
    if (p==='7d')   { setFrom('2026-05-31'); setTo('2026-06-07'); }
    if (p==='30d')  { setFrom('2026-05-07'); setTo('2026-06-07'); }
    if (p==='3m')   { setFrom('2026-03-01'); setTo('2026-06-07'); }
    if (p==='6m')   { setFrom('2026-01-01'); setTo('2026-06-07'); }
    if (p==='year') { setFrom('2026-01-01'); setTo('2026-12-31'); }
  };

  const sparkArr = MONTHLY_DATA.map(d=>d.revenue);
  const totalRev = MONTHLY_DATA.reduce((s,d)=>s+d.revenue,0);
  const totalExp = MONTHLY_DATA.reduce((s,d)=>s+d.expenses,0);
  const totalProfit = totalRev - totalExp;
  const avgTicket = Math.round(totalRev / MONTHLY_DATA.reduce((s,d)=>s+d.appointments,0));
  const totalAppts = MONTHLY_DATA.reduce((s,d)=>s+d.appointments,0);
  const totalNew   = MONTHLY_DATA.reduce((s,d)=>s+d.newClients,0);

  const profStats = [
    { ...SA_PROFESSIONALS[0], appts: 89, revenue: 5220, rating: 4.9, noShow: 3 },
    { ...SA_PROFESSIONALS[1], appts: 73, revenue: 4380, rating: 4.7, noShow: 5 },
    { ...SA_PROFESSIONALS[2], appts: 54, revenue: 3030, rating: 4.8, noShow: 2 },
  ];

  const TABS = [
    { id:'overview',    label:'Visão Geral',    icon:'chart'   },
    { id:'revenue',     label:'Receita',        icon:'dollar'  },
    { id:'clients',     label:'Clientes',       icon:'users'   },
    { id:'professionals',label:'Profissionais', icon:'user'    },
  ];

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Relatórios" subtitle="Análise completa do desempenho do negócio"
        actions={<Btn onClick={()=>window.SA_TOAST('Exportando relatório…','info')} icon={<Icon name="arrowDown" size={15}/>}>Exportar</Btn>}/>

      <div style={{ padding:'16px 32px 0' }}>
        {/* Date range */}
        <DateRangeBar from={from} to={to} preset={preset} onFrom={setFrom} onTo={setTo} onPreset={handlePreset}/>

        {/* Tab nav */}
        <div style={{ display:'flex', gap:4, borderBottom:'1px solid var(--sa-border)', marginBottom:20 }}>
          {TABS.map(t=>(
            <button key={t.id} onClick={()=>setTab(t.id)} style={{ display:'flex', alignItems:'center', gap:7, padding:'9px 16px', border:'none', cursor:'pointer', background:'transparent', fontFamily:'var(--sa-font-body)', fontSize:13, fontWeight:tab===t.id?600:500, color:tab===t.id?'var(--sa-primary)':'var(--sa-text3)', borderBottom:tab===t.id?'2px solid var(--sa-primary)':'2px solid transparent', marginBottom:-1, transition:'all 160ms' }}>
              <Icon name={t.icon} size={14}/>{t.label}
            </button>
          ))}
        </div>

        {/* ── VISÃO GERAL ──────────────────────────────────────── */}
        {tab==='overview'&&(
          <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
            {/* Metric cards */}
            <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:16 }}>
              <MetricCard label="Receita Total"   value={SA_FMT.currency(totalRev)}    trend="+14% vs. período ant." positive sparkData={sparkArr}            color="var(--sa-secondary)" icon="dollar"/>
              <MetricCard label="Lucro Líquido"   value={SA_FMT.currency(totalProfit)} trend={`margem ${Math.round(totalProfit/totalRev*100)}%`} positive sparkData={MONTHLY_DATA.map(d=>d.revenue-d.expenses)} color="#10b981" icon="trendUp"/>
              <MetricCard label="Atendimentos"    value={totalAppts}                   trend="+12% vs. período ant." positive sparkData={MONTHLY_DATA.map(d=>d.appointments)} color="#6366f1" icon="calendar"/>
              <MetricCard label="Novos Clientes"  value={totalNew}                     trend="+8% vs. período ant."  positive sparkData={MONTHLY_DATA.map(d=>d.newClients)} color="#f59e0b" icon="users"/>
            </div>

            {/* Comparison chart + breakdown */}
            <div style={{ display:'grid', gridTemplateColumns:'1fr 320px', gap:20 }}>
              <Card style={{ padding:22 }}>
                <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:20 }}>
                  <div>
                    <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Receita vs. Despesas</h3>
                    <p style={{ fontSize:12, color:'var(--sa-text3)', margin:0 }}>Comparação mensal — Janeiro a Junho 2026</p>
                  </div>
                  <div style={{ textAlign:'right' }}>
                    <div style={{ fontFamily:"var(--sa-font-heading)", fontSize:20, fontWeight:800, color:'#10b981' }}>+{Math.round(totalProfit/totalRev*100)}%</div>
                    <div style={{ fontSize:11, color:'var(--sa-text3)' }}>Margem média</div>
                  </div>
                </div>
                <ComparisonChart data={MONTHLY_DATA}/>
              </Card>

              {/* Summary breakdown */}
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Resumo Financeiro</h3>
                {[
                  { l:'Receita bruta',   v:SA_FMT.currency(totalRev),    c:'var(--sa-secondary)' },
                  { l:'Total despesas',  v:SA_FMT.currency(totalExp),    c:'#ef4444' },
                  { l:'Lucro líquido',   v:SA_FMT.currency(totalProfit), c:'#10b981' },
                  { l:'Ticket médio',    v:SA_FMT.currency(avgTicket),   c:'var(--sa-text1)' },
                  { l:'Atendimentos',    v:totalAppts,                   c:'var(--sa-text1)' },
                  { l:'Novos clientes',  v:totalNew,                     c:'var(--sa-text1)' },
                ].map((r,i,arr)=>(
                  <div key={r.l} style={{ display:'flex', justifyContent:'space-between', padding:'9px 0', borderBottom:i<arr.length-1?'1px solid var(--sa-border)':'none' }}>
                    <span style={{ fontSize:13, color:'var(--sa-text3)' }}>{r.l}</span>
                    <span style={{ fontSize:13, fontWeight:700, color:r.c }}>{r.v}</span>
                  </div>
                ))}
              </Card>
            </div>

            {/* Peak hours heatmap */}
            <Card style={{ padding:22 }}>
              <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Horários de Pico</h3>
              <p style={{ fontSize:12, color:'var(--sa-text3)', margin:'0 0 16px' }}>Número de atendimentos por hora e dia da semana</p>
              <Heatmap data={PEAK_HOURS}/>
              <div style={{ display:'flex', gap:8, alignItems:'center', marginTop:10 }}>
                <span style={{ fontSize:11, color:'var(--sa-text3)' }}>Menos</span>
                {[5,20,40,60,85].map(v=>(
                  <div key={v} style={{ width:18, height:12, borderRadius:3, background:`color-mix(in srgb, var(--sa-secondary) ${v}%, var(--sa-surface2))` }}/>
                ))}
                <span style={{ fontSize:11, color:'var(--sa-text3)' }}>Mais</span>
              </div>
            </Card>
          </div>
        )}

        {/* ── RECEITA ──────────────────────────────────────────── */}
        {tab==='revenue'&&(
          <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
            <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:16 }}>
              <MetricCard label="Receita Junho"   value={SA_FMT.currency(12450)} trend="+14% vs. maio" positive sparkData={sparkArr} color="var(--sa-secondary)" icon="dollar"/>
              <MetricCard label="Ticket Médio"    value={SA_FMT.currency(avgTicket)} trend="+5%" positive sparkData={[55,58,60,62,64,avgTicket]} color="#6366f1" icon="arrowUp"/>
              <MetricCard label="Projeção Anual"  value={SA_FMT.currency(Math.round(totalRev/6*12))} trend="+18% vs. 2025" positive sparkData={MONTHLY_DATA.map(d=>d.revenue)} color="#10b981" icon="trendUp"/>
            </div>
            <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 20px' }}>Receita por Serviço</h3>
                {SVC_REVENUE.map((s,i)=>(
                  <AnimBar key={s.name} label={s.name} value={s.rev} max={SVC_REVENUE[0].rev} sub={`${s.count} atend.`}
                    color={['var(--sa-secondary)','#6366f1','#10b981','#f59e0b','#ec4899','#0ea5e9'][i]}/>
                ))}
              </Card>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 20px' }}>Receita por Profissional</h3>
                {profStats.map((p,i)=>(
                  <AnimBar key={p.id} label={p.name.split(' ')[0]} value={p.revenue} max={profStats[0].revenue} sub={`${p.appts} atend.`} color={p.color}/>
                ))}
                <div style={{ marginTop:20, paddingTop:16, borderTop:'1px solid var(--sa-border)' }}>
                  <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', marginBottom:10 }}>Comissões Pagas</div>
                  {profStats.map(p=>(
                    <div key={p.id} style={{ display:'flex', justifyContent:'space-between', padding:'7px 0', borderBottom:'1px solid var(--sa-border)' }}>
                      <div style={{ display:'flex', alignItems:'center', gap:8 }}><Avt name={p.name} size={22} color={p.color}/><span style={{ fontSize:13, color:'var(--sa-text2)' }}>{p.name.split(' ')[0]}</span></div>
                      <span style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)' }}>{SA_FMT.currency(Math.round(p.revenue*0.38))}</span>
                    </div>
                  ))}
                </div>
              </Card>
            </div>
          </div>
        )}

        {/* ── CLIENTES ─────────────────────────────────────────── */}
        {tab==='clients'&&(
          <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
            <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:16 }}>
              <MetricCard label="Total de Clientes" value="247" sub="Cadastrados na base" trend="+23 este mês" positive sparkData={[180,195,200,215,228,247]} color="#6366f1" icon="users"/>
              <MetricCard label="Clientes Ativos"   value="189" sub="Agendamento em 60 dias" trend="76% da base" positive sparkData={[140,152,158,168,180,189]} color="#10b981" icon="check"/>
              <MetricCard label="Taxa de Retorno"   value="73%" sub="Clientes recorrentes" trend="+5% vs. mês ant." positive sparkData={[62,65,68,69,71,73]} color="var(--sa-secondary)" icon="refresh"/>
            </div>
            <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 20px' }}>Novos vs. Recorrentes (mensal)</h3>
                {MONTHLY_DATA.map((d,i)=>{
                  const recurring = d.appointments - d.newClients;
                  return (
                    <div key={d.month} style={{ display:'grid', gridTemplateColumns:'36px 1fr 1fr', gap:8, alignItems:'center', marginBottom:10 }}>
                      <span style={{ fontSize:11, color:'var(--sa-text3)', fontWeight:600 }}>{d.month}</span>
                      <div>
                        <div style={{ height:8, borderRadius:4, background:'var(--sa-surface2)', overflow:'hidden', marginBottom:2 }}>
                          <div style={{ height:'100%', background:'var(--sa-secondary)', width:`${Math.round(d.newClients/d.appointments*100)}%`, borderRadius:4, transition:'width 700ms ease' }}/>
                        </div>
                        <span style={{ fontSize:10, color:'var(--sa-text3)' }}>Novos: {d.newClients}</span>
                      </div>
                      <div>
                        <div style={{ height:8, borderRadius:4, background:'var(--sa-surface2)', overflow:'hidden', marginBottom:2 }}>
                          <div style={{ height:'100%', background:'#6366f1', width:`${Math.round(recurring/d.appointments*100)}%`, borderRadius:4, transition:'width 700ms ease' }}/>
                        </div>
                        <span style={{ fontSize:10, color:'var(--sa-text3)' }}>Recorrentes: {recurring}</span>
                      </div>
                    </div>
                  );
                })}
              </Card>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 20px' }}>Segmentação de Clientes</h3>
                {[
                  { l:'VIP (10+ visitas)',    n:38,  pct:15, c:'var(--sa-secondary)' },
                  { l:'Frequente (5-9)',      n:72,  pct:29, c:'#6366f1'            },
                  { l:'Regular (2-4)',        n:89,  pct:36, c:'#10b981'            },
                  { l:'Novos (1 visita)',     n:48,  pct:19, c:'#f59e0b'            },
                ].map(s=>(
                  <div key={s.l} style={{ marginBottom:14 }}>
                    <div style={{ display:'flex', justifyContent:'space-between', marginBottom:5 }}>
                      <span style={{ fontSize:13, color:'var(--sa-text1)', fontWeight:500 }}>{s.l}</span>
                      <span style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)' }}>{s.n} ({s.pct}%)</span>
                    </div>
                    <div style={{ height:8, borderRadius:4, background:'var(--sa-surface2)', overflow:'hidden' }}>
                      <div style={{ height:'100%', borderRadius:4, background:s.c, width:`${s.pct}%`, transition:'width 700ms ease' }}/>
                    </div>
                  </div>
                ))}
                <div style={{ marginTop:20, padding:'12px', background:'var(--sa-surface2)', borderRadius:10, border:'1px solid var(--sa-border)' }}>
                  <div style={{ fontSize:12, fontWeight:700, color:'var(--sa-text1)', marginBottom:4 }}>⚠ No-show este mês</div>
                  <div style={{ fontSize:24, fontWeight:800, color:'#ef4444', fontFamily:"var(--sa-font-heading)" }}>10 <span style={{ fontSize:13, color:'var(--sa-text3)', fontWeight:400 }}>clientes (5.3%)</span></div>
                </div>
              </Card>
            </div>
          </div>
        )}

        {/* ── PROFISSIONAIS ─────────────────────────────────────── */}
        {tab==='professionals'&&(
          <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
            <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:16 }}>
              {profStats.map(p=>(
                <div key={p.id} style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, overflow:'hidden', position:'relative' }}>
                  <div style={{ height:4, background:p.color }}/>
                  <div style={{ padding:'18px 18px 0' }}>
                    <div style={{ display:'flex', alignItems:'center', gap:12, marginBottom:16 }}>
                      <Avt name={p.name} size={44} color={p.color}/>
                      <div>
                        <div style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:700, color:'var(--sa-text1)' }}>{p.name}</div>
                        <div style={{ fontSize:12, color:'var(--sa-text3)', marginTop:2 }}>{p.role}</div>
                      </div>
                    </div>
                    {[{l:'Atendimentos',v:p.appts},{l:'Receita',v:SA_FMT.currency(p.revenue)},{l:'Avaliação',v:`★ ${p.rating}`},{l:'No-shows',v:p.noShow}].map((r,i,arr)=>(
                      <div key={r.l} style={{ display:'flex', justifyContent:'space-between', padding:'7px 0', borderBottom:i<arr.length-1?`1px solid color-mix(in srgb,var(--sa-primary) 12%,transparent)`:'none' }}>
                        <span style={{ fontSize:12, color:'var(--sa-text3)' }}>{r.l}</span>
                        <span style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)' }}>{r.v}</span>
                      </div>
                    ))}
                    <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.07 }}><Icon name="user" size={130} style={{ color:'var(--sa-primary)' }}/></div>
                  </div>
                  <div style={{ padding:'12px 16px 16px' }}>
                    <AnimBar label="Participação receita" value={p.revenue} max={profStats[0].revenue} color={p.color}/>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

Object.assign(window, { ReportsScreen });
