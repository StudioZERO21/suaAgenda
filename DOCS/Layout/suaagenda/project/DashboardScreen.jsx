// ============================================================
// suaAgenda.pro — Dashboard Screen v2
// ============================================================
const { useState, useMemo, useEffect } = React;

// ── ANIMATED DONUT CHART ─────────────────────────────────────
function DonutChart({ segments, total }) {
  const [animated, setAnimated] = useState(false);
  useEffect(() => { const t = setTimeout(() => setAnimated(true), 120); return () => clearTimeout(t); }, []);

  const R = 58, CX = 80, CY = 80;
  const CIRC = 2 * Math.PI * R;
  let cumPct = 0;

  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 28 }}>
      {/* SVG Donut */}
      <div style={{ position: 'relative', flexShrink: 0, width: 160, height: 160 }}>
        <svg width={160} height={160} viewBox="0 0 160 160" style={{ transform: 'rotate(-90deg)' }}>
          {/* Track */}
          <circle cx={CX} cy={CY} r={R} fill="none" stroke="var(--sa-surface2)" strokeWidth={18} />
          {segments.map((seg, i) => {
            const dashLen = animated ? (seg.pct / 100) * CIRC : 0;
            const offset  = -(cumPct / 100) * CIRC;
            cumPct += seg.pct;
            return (
              <circle key={i} cx={CX} cy={CY} r={R} fill="none"
                stroke={seg.color} strokeWidth={18}
                strokeLinecap="round"
                strokeDasharray={`${dashLen} ${CIRC}`}
                strokeDashoffset={offset}
                style={{ transition: `stroke-dasharray 900ms cubic-bezier(.4,0,.2,1) ${i*160}ms` }}
              />
            );
          })}
        </svg>
        {/* Center label */}
        <div style={{ position: 'absolute', inset: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>
          <div style={{ fontFamily: "'Poppins',sans-serif", fontSize: 26, fontWeight: 800, color: 'var(--sa-text1)', lineHeight: 1 }}>{total}</div>
          <div style={{ fontSize: 10, color: 'var(--sa-text3)', fontWeight: 600, letterSpacing: '.5px', marginTop: 2 }}>TOTAL</div>
        </div>
      </div>

      {/* Legend */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16, flex: 1 }}>
        {segments.map(seg => (
          <div key={seg.label}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 5 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <div style={{ width: 8, height: 8, borderRadius: '50%', background: seg.color, flexShrink: 0 }} />
                <span style={{ fontSize: 12, color: 'var(--sa-text2)', fontWeight: 500 }}>{seg.label}</span>
              </div>
              <span style={{ fontFamily: "'Poppins',sans-serif", fontSize: 15, fontWeight: 700, color: 'var(--sa-text1)' }}>{seg.pct}%</span>
            </div>
            {/* Progress bar */}
            <div style={{ height: 4, borderRadius: 2, background: 'var(--sa-surface2)', overflow: 'hidden' }}>
              <div style={{ height: '100%', borderRadius: 2, background: seg.color,
                width: animated ? `${seg.pct}%` : '0%',
                transition: `width 900ms cubic-bezier(.4,0,.2,1) ${segments.indexOf(seg)*160}ms`,
              }} />
            </div>
            <div style={{ fontSize: 11, color: 'var(--sa-text3)', marginTop: 3 }}>{seg.count} agendamentos</div>
          </div>
        ))}
      </div>
    </div>
  );
}

// ── STAT CARD ─────────────────────────────────────────────────
function StatCard({ label, value, trend, positive, icon, tint }) {
  // Parse numeric part for count-up
  const numericStr = String(value).replace(/[^0-9.]/g, '');
  const numeric = parseFloat(numericStr) || 0;
  const counted = useCountUp(numeric);
  const prefix  = String(value).match(/^[^0-9]*/)?.[0] || '';
  const suffix   = String(value).match(/[^0-9]*$/)?.[0] || '';
  const display  = `${prefix}${counted.toLocaleString('pt-BR')}${suffix}`;

  return (
    <div style={{
      background: 'color-mix(in srgb,var(--sa-primary) 8%,transparent)',
      border: '1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)',
      borderRadius: 16, padding: '22px 22px 0',
      position: 'relative', overflow: 'hidden',
      minHeight: 148, display: 'flex', flexDirection: 'column',
    }}>
      <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:12, opacity:.75 }}>{label}</div>
      <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:34, fontWeight:800, color:'var(--sa-text1)', lineHeight:1, letterSpacing:'-1px' }}>{display}</div>
      {trend && (
        <div style={{ display:'flex', alignItems:'center', gap:4, marginTop:10, fontSize:12, fontWeight:600, color:positive?'#10b981':'#ef4444' }}>
          <Icon name={positive?'trendUp':'trendDown'} size={12} />{trend} vs. mês anterior
        </div>
      )}
      {/* Large bleeding icon */}
      <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.08, pointerEvents:'none' }}>
        <Icon name={icon} size={130} style={{ color:'var(--sa-primary)' }} />
      </div>
    </div>
  );
}

// ── KANBAN BOARD ─────────────────────────────────────────────
const KANBAN_COLS = [
  { id:'pending',    label:'Aguardando',  color:'#f59e0b', icon:'clock'    },
  { id:'confirmed',  label:'Confirmado',  color:'#6366f1', icon:'check'    },
  { id:'inprogress', label:'Em atendimento', color:'#0ea5e9', icon:'users' },
  { id:'done',       label:'Concluído',   color:'#10b981', icon:'sparkle'  },
  { id:'cancelled',  label:'Cancelado',   color:'#ef4444', icon:'x'        },
];

function KanbanCard({ appt, onStatusChange }) {
  const prof = SA_PROFESSIONALS.find(p => p.id === appt.professionalId);
  const col  = KANBAN_COLS.find(c => c.id === (appt.kanbanStatus||appt.status)) || KANBAN_COLS[0];
  return (
    <div style={{ background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:10, padding:'11px 12px', marginBottom:8, cursor:'pointer', transition:'box-shadow 160ms', borderLeft:`3px solid ${col.color}` }}
      onMouseEnter={e=>e.currentTarget.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'}
      onMouseLeave={e=>e.currentTarget.style.boxShadow='none'}>
      <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:6 }}>
        <div style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)', lineHeight:1.3 }}>{appt.clientName}</div>
        <span style={{ fontSize:10, fontWeight:700, color:col.color, background:`${col.color}12`, padding:'2px 7px', borderRadius:20 }}>{SA_FMT.time(appt.startHour, appt.startMin)}</span>
      </div>
      <div style={{ fontSize:12, color:'var(--sa-text3)', marginBottom:8 }}>{appt.serviceName}</div>
      <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center' }}>
        <div style={{ display:'flex', alignItems:'center', gap:6 }}>
          <Avt name={prof?.name||'?'} size={20} color={prof?.color||'#888'}/>
          <span style={{ fontSize:11, color:'var(--sa-text3)' }}>{prof?.name?.split(' ')[0]||'—'}</span>
        </div>
        {/* Move arrows */}
        <div style={{ display:'flex', gap:4 }}>
          {KANBAN_COLS.map((c,i) => c.id !== (appt.kanbanStatus||appt.status) && (
            <button key={c.id} onClick={e=>{e.stopPropagation();onStatusChange(appt.id,c.id);}}
              title={`Mover para ${c.label}`}
              style={{ width:18, height:18, borderRadius:4, border:`1px solid ${c.color}40`, background:`${c.color}10`, cursor:'pointer', display:'flex', alignItems:'center', justifyContent:'center' }}>
              <div style={{ width:6, height:6, borderRadius:'50%', background:c.color }}/>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}

function KanbanBoard({ appointments, onStatusChange }) {
  return (
    <div style={{ display:'grid', gridTemplateColumns:'repeat(5,1fr)', gap:12, overflowX:'auto', padding:'4px 2px' }}>
      {KANBAN_COLS.map(col => {
        const cards = appointments.filter(a => (a.kanbanStatus||a.status) === col.id ||
          (col.id==='inprogress' && a.kanbanStatus==='inprogress') ||
          (col.id==='done' && a.kanbanStatus==='done')
        );
        return (
          <div key={col.id} style={{ minWidth:180 }}>
            {/* Column header */}
            <div style={{ display:'flex', alignItems:'center', gap:7, padding:'8px 10px', background:`${col.color}10`, border:`1px solid ${col.color}25`, borderRadius:9, marginBottom:8 }}>
              <div style={{ width:8, height:8, borderRadius:'50%', background:col.color, flexShrink:0 }}/>
              <span style={{ fontSize:12, fontWeight:700, color:col.color }}>{col.label}</span>
              <span style={{ fontSize:11, fontWeight:700, color:col.color, marginLeft:'auto', background:`${col.color}15`, borderRadius:20, padding:'1px 7px' }}>{cards.length}</span>
            </div>
            {/* Drop zone */}
            <div style={{ minHeight:80, background:`${col.color}04`, borderRadius:9, padding:'2px 0', border:`1px dashed ${col.color}20` }}
              onDragOver={e=>e.preventDefault()}
              onDrop={e=>{e.preventDefault();const id=Number(e.dataTransfer.getData('apptId'));onStatusChange(id,col.id);}}>
              {cards.map(a => (
                <div key={a.id} draggable onDragStart={e=>e.dataTransfer.setData('apptId',String(a.id))} style={{ padding:'0 6px' }}>
                  <KanbanCard appt={a} onStatusChange={onStatusChange}/>
                </div>
              ))}
              {cards.length===0&&<div style={{ padding:'16px 8px', textAlign:'center', fontSize:11, color:`${col.color}60` }}>Nenhum aqui</div>}
            </div>
          </div>
        );
      })}
    </div>
  );
}
function Timeline({ appointments, onNavigate }) {
  if (!appointments.length) {
    return (
      <div style={{ padding: '48px 0', textAlign: 'center', color: 'var(--sa-text3)', fontSize: 14 }}>
        Nenhum agendamento próximo
      </div>
    );
  }

  // Group by date
  const groups = {};
  appointments.forEach(a => {
    if (!groups[a.date]) groups[a.date] = [];
    groups[a.date].push(a);
  });

  const STATUS_DOT = { confirmed: '#10b981', pending: '#f59e0b', cancelled: '#ef4444' };

  return (
    <div style={{ position: 'relative', paddingBottom: 8 }}>
      {/* Vertical rail */}
      <div style={{ position: 'absolute', left: 15, top: 8, bottom: 8, width: 2, background: 'linear-gradient(to bottom, var(--sa-secondary), var(--sa-border) 80%)', borderRadius: 1, opacity: .5 }} />

      {Object.entries(groups).map(([date, appts]) => (
        <div key={date} style={{ marginBottom: 20 }}>
          {/* Date label */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 10, paddingLeft: 36 }}>
            <span style={{ fontSize: 11, fontWeight: 700, color: date === SA_TODAY ? 'var(--sa-secondary)' : 'var(--sa-text3)', letterSpacing: '.8px', textTransform: 'uppercase' }}>
              {date === SA_TODAY ? '✦ Hoje' : `${SA_FMT.weekday(date)}, ${SA_FMT.short(date)}`}
            </span>
            <div style={{ flex: 1, height: 1, background: 'var(--sa-border)' }} />
          </div>

          {appts.map((appt, i) => {
            const dotCol = STATUS_DOT[appt.status] || '#999';
            const prof = SA_PROFESSIONALS.find(p => p.id === appt.professionalId);
            return (
              <div key={appt.id} style={{ display: 'flex', gap: 10, alignItems: 'flex-start', marginBottom: i < appts.length - 1 ? 10 : 0, paddingLeft: 6 }}>
                {/* Dot */}
                <div style={{ position: 'relative', flexShrink: 0, marginTop: 10, zIndex: 1 }}>
                  <div style={{ width: 20, height: 20, borderRadius: '50%', background: `${dotCol}18`, border: `2px solid ${dotCol}`, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <div style={{ width: 6, height: 6, borderRadius: '50%', background: dotCol }} />
                  </div>
                </div>
                {/* Card */}
                <div style={{ flex: 1, background: 'var(--sa-surface2)', borderRadius: 10, padding: '10px 12px', border: `1px solid var(--sa-border)`, borderLeft: `3px solid ${dotCol}`, transition: 'box-shadow 150ms' }}
                  onMouseEnter={e => e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,.08)'}
                  onMouseLeave={e => e.currentTarget.style.boxShadow = 'none'}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 8 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, flex: 1, minWidth: 0 }}>
                      {/* Client avatar */}
                      <Avt name={appt.clientName} size={28} color={dotCol} />
                      <div style={{ minWidth: 0 }}>
                        <div style={{ fontSize: 13, fontWeight: 700, color: 'var(--sa-text1)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{appt.clientName}</div>
                        <div style={{ fontSize: 11, color: 'var(--sa-text3)', marginTop: 1, display: 'flex', alignItems: 'center', gap: 4 }}>
                          {appt.serviceName}
                          <span style={{ opacity: .4 }}>·</span>
                          {/* Prof avatar */}
                          <Avt name={appt.professionalName} size={14} color={prof?.color || '#888'} />
                          <span>{appt.professionalName.split(' ')[0]}</span>
                        </div>
                      </div>
                    </div>
                    <div style={{ flexShrink: 0, textAlign: 'right' }}>
                      <div style={{ fontSize: 13, fontWeight: 800, color: 'var(--sa-secondary)', fontFamily: "var(--sa-font-heading,'Poppins',sans-serif)" }}>{SA_FMT.time(appt.startHour, appt.startMin)}</div>
                      <Badge status={appt.status} />
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      ))}

      {appointments.length >= 8 && (
        <div style={{ paddingLeft: 36, marginTop: 8 }}>
          <button onClick={() => onNavigate('calendar')} style={{ fontSize: 13, fontWeight: 600, color: 'var(--sa-secondary)', background: 'none', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 4 }}>
            Ver agenda completa <Icon name="chevR" size={13}/>
          </button>
        </div>
      )}
    </div>
  );
}

// ── DASHBOARD ─────────────────────────────────────────────────
function DashboardScreen({ appointments, onNavigate, onNewAppt }) {
  const [view, setView]           = React.useState('timeline');
  const [apptState, setApptState] = React.useState(appointments);
  React.useEffect(() => { setApptState(appointments); }, [appointments]);
  const handleStatusChange = (id, newStatus) => {
    setApptState(prev => prev.map(a => a.id === id ? { ...a, kanbanStatus: newStatus } : a));
    window.SA_TOAST(`Movido para ${KANBAN_COLS.find(c=>c.id===newStatus)?.label||newStatus}`, 'success');
  };

  const loading = useLoading(800);
  const today = SA_TODAY;

  const stats = useMemo(() => {
    const month = appointments.filter(a => a.date.startsWith('2026-06'));
    const todayA = appointments.filter(a => a.date === today);
    const confirmed  = month.filter(a => a.status === 'confirmed');
    const pending    = month.filter(a => a.status === 'pending');
    const cancelled  = month.filter(a => a.status === 'cancelled');
    const revenue    = confirmed.reduce((s, a) => s + a.price, 0);
    const rate       = month.length ? Math.round(confirmed.length / month.length * 100) : 0;
    return {
      total: month.length, revenue, clients: 9, rate,
      todayCount: todayA.length,
      todayRev: todayA.filter(a => a.status === 'confirmed').reduce((s, a) => s + a.price, 0),
      confirmed: confirmed.length, pending: pending.length, cancelled: cancelled.length,
    };
  }, [appointments]);

  const upcoming = apptState
    .filter(a => a.date >= today)
    .sort((a, b) => a.date.localeCompare(b.date) || (a.startHour * 60 + a.startMin) - (b.startHour * 60 + b.startMin))
    .slice(0, 9);

  // All upcoming + today for kanban (broader window)
  const kanbanAppts = apptState
    .filter(a => a.date >= today.slice(0,7) + '-01') // whole month
    .sort((a, b) => a.date.localeCompare(b.date) || (a.startHour*60+a.startMin)-(b.startHour*60+b.startMin));

  // Ensure kanban shows appointments even if no future ones — fallback to all
  const kanbanDisplay = kanbanAppts.length > 0 ? kanbanAppts : apptState;

  const donutSegs = [
    { label: 'Confirmados', pct: Math.round(stats.confirmed / Math.max(stats.total, 1) * 100), color: '#10b981', count: stats.confirmed },
    { label: 'Pendentes',   pct: Math.round(stats.pending   / Math.max(stats.total, 1) * 100), color: '#f59e0b', count: stats.pending   },
    { label: 'Cancelados',  pct: 100 - Math.round(stats.confirmed / Math.max(stats.total,1)*100) - Math.round(stats.pending / Math.max(stats.total,1)*100), color: '#ef4444', count: stats.cancelled },
  ];

  const CARDS = [
    { label: 'Agendamentos', value: stats.total,               trend: '+12%', positive: true,  icon: 'calendar', tint: '#6366f1' },
    { label: 'Receita (mês)', value: SA_FMT.currency(stats.revenue), trend: '+8%',  positive: true,  icon: 'dollar',   tint: '#d4a574' },
    { label: 'Novos Clientes', value: stats.clients,           trend: '+3',   positive: true,  icon: 'users',    tint: '#10b981' },
    { label: 'Taxa Confirmação', value: `${stats.rate}%`,      trend: '+2%',  positive: true,  icon: 'check',    tint: '#f59e0b' },
  ];

  return (
    <div style={{ flex: 1, padding: '0 0 36px' }}>
      <AppHeader
        title={`Olá, Maria ✦`}
        subtitle={`${SA_FMT.date(today)} — ${stats.todayCount} agendamentos hoje`}
        actions={<Btn onClick={onNewAppt} icon={<Icon name="plus" size={15}/>} size="md">Novo Agendamento</Btn>}
      />

      <div style={{ padding: '24px 32px 0' }}>
        {/* Stat cards — count-up animation */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16, marginBottom: 24 }}>
          {CARDS.map(c => <StatCard key={c.label} {...c} />)}
        </div>

        {/* View toggle + main content */}
        <div style={{ marginBottom: 16, display:'flex', alignItems:'center', justifyContent:'space-between' }}>
          <h3 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:16, fontWeight:600, color:'var(--sa-text1)', margin:0 }}>
            {view==='kanban' ? 'Kanban de Atendimentos' : 'Próximos Agendamentos'}
          </h3>
          <div style={{ display:'flex', gap:0, background:'var(--sa-surface2)', border:'1px solid var(--sa-border)', borderRadius:8, overflow:'hidden' }}>
            {[['timeline','Linha do Tempo','calendar'],['kanban','Kanban','sparkle']].map(([v,l,ic])=>(
              <button key={v} onClick={()=>setView(v)}
                style={{ display:'flex', alignItems:'center', gap:6, padding:'7px 14px', border:'none', borderRight:v==='timeline'?'1px solid var(--sa-border)':'none', background:view===v?'var(--sa-primary)':'transparent', color:view===v?'#fff':'var(--sa-text2)', fontSize:12, fontWeight:view===v?700:500, cursor:'pointer', fontFamily:'var(--sa-font-body)', transition:'all 150ms' }}>
                <Icon name={ic} size={13}/>{l}
              </button>
            ))}
          </div>
        </div>

        {/* KANBAN VIEW */}
        {view==='kanban' && (
          <div style={{ overflowX:'auto', paddingBottom:12 }}>
            <KanbanBoard appointments={kanbanDisplay} onStatusChange={handleStatusChange}/>
          </div>
        )}

        {/* TIMELINE VIEW + right column */}
        {view==='timeline' && (
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 360px', gap: 20 }}>

          {/* Timeline */}
          <Card style={{ padding: 0, overflow: 'hidden' }}>
            <div style={{ padding: '20px 20px 0', display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
              <div>
                <h3 style={{ fontFamily: "var(--sa-font-heading,'Poppins',sans-serif)", fontSize: 16, fontWeight: 600, color: 'var(--sa-text1)', margin: 0 }}>Próximos Agendamentos</h3>
                <p style={{ fontSize: 13, color: 'var(--sa-text3)', margin: '3px 0 0' }}>Linha do tempo</p>
              </div>
              <Btn variant="ghost" size="sm" onClick={() => onNavigate('calendar')} icon={<Icon name="calendar" size={14}/>}>Agenda</Btn>
            </div>
            <div style={{ padding: '0 16px 16px' }}>
              <Timeline appointments={upcoming} onNavigate={onNavigate} />
            </div>
          </Card>

          {/* Right column */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

            {/* Donut chart */}
            <Card style={{ padding: 22 }}>
              <h4 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 14, fontWeight: 600, color: 'var(--sa-text1)', margin: '0 0 20px', display: 'flex', alignItems: 'center', gap: 8 }}>
                <Icon name="chart" size={15} style={{ color: 'var(--sa-secondary)' }} />
                Status dos Agendamentos
              </h4>
              <DonutChart segments={donutSegs} total={stats.total} />
            </Card>

            {/* Today summary */}
            <Card style={{ padding: 20 }}>
              <h4 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 14, fontWeight: 600, color: 'var(--sa-text1)', margin: '0 0 14px' }}>Resumo de Hoje</h4>
              {[
                { label: 'Agendamentos', value: stats.todayCount, icon: 'calendar' },
                { label: 'Receita Prevista', value: SA_FMT.currency(stats.todayRev), icon: 'dollar' },
                { label: 'Confirmados', value: appointments.filter(a => a.date===today&&a.status==='confirmed').length, icon: 'check' },
                { label: 'Pendentes', value: appointments.filter(a => a.date===today&&a.status==='pending').length, icon: 'clock' },
              ].map((item, i, arr) => (
                <div key={item.label} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '9px 0', borderBottom: i<arr.length-1 ? '1px solid var(--sa-border)' : 'none' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <Icon name={item.icon} size={14} style={{ color: 'var(--sa-text3)' }} />
                    <span style={{ fontSize: 13, color: 'var(--sa-text2)' }}>{item.label}</span>
                  </div>
                  <span style={{ fontSize: 15, fontWeight: 800, color: 'var(--sa-text1)', fontFamily: "'Poppins',sans-serif" }}>{item.value}</span>
                </div>
              ))}
            </Card>

            {/* Prof mini bars */}
            <Card style={{ padding: 20 }}>
              <h4 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 14, fontWeight: 600, color: 'var(--sa-text1)', margin: '0 0 14px' }}>Por Profissional</h4>
              {SA_PROFESSIONALS.map(p => {
                const cnt = appointments.filter(a => a.professionalId === p.id && a.status !== 'cancelled').length;
                const max = 20;
                return (
                  <div key={p.id} style={{ marginBottom: 12 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 5 }}>
                      <span style={{ fontSize: 12, color: 'var(--sa-text2)', fontWeight: 500 }}>{p.name.split(' ')[0]}</span>
                      <span style={{ fontSize: 12, fontWeight: 700, color: 'var(--sa-text1)' }}>{cnt}</span>
                    </div>
                    <div style={{ height: 6, borderRadius: 3, background: 'var(--sa-surface2)', overflow: 'hidden' }}>
                      <div style={{ height: '100%', borderRadius: 3, background: p.color, width: `${Math.min(cnt / max * 100, 100)}%`, transition: 'width 800ms ease' }} />
                    </div>
                  </div>
                );
              })}
            </Card>

          </div>
        </div>
        )} {/* end timeline view */}
      </div>
    </div>
  );
}

Object.assign(window, { DashboardScreen });
