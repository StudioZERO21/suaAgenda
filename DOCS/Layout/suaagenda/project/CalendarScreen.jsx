// ============================================================
// suaAgenda.pro — Calendar: Day / Week / Month views
// ============================================================
const { useState, useEffect, useRef, useMemo, useCallback } = React;

const HOUR_H      = 56;   // px per hour (slightly smaller for 24h)
const START_H     = 0;
const END_H       = 24;
const BIZ_START   = 8;    // business start
const BIZ_END     = 20;   // business end
const HOURS       = Array.from({ length: 24 }, (_, i) => i);
const DAY_NAMES     = ['Seg','Ter','Qua','Qui','Sex','Sáb'];
const DAY_NAMES_FULL= ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
const MONTH_NAMES   = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

function timeToY(h, m=0)  { return (h + m/60) * HOUR_H; }
function yToSnapped(y) {
  const totalMins = Math.max(0, (y / HOUR_H) * 60);
  const snapped = Math.round(totalMins / 30) * 30;
  const h = Math.min(Math.max(Math.floor(snapped / 60), 0), 23);
  return { h, m: snapped % 60 };
}

const STATUS_STYLE = {
  confirmed: { border:'none',   opacity:1   },
  pending:   { border:'dashed', opacity:.85 },
  cancelled: { border:'solid',  opacity:.45 },
};

// ── APPOINTMENT BLOCK (for day/week) ──────────────────────────
function ApptBlock({ appt, isDragging, onMouseDown, onClick }) {
  const top    = timeToY(appt.startHour, appt.startMin);
  const height = Math.max((appt.duration / 60) * HOUR_H - 3, 22);
  const prof   = SA_PROFESSIONALS.find(p => p.id === appt.professionalId);
  const col    = prof?.color || 'var(--sa-primary)';
  const ss     = STATUS_STYLE[appt.status] || STATUS_STYLE.confirmed;
  const isLight= col === '#d4a574' || col === '#e6c299';

  return (
    <div
      onMouseDown={e => { e.preventDefault(); e.stopPropagation(); onMouseDown(e, appt); }}
      onClick={e    => { e.stopPropagation(); if (!isDragging) onClick(appt); }}
      style={{
        position:'absolute', left:3, right:3, top, height,
        background: isDragging ? `${col}30` : `${col}22`,
        borderLeft: `3px solid ${col}`,
        borderTop:    ss.border==='dashed' ? `1px dashed ${col}` : `1px solid ${col}40`,
        borderRight:  ss.border==='dashed' ? `1px dashed ${col}` : `1px solid ${col}40`,
        borderBottom: ss.border==='dashed' ? `1px dashed ${col}` : `1px solid ${col}40`,
        borderRadius:'0 6px 6px 0', opacity:ss.opacity,
        cursor: isDragging ? 'grabbing' : 'grab',
        overflow:'hidden', userSelect:'none',
        pointerEvents: isDragging ? 'none' : 'auto',
        transition: isDragging ? 'none' : 'box-shadow 150ms',
        zIndex: isDragging ? 1 : 2, padding:'3px 6px',
      }}
      onMouseEnter={e => { if (!isDragging) e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,.12)'; }}
      onMouseLeave={e => { e.currentTarget.style.boxShadow = 'none'; }}>
      <div style={{ fontSize:11, fontWeight:700, color:col, lineHeight:1.2, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' }}>{appt.serviceName}</div>
      {height > 28 && (
        <div style={{ display:'flex', alignItems:'center', gap:4, marginTop:1 }}>
          <Avt name={prof?.name||'?'} size={14} color={col}/>
          <div style={{ fontSize:10, color:isLight?'#5a4a2a':col, opacity:.85, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap', fontWeight:600 }}>{prof?.name?.split(' ')[0]||''}</div>
        </div>
      )}
      {height > 44 && <div style={{ fontSize:10, color:isLight?'#5a4a2a':col, opacity:.7, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap', marginTop:1 }}>{appt.clientName}</div>}
      {height > 60 && <div style={{ fontSize:10, color:'var(--sa-text3)', marginTop:1 }}>{SA_FMT.time(appt.startHour, appt.startMin)}</div>}
    </div>
  );
}

function GhostBlock({ appt, h, m }) {
  const top    = timeToY(h, m);
  const height = Math.max((appt.duration / 60) * HOUR_H - 3, 22);
  const prof   = SA_PROFESSIONALS.find(p => p.id === appt.professionalId);
  const col    = prof?.color || 'var(--sa-primary)';
  return (
    <div style={{ position:'absolute', left:3, right:3, top, height, border:`2px dashed ${col}`, borderRadius:6, background:`${col}12`, pointerEvents:'none', zIndex:10 }}>
      <div style={{ fontSize:11, fontWeight:700, color:col, padding:'3px 6px' }}>{SA_FMT.time(h, m)} · {appt.serviceName}</div>
    </div>
  );
}


// ── STICKY HEADER ROW (outside scroll) ────────────────────────
function TimeGridHeader({ days, profFilter }) {
  const isDay = days.length === 1;
  return (
    <div style={{ display:'flex', borderBottom:'2px solid var(--sa-border)', background:'var(--sa-surface)', flexShrink:0, zIndex:10 }}>
      {/* Time gutter */}
      <div style={{ width:61, flexShrink:0, borderRight:'1px solid var(--sa-border)', background:'var(--sa-surface2)', padding:'8px 0', display:'flex', alignItems:'center', justifyContent:'center' }}>
        <Icon name="clock" size={13} style={{ color:'var(--sa-text3)', opacity:.5 }}/>
      </div>
      {/* Day headers */}
      {days.map((day, di) => {
        const isToday = day === SA_TODAY;
        const d = new Date(day + 'T12:00:00');
        const dayName = isDay
          ? d.toLocaleDateString('pt-BR', { weekday:'long' })
          : ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'][(d.getDay()+6)%7];
        return (
          <div key={day} style={{ flex:1, borderRight: di<days.length-1?'1px solid var(--sa-border)':undefined, minWidth: isDay?0:80, padding:'8px 4px', display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', background: isToday?'color-mix(in srgb,var(--sa-secondary) 8%,transparent)':'var(--sa-surface2)' }}>
            <span style={{ fontSize:10, fontWeight:700, color:isToday?'var(--sa-secondary)':'var(--sa-text3)', letterSpacing:'.8px', textTransform:'uppercase' }}>{dayName}</span>
            <span style={{ fontSize:20, fontWeight:800, color:isToday?'var(--sa-secondary)':'var(--sa-text1)', fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", lineHeight:1.1 }}>{day.split('-')[2]}</span>
          </div>
        );
      })}
    </div>
  );
}

// ── TIME GRID (shared by day & week) ──────────────────────────
function TimeGrid({ days, appointments, drag, profFilter, onSlotClick, onMouseDown, onApptClick }) {
  const filtered = useMemo(() => appointments.filter(a => {
    if (!days.includes(a.date)) return false;
    if (profFilter !== 'all' && a.professionalId !== Number(profFilter)) return false;
    return true;
  }), [appointments, days, profFilter]);

  const isDay = days.length === 1;

  return (
    <div style={{ display:'flex', border:'1px solid var(--sa-border)', borderRadius:12, overflow:'hidden', background:'var(--sa-surface)', minWidth: isDay ? 'auto' : 760 }}>
      {/* Time column */}
      <div style={{ width:60, flexShrink:0, borderRight:'1px solid var(--sa-border)' }}>

        <div style={{ position:'relative', height:24 * HOUR_H }}>
          {HOURS.map(h => (
            <div key={h} style={{ position:'absolute', top:h*HOUR_H-9, left:0, right:0, textAlign:'right', paddingRight:10, fontSize:11,
              color: (h >= BIZ_START && h < BIZ_END) ? 'var(--sa-text3)' : 'var(--sa-text3)', fontWeight:500,
              opacity: (h >= BIZ_START && h < BIZ_END) ? 1 : 0.5,
            }}>{String(h).padStart(2,'0')}:00</div>
          ))}
        </div>
      </div>

      {/* Day columns */}
      {days.map((day, di) => {
        const isToday = day === SA_TODAY;
        const dayAppts = filtered.filter(a => a.date === day);
        const d = new Date(day + 'T12:00:00');
        const dayName = isDay
          ? d.toLocaleDateString('pt-BR', { weekday:'long' })
          : DAY_NAMES[di] || DAY_NAMES_FULL[d.getDay()];

        return (
          <div key={day} style={{ flex:1, borderRight: di < days.length-1 ? '1px solid var(--sa-border)' : undefined, minWidth: isDay ? 0 : 80 }}>


            {/* Time slots */}
            <div data-day={day} style={{ position:'relative', height:24*HOUR_H, cursor:'cell' }}
              onClick={e => { const rect=e.currentTarget.getBoundingClientRect(); const relY=e.clientY-rect.top; const {h,m}=yToSnapped(relY); onSlotClick(day,h,m); }}>
              {/* Off-hours shading */}
              <div style={{ position:'absolute', left:0, right:0, top:0, height:BIZ_START*HOUR_H, background:'var(--sa-surface2)', opacity:.6, pointerEvents:'none', zIndex:0 }}/>
              <div style={{ position:'absolute', left:0, right:0, top:BIZ_END*HOUR_H, bottom:0, background:'var(--sa-surface2)', opacity:.6, pointerEvents:'none', zIndex:0 }}/>
              {HOURS.map(h => (
                <React.Fragment key={h}>
                  <div style={{ position:'absolute', top:h*HOUR_H, left:0, right:0, borderTop:`1px solid ${h===BIZ_START||h===BIZ_END?'var(--sa-secondary)':'var(--sa-border)'}`, opacity:h===BIZ_START||h===BIZ_END?.4:1, pointerEvents:'none' }}/>
                  <div style={{ position:'absolute', top:h*HOUR_H+HOUR_H/2, left:0, right:0, borderTop:'1px dashed var(--sa-border)', opacity:.4, pointerEvents:'none' }}/>
                </React.Fragment>
              ))}
              {dayAppts.map(appt => (
                <ApptBlock key={appt.id} appt={appt}
                  isDragging={drag?.appt.id === appt.id}
                  onMouseDown={onMouseDown} onClick={onApptClick}/>
              ))}
              {drag && drag.ghostDay === day && <GhostBlock appt={drag.appt} h={drag.ghostH} m={drag.ghostM}/>}
            </div>
          </div>
        );
      })}
    </div>
  );
}

// ── MONTH VIEW ────────────────────────────────────────────────
function MonthView({ year, month, appointments, profFilter, onDayClick, onApptClick }) {
  // Build calendar grid (Mon-first weeks)
  const firstDay = new Date(year, month, 1);
  const lastDay  = new Date(year, month + 1, 0);
  const firstDow = (firstDay.getDay() + 6) % 7; // Mon=0
  const totalDays = lastDay.getDate();

  const cells = [];
  for (let i = 0; i < firstDow; i++) cells.push(null);
  for (let d = 1; d <= totalDays; d++) cells.push(d);
  while (cells.length % 7 !== 0) cells.push(null);

  const toDateStr = d => d ? `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}` : null;

  const filtered = useMemo(() => appointments.filter(a => {
    if (profFilter !== 'all' && a.professionalId !== Number(profFilter)) return false;
    const [ay, am] = a.date.split('-').map(Number);
    return ay === year && am === month + 1;
  }), [appointments, profFilter, year, month]);

  const weeks = [];
  for (let i = 0; i < cells.length; i += 7) weeks.push(cells.slice(i, i+7));

  return (
    <div style={{ border:'1px solid var(--sa-border)', borderRadius:12, overflow:'hidden', background:'var(--sa-surface)' }}>
      {/* Weekday headers */}
      <div style={{ display:'grid', gridTemplateColumns:'repeat(7,1fr)', borderBottom:'1px solid var(--sa-border)' }}>
        {['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'].map(d => (
          <div key={d} style={{ padding:'10px 0', textAlign:'center', fontSize:11, fontWeight:700, color:'var(--sa-text3)', letterSpacing:'.5px', textTransform:'uppercase', background:'var(--sa-surface2)' }}>{d}</div>
        ))}
      </div>

      {/* Weeks */}
      {weeks.map((week, wi) => (
        <div key={wi} style={{ display:'grid', gridTemplateColumns:'repeat(7,1fr)', borderBottom: wi < weeks.length-1 ? '1px solid var(--sa-border)' : 'none' }}>
          {week.map((day, di) => {
            const dateStr = toDateStr(day);
            const isToday = dateStr === SA_TODAY;
            const dayAppts = dateStr ? filtered.filter(a => a.date === dateStr) : [];
            const maxShow = 3;

            return (
              <div key={di}
                onClick={() => day && onDayClick(dateStr)}
                style={{
                  minHeight:100, padding:'6px', borderRight: di < 6 ? '1px solid var(--sa-border)' : 'none',
                  background: isToday ? `var(--sa-secondary)08` : day ? 'transparent' : 'var(--sa-surface2)',
                  cursor: day ? 'pointer' : 'default',
                  transition:'background 120ms',
                }}
                onMouseEnter={e => { if(day) e.currentTarget.style.background = 'var(--sa-surface2)'; }}
                onMouseLeave={e => { e.currentTarget.style.background = isToday ? `var(--sa-secondary)08` : day ? 'transparent' : 'var(--sa-surface2)'; }}>
                {/* Day number */}
                {day && (
                  <div style={{ display:'inline-flex', alignItems:'center', justifyContent:'center', width:26, height:26, borderRadius:'50%', background:isToday?'var(--sa-secondary)':'transparent', marginBottom:4 }}>
                    <span style={{ fontSize:13, fontWeight:isToday?700:500, color:isToday?'#fff':'var(--sa-text2)' }}>{day}</span>
                  </div>
                )}
                {/* Event pills */}
                {dayAppts.slice(0, maxShow).map(a => {
                  const prof = SA_PROFESSIONALS.find(p => p.id === a.professionalId);
                  const col  = prof?.color || 'var(--sa-primary)';
                  return (
                    <div key={a.id}
                      onClick={e => { e.stopPropagation(); onApptClick(a); }}
                      style={{ fontSize:10, fontWeight:600, color:col, background:`${col}18`, border:`1px solid ${col}30`, borderRadius:4, padding:'2px 5px', marginBottom:2, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap', cursor:'pointer', transition:'all 120ms' }}
                      onMouseEnter={e=>e.currentTarget.style.background=`${col}30`}
                      onMouseLeave={e=>e.currentTarget.style.background=`${col}18`}>
                      {SA_FMT.time(a.startHour,a.startMin)} {a.clientName.split(' ')[0]}
                    </div>
                  );
                })}
                {dayAppts.length > maxShow && (
                  <div style={{ fontSize:10, color:'var(--sa-text3)', fontWeight:600, padding:'1px 4px' }}>+{dayAppts.length - maxShow} mais</div>
                )}
              </div>
            );
          })}
        </div>
      ))}
    </div>
  );
}

// ── MAIN CALENDAR SCREEN ──────────────────────────────────────
function CalendarScreen({ appointments, setAppointments, onNewAppt }) {
  const [viewMode,   setViewMode]   = useState('week');
  const [curDate,    setCurDate]    = useState(SA_TODAY);
  const scrollRef = useRef(null);

  // Auto-scroll to business start — use setTimeout to wait for DOM
  useEffect(() => {
    const t = setTimeout(() => {
      if (scrollRef.current) {
        scrollRef.current.scrollTop = BIZ_START * HOUR_H - 8;
      }
    }, 80);
    return () => clearTimeout(t);
  }, [viewMode]);
  const [profFilter, setProfFilter] = useState('all');
  const [drag,       setDrag]       = useState(null);
  const [selAppt,    setSelAppt]    = useState(null);

  // ── Navigation ──────────────────────────────────────────────
  const navigate = (dir) => {
    const d = new Date(curDate + 'T12:00:00');
    if (viewMode === 'day')   d.setDate(d.getDate() + dir);
    if (viewMode === 'week')  d.setDate(d.getDate() + dir * 7);
    if (viewMode === 'month') d.setMonth(d.getMonth() + dir);
    setCurDate(d.toISOString().split('T')[0]);
  };

  // ── Derived date info ────────────────────────────────────────
  const d    = new Date(curDate + 'T12:00:00');
  const year = d.getFullYear();
  const month= d.getMonth();

  const weekDates = useMemo(() => SA_GET_WEEK(curDate).slice(0, 6), [curDate]); // Mon–Sat
  const dayDates  = [curDate];

  const headerTitle = useMemo(() => {
    if (viewMode === 'day')   return d.toLocaleDateString('pt-BR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    if (viewMode === 'week')  return `${SA_FMT.short(weekDates[0])} – ${SA_FMT.short(weekDates[5])}`;
    if (viewMode === 'month') return `${MONTH_NAMES[month]} ${year}`;
    return '';
  }, [viewMode, curDate]);

  // ── Drag handlers ────────────────────────────────────────────
  const handleApptMouseDown = useCallback((e, appt) => {
    if (viewMode === 'month') return;
    const rect = e.currentTarget.getBoundingClientRect();
    setDrag({ appt, offsetY: e.clientY - rect.top, ghostDay: appt.date, ghostH: appt.startHour, ghostM: appt.startMin, moved: false });
  }, [viewMode]);

  useEffect(() => {
    if (!drag) return;
    const onMove = (e) => {
      const el = document.elementFromPoint(e.clientX, e.clientY);
      const dayCol = el?.closest('[data-day]');
      if (!dayCol) return;
      const rect = dayCol.getBoundingClientRect();
      const { h, m } = yToSnapped(e.clientY - rect.top - drag.offsetY);
      setDrag(d => ({ ...d, ghostDay: dayCol.dataset.day, ghostH: h, ghostM: m, moved: true }));
    };
    const onUp = () => {
      if (drag.moved) {
        setAppointments(prev => prev.map(a =>
          a.id === drag.appt.id ? { ...a, date: drag.ghostDay, startHour: drag.ghostH, startMin: drag.ghostM } : a
        ));
        window.SA_TOAST(`Movido para ${SA_FMT.time(drag.ghostH, drag.ghostM)}`, 'success');
      }
      setDrag(null);
    };
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
    return () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); };
  }, [drag, setAppointments]);

  const handleSlotClick = (day, h, m) => onNewAppt({ date: day, hour: h, min: m });

  // Clicking a month day → switch to day view
  const handleMonthDayClick = (dateStr) => {
    setCurDate(dateStr);
    setViewMode('day');
  };

  return (
    <div style={{ flex:1, display:'flex', flexDirection:'column', height:'100%', overflow:'hidden', minHeight:0 }}>
      {/* ── HEADER ─────────────────────────────────────────── */}
      <div style={{ padding:'20px 32px 12px', flexShrink:0 }}>
        <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom:10 }}>
          {/* Title */}
          <div>
            <h1 style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:20, fontWeight:700, color:'var(--sa-text1)', margin:0, textTransform: viewMode==='day' ? 'capitalize' : 'none' }}>{headerTitle}</h1>
          </div>

          {/* Controls */}
          <div style={{ display:'flex', gap:8, alignItems:'center' }}>
            {/* View tabs */}
            <div style={{ display:'flex', background:'var(--sa-surface2)', border:'1.5px solid var(--sa-border)', borderRadius:8, overflow:'hidden' }}>
              {[['day','Dia'],['week','Semana'],['month','Mês']].map(([v,l], i, arr) => (
                <button key={v} onClick={() => setViewMode(v)} style={{
                  padding:'7px 16px', border:'none',
                  borderRight: i < arr.length-1 ? '1px solid var(--sa-border)' : 'none',
                  background: viewMode===v ? 'var(--sa-primary)' : 'transparent',
                  color: viewMode===v ? '#fff' : 'var(--sa-text2)',
                  cursor:'pointer', fontFamily:"var(--sa-font-body,'Inter',sans-serif)",
                  fontSize:13, fontWeight:viewMode===v?600:400, transition:'all 160ms',
                }}>{l}</button>
              ))}
            </div>

            {/* Prof filter */}
            <select value={profFilter} onChange={e => setProfFilter(e.target.value)}
              style={{ padding:'7px 11px', fontSize:13, border:'1.5px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', cursor:'pointer', fontFamily:"var(--sa-font-body)" }}>
              <option value="all">Todos</option>
              {SA_PROFESSIONALS.map(p => <option key={p.id} value={p.id}>{p.name.split(' ')[0]}</option>)}
            </select>

            {/* Today */}
            <Btn variant="muted" size="sm" onClick={() => { setCurDate(SA_TODAY); }}>Hoje</Btn>

            {/* Prev / Next */}
            <div style={{ display:'flex', border:'1.5px solid var(--sa-border)', borderRadius:8, overflow:'hidden' }}>
              <button onClick={() => navigate(-1)} style={{ padding:'7px 11px', background:'var(--sa-surface)', border:'none', cursor:'pointer', color:'var(--sa-text2)', display:'flex', alignItems:'center' }}>
                <Icon name="chevL" size={16}/>
              </button>
              <button onClick={() => navigate(+1)} style={{ padding:'7px 11px', background:'var(--sa-surface)', border:'none', borderLeft:'1.5px solid var(--sa-border)', cursor:'pointer', color:'var(--sa-text2)', display:'flex', alignItems:'center' }}>
                <Icon name="chevR" size={16}/>
              </button>
            </div>

            <Btn onClick={() => onNewAppt(null)} icon={<Icon name="plus" size={15}/>} size="sm">Novo</Btn>
          </div>
        </div>

        {/* Legend */}
        <div style={{ display:'flex', gap:14, alignItems:'center' }}>
          {SA_PROFESSIONALS.map(p => (
            <div key={p.id} style={{ display:'flex', alignItems:'center', gap:5 }}>
              <div style={{ width:9, height:9, borderRadius:'50%', background:p.color }}/>
              <span style={{ fontSize:12, color:'var(--sa-text3)', fontWeight:500 }}>{p.name}</span>
            </div>
          ))}
          {viewMode !== 'month' && (
            <span style={{ fontSize:11, color:'var(--sa-text3)', fontStyle:'italic', marginLeft:'auto' }}>Arraste para mover · Clique para detalhes</span>
          )}
          {viewMode === 'month' && (
            <span style={{ fontSize:11, color:'var(--sa-text3)', fontStyle:'italic', marginLeft:'auto' }}>Clique num dia para ver detalhes · Clique num evento para editar</span>
          )}
        </div>
      </div>

      {/* Sticky day/week header — outside scroll container */}
      {(viewMode==='day'||viewMode==='week') && (
        <div style={{ padding:'0 32px', flexShrink:0 }}>
          <TimeGridHeader days={viewMode==='day'?dayDates:weekDates} profFilter={profFilter}/>
        </div>
      )}

      {/* ── CALENDAR BODY ──────────────────────────────────── */}
      <div ref={scrollRef} style={{ flex:1, minHeight:0, overflow:'auto', padding:'0 32px 24px' }}>
        {/* DAY VIEW */}
        {viewMode === 'day' && (
          <TimeGrid
            days={dayDates} appointments={appointments}
            drag={drag} profFilter={profFilter}
            onSlotClick={handleSlotClick}
            onMouseDown={handleApptMouseDown}
            onApptClick={setSelAppt}
          />
        )}

        {/* WEEK VIEW */}
        {viewMode === 'week' && (
          <TimeGrid
            days={weekDates} appointments={appointments}
            drag={drag} profFilter={profFilter}
            onSlotClick={handleSlotClick}
            onMouseDown={handleApptMouseDown}
            onApptClick={setSelAppt}
          />
        )}

        {/* MONTH VIEW */}
        {viewMode === 'month' && (
          <MonthView
            year={year} month={month}
            appointments={appointments}
            profFilter={profFilter}
            onDayClick={handleMonthDayClick}
            onApptClick={setSelAppt}
          />
        )}
      </div>

      {/* ── APPOINTMENT DETAIL MODAL ────────────────────────── */}
      {selAppt && (
        <Modal open={!!selAppt} onClose={() => setSelAppt(null)} title="Detalhes do Agendamento"
          footer={<>
            <Btn variant="danger" size="sm" onClick={() => {
              setAppointments(p => p.filter(a => a.id !== selAppt.id));
              setSelAppt(null);
              window.SA_TOAST('Agendamento cancelado','error');
            }}>Cancelar</Btn>
            <Btn variant="secondary" size="sm" onClick={() => setSelAppt(null)}>Fechar</Btn>
            <Btn size="sm" onClick={() => {
              setAppointments(p => p.map(a => a.id===selAppt.id ? { ...a, status:'confirmed' } : a));
              setSelAppt(null);
              window.SA_TOAST('Agendamento confirmado!','success');
            }}>Confirmar</Btn>
          </>}>
          <div style={{ display:'flex', flexDirection:'column', gap:16 }}>
            <div style={{ display:'flex', alignItems:'center', gap:14 }}>
              <Avt name={selAppt.clientName} size={48} color={SA_PROFESSIONALS.find(p=>p.id===selAppt.professionalId)?.color}/>
              <div>
                <div style={{ fontSize:18, fontWeight:700, color:'var(--sa-text1)', fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)" }}>{selAppt.clientName}</div>
                <Badge status={selAppt.status}/>
              </div>
            </div>
            <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:10 }}>
              {[
                { l:'Serviço',      v:selAppt.serviceName        },
                { l:'Profissional', v:selAppt.professionalName   },
                { l:'Data',         v:SA_FMT.date(selAppt.date)  },
                { l:'Horário',      v:`${SA_FMT.time(selAppt.startHour,selAppt.startMin)} (${selAppt.duration}min)` },
                { l:'Valor',        v:SA_FMT.currency(selAppt.price) },
                { l:'Status',       v:selAppt.status             },
              ].map(({ l, v }) => (
                <div key={l} style={{ background:'var(--sa-surface2)', borderRadius:8, padding:'10px 14px' }}>
                  <div style={{ fontSize:11, color:'var(--sa-text3)', fontWeight:600, letterSpacing:'.4px', textTransform:'uppercase' }}>{l}</div>
                  <div style={{ fontSize:14, fontWeight:600, color:'var(--sa-text1)', marginTop:3 }}>{v}</div>
                </div>
              ))}
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
}

Object.assign(window, { CalendarScreen });
