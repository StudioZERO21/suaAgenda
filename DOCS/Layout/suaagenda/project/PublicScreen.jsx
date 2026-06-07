// ============================================================
// suaAgenda.pro — Public Booking Page v2 (Professional)
// ============================================================
const { useState, useRef } = React;

// ── LOGO MARK ────────────────────────────────────────────────
function LogoMark({ size = 38 }) {
  return (
    <svg width={size} height={size} viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect width="38" height="38" rx="10" fill="var(--sa-secondary)" />
      {/* Diamond accent */}
      <rect x="19" y="6" width="9" height="9" rx="2" transform="rotate(45 19 6)" fill="rgba(255,255,255,.25)" />
      {/* Scissors blade 1 */}
      <circle cx="13" cy="14" r="3.5" stroke="white" strokeWidth="1.8" fill="none" />
      <circle cx="25" cy="24" r="3.5" stroke="white" strokeWidth="1.8" fill="none" />
      <line x1="16" y1="16.5" x2="22" y2="21.5" stroke="white" strokeWidth="1.8" strokeLinecap="round" />
      <line x1="22" y1="16.5" x2="16" y2="21.5" stroke="white" strokeWidth="1.8" strokeLinecap="round" />
    </svg>
  );
}

// ── IMAGE PLACEHOLDER ────────────────────────────────────────
function ImgSlot({ label, h = 220, radius = 12, style = {}, overlay = false }) {
  const uid = label.replace(/\s+/g, '-');
  return (
    <div style={{ width: '100%', height: h, borderRadius: radius, overflow: 'hidden', position: 'relative', background: 'var(--sa-surface2)', border: '1px solid var(--sa-border)', ...style }}>
      <svg width="100%" height="100%" style={{ position: 'absolute', inset: 0 }}>
        <defs>
          <pattern id={`sp-${uid}`} patternUnits="userSpaceOnUse" width="24" height="24" patternTransform="rotate(45)">
            <rect width="24" height="24" fill="var(--sa-surface2)" />
            <rect width="12" height="24" fill="rgba(0,0,0,.025)" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill={`url(#sp-${uid})`} />
      </svg>
      {overlay && <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(to top, rgba(0,0,0,.7) 0%, rgba(0,0,0,.2) 50%, transparent 100%)' }} />}
      <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%,-50%)', textAlign: 'center', pointerEvents: 'none', opacity: overlay ? 0 : 1 }}>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" style={{ marginBottom: 6, display: 'block', margin: '0 auto 6px' }}>
          <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
        </svg>
        <div style={{ fontFamily: 'monospace', fontSize: 10, color: 'var(--sa-text3)', letterSpacing: '.5px' }}>{label}</div>
      </div>
    </div>
  );
}

// ── PROF AVATAR PLACEHOLDER ──────────────────────────────────
function ProfPhoto({ prof, size = 100 }) {
  const uid = `prof-${prof.id}`;
  return (
    <div style={{ width: size, height: size, borderRadius: '50%', overflow: 'hidden', position: 'relative', border: '3px solid var(--sa-border)', background: 'var(--sa-surface2)', flexShrink: 0 }}>
      <svg width="100%" height="100%" style={{ position: 'absolute', inset: 0 }}>
        <defs>
          <pattern id={uid} patternUnits="userSpaceOnUse" width="16" height="16" patternTransform="rotate(45)">
            <rect width="16" height="16" fill="var(--sa-surface2)" />
            <rect width="8" height="16" fill="rgba(0,0,0,.03)" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill={`url(#${uid})`} />
      </svg>
      <div style={{ position: 'absolute', inset: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" strokeWidth="1.5">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        <div style={{ fontFamily: 'monospace', fontSize: 8, color: 'var(--sa-text3)', marginTop: 2 }}>foto</div>
      </div>
    </div>
  );
}

// ── QUICK BOOK MODAL ─────────────────────────────────────────
function QuickBookModal({ open, onClose, prefill }) {
  const [step, setStep] = useState(1);
  const [form, setForm] = useState({ service: '', prof: '', date: '', time: '', name: '', phone: '' });

  // Pre-fill when opened from availability slots
  React.useEffect(() => {
    if (open && prefill) {
      setForm(f => ({
        ...f,
        prof: String(prefill.profId || ''),
        date: prefill.date || '',
        time: prefill.hour != null ? SA_FMT.time(prefill.hour, prefill.min || 0) : '',
      }));
      setStep(prefill.profId ? 2 : 1);
    } else if (!open) {
      setStep(1);
      setForm({ service: '', prof: '', date: '', time: '', name: '', phone: '' });
    }
  }, [open]);
  const [loading, setLoading] = useState(false);
  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  if (!open) return null;

  const confirm = () => { setLoading(true); setTimeout(() => { setLoading(false); setStep(3); }, 1100); };

  return (
    <Modal open={open} onClose={onClose} title={step === 3 ? '✓ Confirmado!' : 'Agendar Horário'} size="sm"
      footer={step < 3 ? <>
        {step === 2 && <Btn variant="ghost" size="sm" onClick={() => setStep(1)}>Voltar</Btn>}
        <Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn>
        {step === 1
          ? <Btn size="sm" disabled={!form.service || !form.prof} onClick={() => setStep(2)}>Continuar</Btn>
          : <Btn size="sm" loading={loading} onClick={confirm}>Confirmar</Btn>}
      </> : <Btn fullWidth onClick={onClose}>Fechar</Btn>}>
      {step === 1 && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
          <Sel label="Serviço" value={form.service} onChange={e => set('service', e.target.value)} placeholder="Selecionar serviço..."
            options={SA_SERVICES.map(s => ({ value: String(s.id), label: `${s.name} — ${SA_FMT.currency(s.price)}` }))} />
          <Sel label="Profissional" value={form.prof} onChange={e => set('prof', e.target.value)} placeholder="Selecionar profissional..."
            options={SA_PROFESSIONALS.map(p => ({ value: String(p.id), label: `${p.name} · ${p.role}` }))} />
        </div>
      )}
      {step === 2 && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
          <div style={{ background: 'var(--sa-surface2)', borderRadius: 8, padding: '10px 14px', fontSize: 13, color: 'var(--sa-text2)' }}>
            {SA_SERVICES.find(s => String(s.id) === form.service)?.name} · {SA_PROFESSIONALS.find(p => String(p.id) === form.prof)?.name}
          </div>
          <Inp label="Seu nome" value={form.name} onChange={e => set('name', e.target.value)} placeholder="Nome completo" required icon={<Icon name="user" size={14}/>} />
          <Inp label="WhatsApp" value={form.phone} onChange={e => set('phone', e.target.value)} placeholder="(11) 99999-0000" required icon={<Icon name="phone" size={14}/>} />
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
            <Inp label="Data" value={form.date} onChange={e => set('date', e.target.value)} type="date" required />
            <Sel label="Horário" value={form.time} onChange={e => set('time', e.target.value)} placeholder="Escolher..."
              options={['08:00','09:00','10:00','11:00','13:00','14:00','15:00','16:00','17:00'].map(t => ({ value: t, label: t }))} />
          </div>
          <label style={{ display: 'flex', gap: 8, fontSize: 12, color: 'var(--sa-text3)', cursor: 'pointer', alignItems: 'flex-start' }}>
            <input type="checkbox" required style={{ marginTop: 1, accentColor: 'var(--sa-primary)' }} />
            Concordo com os termos e autorizo o contato via WhatsApp
          </label>
        </div>
      )}
      {step === 3 && (
        <div style={{ textAlign: 'center', padding: '16px 0' }}>
          <div style={{ width: 60, height: 60, borderRadius: '50%', background: 'rgba(16,185,129,.12)', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 18px' }}>
            <Icon name="check" size={26} style={{ color: '#10b981' }} />
          </div>
          <p style={{ fontSize: 15, color: 'var(--sa-text1)', fontWeight: 600, margin: '0 0 8px', fontFamily: "'Poppins',sans-serif" }}>Agendamento Confirmado!</p>
          <p style={{ fontSize: 13, color: 'var(--sa-text3)', lineHeight: 1.7, margin: 0 }}>Você receberá a confirmação no WhatsApp em breve. Até logo!</p>
        </div>
      )}
    </Modal>
  );
}

// ── PROF AVAILABILITY MODAL ────────────────────────────────
function ProfDetailModal({ prof, open, onClose, onBook }) {
  const [selDay, setSelDay] = React.useState(0);

  const days = Array.from({ length: 6 }, (_, i) => {
    const d = new Date('2026-06-06T12:00:00');
    d.setDate(d.getDate() + i);
    return d.toISOString().split('T')[0];
  });

  const ALL_SLOTS = [];
  for (let h = 8; h < 19; h++) for (let m = 0; m < 60; m += 30) ALL_SLOTS.push({ h, m });

  const currentDay = days[selDay];
  const slots = React.useMemo(() => {
    if (!prof) return [];
    const appts = SA_APPOINTMENTS.filter(a =>
      a.date === currentDay && a.professionalId === prof.id && a.status !== 'cancelled'
    );
    return ALL_SLOTS.map(slot => {
      const sm = slot.h * 60 + slot.m;
      const busy = appts.some(a => { const s = a.startHour * 60 + a.startMin; return sm >= s && sm < s + a.duration; });
      return { ...slot, available: !busy };
    });
  }, [prof?.id, currentDay]);

  const freeQt = slots.filter(s => s.available).length;
  if (!prof) return null;

  return (
    <Modal open={open} onClose={onClose} title={prof.name} subtitle={`${prof.role} · Escolha um horário`} size="md">
      <div>
        {/* Day tabs */}
        <div style={{ display: 'flex', gap: 6, marginBottom: 20, overflowX: 'auto', paddingBottom: 2 }}>
          {days.map((day, i) => {
            const active = selDay === i;
            const isSun = new Date(day + 'T12:00:00').getDay() === 0;
            return (
              <button key={day} onClick={() => !isSun && setSelDay(i)} disabled={isSun}
                style={{
                  padding: '8px 14px', borderRadius: 10, minWidth: 68, flexShrink: 0,
                  border: `1.5px solid ${active ? 'var(--sa-secondary)' : 'var(--sa-border)'}`,
                  background: active ? 'var(--sa-secondary)' : isSun ? 'var(--sa-surface2)' : 'var(--sa-surface)',
                  color: active ? '#fff' : isSun ? 'var(--sa-text3)' : 'var(--sa-text1)',
                  cursor: isSun ? 'not-allowed' : 'pointer', fontFamily: "'Inter',sans-serif",
                  transition: 'all 180ms', textAlign: 'center', opacity: isSun ? .35 : 1,
                }}>
                <div style={{ fontSize: 10, fontWeight: 700, letterSpacing: '.8px', textTransform: 'uppercase', opacity: .65, marginBottom: 2 }}>{i === 0 ? 'Hoje' : SA_FMT.weekday(day)}</div>
                <div style={{ fontSize: 18, fontWeight: 700, fontFamily: "'Poppins',sans-serif", lineHeight: 1 }}>{day.split('-')[2]}</div>
              </button>
            );
          })}
        </div>

        {/* Counter */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 14, padding: '8px 12px', borderRadius: 8, background: freeQt > 0 ? 'rgba(16,185,129,.08)' : 'rgba(239,68,68,.06)', border: `1px solid ${freeQt > 0 ? 'rgba(16,185,129,.2)' : 'rgba(239,68,68,.15)'}` }}>
          <span style={{ width: 7, height: 7, borderRadius: '50%', background: freeQt > 0 ? '#10b981' : '#ef4444', flexShrink: 0 }} />
          <span style={{ fontSize: 13, fontWeight: 600, color: freeQt > 0 ? '#059669' : '#dc2626' }}>
            {freeQt > 0 ? `${freeQt} horários disponíveis` : 'Nenhum horário disponível neste dia'}
          </span>
        </div>

        {/* Slots */}
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
          {slots.map(slot => {
            const label = SA_FMT.time(slot.h, slot.m);
            return (
              <button key={label}
                onClick={() => { if (slot.available) { onClose(); onBook({ profId: prof.id, date: currentDay, hour: slot.h, min: slot.m }); } }}
                disabled={!slot.available}
                style={{
                  padding: '9px 16px', borderRadius: 9,
                  border: `1.5px solid ${slot.available ? `${prof.color}55` : 'var(--sa-border)'}`,
                  background: slot.available ? `${prof.color}12` : 'var(--sa-surface2)',
                  color: slot.available ? prof.color : 'var(--sa-text3)',
                  fontSize: 13, fontWeight: slot.available ? 700 : 400,
                  cursor: slot.available ? 'pointer' : 'not-allowed',
                  fontFamily: "'Inter',sans-serif", transition: 'all 150ms',
                  opacity: slot.available ? 1 : 0.35,
                  textDecoration: slot.available ? 'none' : 'line-through',
                }}
                onMouseEnter={e => { if (slot.available) e.currentTarget.style.background = `${prof.color}28`; }}
                onMouseLeave={e => { e.currentTarget.style.background = slot.available ? `${prof.color}12` : 'var(--sa-surface2)'; }}>
                {label}
              </button>
            );
          })}
        </div>

        {/* Legend */}
        <div style={{ display: 'flex', gap: 16, marginTop: 18, paddingTop: 14, borderTop: '1px solid var(--sa-border)' }}>
          {[['#10b981', 'Disponível — clique para agendar'], ['#aaa', 'Ocupado']].map(([col, lbl]) => (
            <div key={lbl} style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
              <div style={{ width: 9, height: 9, borderRadius: 2, background: col }} />
              <span style={{ fontSize: 12, color: 'var(--sa-text3)' }}>{lbl}</span>
            </div>
          ))}
        </div>
      </div>
    </Modal>
  );
}

// (legacy standalone section — kept as dead code, not rendered)
function AvailabilitySection({ onBook }) {
  const [selDay, setSelDay] = useState(0);

  const days = Array.from({ length: 6 }, (_, i) => {
    const d = new Date('2026-06-06T12:00:00');
    d.setDate(d.getDate() + i);
    return d.toISOString().split('T')[0];
  });

  const currentDay = days[selDay];

  // All 30-min slots 8:00–19:00
  const ALL_SLOTS = [];
  for (let h = 8; h < 19; h++) {
    for (let m = 0; m < 60; m += 30) ALL_SLOTS.push({ h, m });
  }

  const getSlots = (profId, dateStr) => {
    const appts = SA_APPOINTMENTS.filter(a =>
      a.date === dateStr && a.professionalId === profId && a.status !== 'cancelled'
    );
    return ALL_SLOTS.map(slot => {
      const sm = slot.h * 60 + slot.m;
      const busy = appts.some(a => {
        const start = a.startHour * 60 + a.startMin;
        return sm >= start && sm < start + a.duration;
      });
      return { ...slot, available: !busy };
    });
  };

  return (
    <div style={{ marginBottom: 80 }}>
      {/* Section header */}
      <div style={{ textAlign: 'center', marginBottom: 40 }}>
        <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--sa-secondary)', letterSpacing: '2px', textTransform: 'uppercase', marginBottom: 12 }}>Consulte em tempo real</p>
        <h2 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 36, fontWeight: 700, color: 'var(--sa-text1)', margin: '0 0 12px' }}>Horários Disponíveis</h2>
        <p style={{ fontSize: 15, color: 'var(--sa-text3)', margin: 0 }}>Selecione um dia e clique no horário desejado para agendar</p>
      </div>

      {/* Day tabs */}
      <div style={{ display: 'flex', gap: 8, marginBottom: 28, overflowX: 'auto', paddingBottom: 4 }}>
        {days.map((day, i) => {
          const active = selDay === i;
          const d = new Date(day + 'T12:00:00');
          const isSun = d.getDay() === 0;
          return (
            <button key={day} onClick={() => !isSun && setSelDay(i)} disabled={isSun}
              style={{
                padding: '10px 16px', borderRadius: 12, minWidth: 78, flexShrink: 0,
                border: `1.5px solid ${active ? 'var(--sa-secondary)' : 'var(--sa-border)'}`,
                background: active ? 'var(--sa-secondary)' : isSun ? 'var(--sa-surface2)' : 'var(--sa-surface)',
                color: active ? '#fff' : isSun ? 'var(--sa-text3)' : 'var(--sa-text1)',
                cursor: isSun ? 'not-allowed' : 'pointer',
                fontFamily: "'Inter',sans-serif", transition: 'all 180ms ease',
                opacity: isSun ? .4 : 1, textAlign: 'center',
              }}>
              <div style={{ fontSize: 10, fontWeight: 700, letterSpacing: '1px', textTransform: 'uppercase', opacity: .7, marginBottom: 3 }}>
                {i === 0 ? 'Hoje' : SA_FMT.weekday(day)}
              </div>
              <div style={{ fontSize: 20, fontWeight: 700, fontFamily: "'Poppins',sans-serif", lineHeight: 1 }}>{day.split('-')[2]}</div>
              <div style={{ fontSize: 10, opacity: .6, marginTop: 2 }}>{SA_FMT.month(day).slice(0,3)}</div>
            </button>
          );
        })}
      </div>

      {/* Slots per professional */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        {SA_PROFESSIONALS.map(prof => {
          const slots  = getSlots(prof.id, currentDay);
          const freeQt = slots.filter(s => s.available).length;
          return (
            <Card key={prof.id} style={{ padding: '20px 24px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 14, marginBottom: 16 }}>
                <Avt name={prof.name} size={42} color={prof.color} />
                <div style={{ flex: 1 }}>
                  <div style={{ fontSize: 15, fontWeight: 700, color: 'var(--sa-text1)', fontFamily: "'Poppins',sans-serif" }}>{prof.name}</div>
                  <div style={{ fontSize: 12, color: 'var(--sa-text3)', marginTop: 1 }}>{prof.role}</div>
                </div>
                <div style={{
                  display: 'flex', alignItems: 'center', gap: 6,
                  padding: '5px 12px', borderRadius: 20,
                  background: freeQt > 0 ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.08)',
                  border: `1px solid ${freeQt > 0 ? 'rgba(16,185,129,.25)' : 'rgba(239,68,68,.2)'}`,
                }}>
                  <span style={{ width: 6, height: 6, borderRadius: '50%', background: freeQt > 0 ? '#10b981' : '#ef4444', flexShrink: 0 }} />
                  <span style={{ fontSize: 12, fontWeight: 600, color: freeQt > 0 ? '#059669' : '#dc2626' }}>
                    {freeQt > 0 ? `${freeQt} livres` : 'Indisponível'}
                  </span>
                </div>
              </div>

              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 7 }}>
                {slots.map(slot => {
                  const label = SA_FMT.time(slot.h, slot.m);
                  return (
                    <button key={label}
                      onClick={() => slot.available && onBook({ profId: prof.id, date: currentDay, hour: slot.h, min: slot.m })}
                      disabled={!slot.available}
                      title={slot.available ? `Agendar ${label} com ${prof.name}` : 'Horário ocupado'}
                      style={{
                        padding: '7px 13px', borderRadius: 9,
                        border: `1.5px solid ${slot.available ? `${prof.color}50` : 'var(--sa-border)'}`,
                        background: slot.available ? `${prof.color}10` : 'var(--sa-surface2)',
                        color: slot.available ? prof.color : 'var(--sa-text3)',
                        fontSize: 12, fontWeight: slot.available ? 700 : 400,
                        cursor: slot.available ? 'pointer' : 'not-allowed',
                        fontFamily: "'Inter',sans-serif", transition: 'all 150ms',
                        opacity: slot.available ? 1 : 0.4,
                        textDecoration: slot.available ? 'none' : 'line-through',
                        position: 'relative',
                      }}
                      onMouseEnter={e => { if (slot.available) { e.currentTarget.style.background = `${prof.color}22`; e.currentTarget.style.transform = 'scale(1.05)'; }}}
                      onMouseLeave={e => { e.currentTarget.style.background = slot.available ? `${prof.color}10` : 'var(--sa-surface2)'; e.currentTarget.style.transform = 'scale(1)'; }}>
                      {label}
                    </button>
                  );
                })}
              </div>
            </Card>
          );
        })}
      </div>

      {/* Legend */}
      <div style={{ display: 'flex', gap: 20, marginTop: 16, justifyContent: 'center' }}>
        {[
          { color: '#10b981', label: 'Disponível — clique para agendar' },
          { color: 'var(--sa-text3)', label: 'Ocupado' },
        ].map(({ color, label }) => (
          <div key={label} style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
            <div style={{ width: 10, height: 10, borderRadius: 3, background: color, opacity: color === 'var(--sa-text3)' ? .4 : 1 }} />
            <span style={{ fontSize: 12, color: 'var(--sa-text3)' }}>{label}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

// ── MAIN PUBLIC SCREEN ───────────────────────────────────────
function PublicScreen({ onBack }) {
  const [book, setBook] = useState(false);
  const [selProf, setSelProf] = useState(null);

  const STATS = [
    { n: '8+',    l: 'Anos de experiência' },
    { n: '2.400', l: 'Clientes atendidos'  },
    { n: '4.9★',  l: 'Avaliação média'     },
    { n: '98%',   l: 'Satisfação'          },
  ];

  const WORKS = [
    'Corte degradê',  'Barba completa', 'Coloração',
    'Corte navalhado','Hidratação',     'Sobrancelha',
  ];

  const PRODETAILS = [
    { ...SA_PROFESSIONALS[0], bio: 'Especialista em cortes clássicos e degradês. Mais de 10 anos de experiência em barbearia de alto padrão.', specs: ['Degradê', 'Corte clássico', 'Navalha'] },
    { ...SA_PROFESSIONALS[1], bio: 'Mestre em barbas estilizadas e acabamentos perfeitos. Referência em cuidados masculinos modernos.', specs: ['Barba', 'Bigode', 'Acabamentos'] },
    { ...SA_PROFESSIONALS[2], bio: 'Colorista com formação internacional. Especializada em colorações modernas e tratamentos capilares.', specs: ['Coloração', 'Mechas', 'Tratamentos'] },
  ];

  const TESTIMONIALS = [
    { name: 'Miguel Santos',  svc: 'Corte + Barba',  text: 'Melhor barbearia da cidade, sem dúvidas. João é um artista — saí completamente transformado.', stars: 5 },
    { name: 'Bruno Lima',     svc: 'Barba completa', text: 'Carlos tem um talento incrível para barbas. Atendimento impecável e resultado perfeito.', stars: 5 },
    { name: 'Rodrigo Alves',  svc: 'Coloração',      text: 'Ana arrasou na coloração! Ambiente sofisticado, atendimento excelente. Super recomendo!', stars: 5 },
  ];

  return (
    <div style={{ minHeight: '100vh', background: 'var(--sa-bg)', fontFamily: "'Inter',sans-serif" }}>

      {/* ── NAVBAR ────────────────────────────────────────── */}
      <nav style={{ background: 'rgba(10,10,10,.96)', backdropFilter: 'blur(12px)', borderBottom: '1px solid rgba(255,255,255,.06)', padding: '0 48px', display: 'flex', alignItems: 'center', height: 68, position: 'sticky', top: 0, zIndex: 100 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, flex: 1 }}>
          <LogoMark size={38} />
          <div>
            <div style={{ fontFamily: "'Poppins',sans-serif", fontSize: 17, fontWeight: 700, color: '#fff', letterSpacing: '-.3px' }}>Barbearia Style</div>
            <div style={{ fontSize: 10, color: 'var(--sa-secondary)', fontWeight: 600, letterSpacing: '1.5px', textTransform: 'uppercase', marginTop: -1 }}>Premium Barber Shop</div>
          </div>
        </div>
        <div style={{ display: 'flex', gap: 32, alignItems: 'center' }}>
          {['Serviços', 'Portfólio', 'Equipe', 'Contato'].map(l => (
            <span key={l} style={{ fontSize: 13, color: 'rgba(255,255,255,.55)', cursor: 'pointer', fontWeight: 500, letterSpacing: '.2px', transition: 'color 150ms' }}
              onMouseEnter={e => e.target.style.color = '#fff'} onMouseLeave={e => e.target.style.color = 'rgba(255,255,255,.55)'}>{l}</span>
          ))}
          <Btn size="sm" onClick={() => setBook(true)} style={{ marginLeft: 8 }}>Agendar Agora</Btn>
          <span onClick={onBack} style={{ fontSize: 12, color: 'rgba(255,255,255,.3)', cursor: 'pointer', marginLeft: 4 }}>← Painel</span>
        </div>
      </nav>

      {/* ── HERO ──────────────────────────────────────────── */}
      <div style={{ position: 'relative', height: 580, overflow: 'hidden' }}>
        {/* Banner placeholder */}
        <div style={{ position: 'absolute', inset: 0, background: '#111' }}>
          <svg width="100%" height="100%" style={{ position: 'absolute', inset: 0, opacity: .4 }}>
            <defs>
              <pattern id="hero-stripe" patternUnits="userSpaceOnUse" width="40" height="40" patternTransform="rotate(15)">
                <rect width="40" height="40" fill="#1a1a1a"/>
                <rect width="20" height="40" fill="rgba(255,255,255,.03)"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hero-stripe)"/>
          </svg>
          <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%,-50%)', textAlign: 'center', opacity: .2 }}>
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="1.2" style={{ margin: '0 auto 8px', display: 'block' }}>
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
            </svg>
            <div style={{ fontFamily: 'monospace', fontSize: 11, color: 'white', letterSpacing: '.5px' }}>banner-principal.jpg</div>
          </div>
        </div>
        {/* Gradient overlay */}
        <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(to right, rgba(0,0,0,.85) 40%, rgba(0,0,0,.3) 100%)' }} />
        {/* Hero content */}
        <div style={{ position: 'relative', height: '100%', display: 'flex', flexDirection: 'column', justifyContent: 'center', padding: '0 80px', maxWidth: 680 }}>
          <div style={{ display: 'inline-flex', alignItems: 'center', gap: 8, background: 'rgba(255,255,255,.1)', borderRadius: 20, padding: '5px 14px', marginBottom: 24, width: 'fit-content', border: '1px solid rgba(255,255,255,.1)' }}>
            <span style={{ fontSize: 11, fontWeight: 600, color: 'var(--sa-secondary)', letterSpacing: '1.5px', textTransform: 'uppercase' }}>Jardim Paulista · São Paulo</span>
          </div>
          <h1 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 56, fontWeight: 800, color: '#fff', lineHeight: 1.05, margin: '0 0 20px', letterSpacing: '-1px' }}>
            Arte em cada<br /><span style={{ color: 'var(--sa-secondary)' }}>detalhe.</span>
          </h1>
          <p style={{ fontSize: 17, color: 'rgba(255,255,255,.65)', margin: '0 0 36px', lineHeight: 1.7, maxWidth: 440 }}>
            Barbearia premium com os melhores profissionais. Experiência única desde 2018.
          </p>
          <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
            <Btn size="lg" onClick={() => setBook(true)}>Agendar Horário</Btn>
            <button style={{ display: 'flex', alignItems: 'center', gap: 8, background: 'transparent', border: '1.5px solid rgba(255,255,255,.25)', borderRadius: 9, padding: '12px 24px', cursor: 'pointer', color: '#fff', fontSize: 15, fontWeight: 600, fontFamily: "'Inter',sans-serif" }}>
              <Icon name="phone" size={16} style={{ color: 'var(--sa-secondary)' }} />
              (11) 99999-0000
            </button>
          </div>
        </div>
      </div>

      {/* ── STATS BAR ─────────────────────────────────────── */}
      <div style={{ background: 'var(--sa-primary)', padding: '0 80px' }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', maxWidth: 960, margin: '0 auto' }}>
          {STATS.map((s, i) => (
            <div key={i} style={{ padding: '28px 0', textAlign: 'center', borderRight: i < 3 ? '1px solid rgba(255,255,255,.1)' : 'none' }}>
              <div style={{ fontFamily: "'Poppins',sans-serif", fontSize: 32, fontWeight: 800, color: 'var(--sa-secondary)', lineHeight: 1 }}>{s.n}</div>
              <div style={{ fontSize: 12, color: 'rgba(255,255,255,.5)', marginTop: 6, fontWeight: 500, letterSpacing: '.3px' }}>{s.l}</div>
            </div>
          ))}
        </div>
      </div>

      <div style={{ maxWidth: 1140, margin: '0 auto', padding: '72px 48px' }}>

        {/* ── SERVICES ──────────────────────────────────── */}
        <div style={{ marginBottom: 80 }}>
          <div style={{ textAlign: 'center', marginBottom: 48 }}>
            <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--sa-secondary)', letterSpacing: '2px', textTransform: 'uppercase', marginBottom: 12 }}>O que oferecemos</p>
            <h2 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 36, fontWeight: 700, color: 'var(--sa-text1)', margin: '0 0 14px' }}>Nossos Serviços</h2>
            <p style={{ fontSize: 16, color: 'var(--sa-text3)', maxWidth: 480, margin: '0 auto' }}>Cada atendimento é pensado com atenção e precisão para superar suas expectativas.</p>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 20 }}>
            {SA_SERVICES.map((svc, i) => (
              <div key={svc.id} style={{ background: 'var(--sa-surface)', border: '1px solid var(--sa-border)', borderRadius: 16, padding: 28, transition: 'all 220ms ease', cursor: 'default' }}
                onMouseEnter={e => { e.currentTarget.style.boxShadow = '0 12px 32px rgba(0,0,0,.1)'; e.currentTarget.style.transform = 'translateY(-3px)'; }}
                onMouseLeave={e => { e.currentTarget.style.boxShadow = 'none'; e.currentTarget.style.transform = 'none'; }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 }}>
                  <div style={{ width: 52, height: 52, borderRadius: 14, background: `var(--sa-secondary)18`, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <Icon name="scissors" size={24} style={{ color: 'var(--sa-secondary)' }} />
                  </div>
                  <span style={{ fontSize: 11, fontWeight: 600, color: 'var(--sa-text3)', background: 'var(--sa-surface2)', border: '1px solid var(--sa-border)', borderRadius: 20, padding: '3px 10px' }}>{svc.duration}min</span>
                </div>
                <h3 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 18, fontWeight: 600, color: 'var(--sa-text1)', margin: '0 0 8px' }}>{svc.name}</h3>
                <p style={{ fontSize: 13, color: 'var(--sa-text3)', margin: '0 0 20px', lineHeight: 1.6 }}>
                  {i === 0 ? 'Corte personalizado com técnicas modernas e acabamento impecável.' :
                   i === 1 ? 'Modelagem e acabamento de barba com navalha e produtos premium.' :
                   i === 2 ? 'Combinação perfeita para o visual completo e sofisticado.' :
                   i === 3 ? 'Coloração com produtos de alta qualidade e resultados duradouros.' :
                   i === 4 ? 'Tratamento intensivo para cabelos ressecados e danificados.' :
                              'Barba modelada e bigode aparado com precisão artesanal.'}
                </p>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <span style={{ fontFamily: "'Poppins',sans-serif", fontSize: 24, fontWeight: 800, color: 'var(--sa-secondary)' }}>{SA_FMT.currency(svc.price)}</span>
                  <Btn size="sm" onClick={() => setBook(true)}>Agendar</Btn>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* ── PORTFOLIO / RECENT WORKS ──────────────────── */}
        <div style={{ marginBottom: 80 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: 36 }}>
            <div>
              <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--sa-secondary)', letterSpacing: '2px', textTransform: 'uppercase', marginBottom: 10 }}>Portfólio</p>
              <h2 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 36, fontWeight: 700, color: 'var(--sa-text1)', margin: 0 }}>Trabalhos Recentes</h2>
            </div>
            <span style={{ fontSize: 13, color: 'var(--sa-secondary)', fontWeight: 600, cursor: 'pointer' }}>Ver todos →</span>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gridTemplateRows: 'auto auto', gap: 14 }}>
            {/* Large featured */}
            <div style={{ gridRow: '1 / 3' }}>
              <ImgSlot label="foto-destaque.jpg" h={460} radius={16} />
            </div>
            {WORKS.slice(1, 5).map((w, i) => (
              <div key={w} style={{ position: 'relative' }}>
                <ImgSlot label={`${w.toLowerCase().replace(/\s+/g,'-')}.jpg`} h={215} radius={12} />
                <div style={{ position: 'absolute', bottom: 10, left: 10, background: 'rgba(0,0,0,.65)', borderRadius: 8, padding: '4px 10px', backdropFilter: 'blur(4px)' }}>
                  <span style={{ fontSize: 11, fontWeight: 600, color: '#fff' }}>{w}</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* ── TEAM ──────────────────────────────────────── */}
        <div style={{ marginBottom: 80 }}>
          <div style={{ textAlign: 'center', marginBottom: 48 }}>
            <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--sa-secondary)', letterSpacing: '2px', textTransform: 'uppercase', marginBottom: 12 }}>Quem faz acontecer</p>
            <h2 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 36, fontWeight: 700, color: 'var(--sa-text1)', margin: 0 }}>Nossa Equipe</h2>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 24 }}>
            {PRODETAILS.map(prof => (
              <div key={prof.id} style={{ background: 'var(--sa-surface)', border: '1px solid var(--sa-border)', borderRadius: 20, overflow: 'hidden' }}>
                {/* Photo area */}
                <div style={{ position: 'relative', height: 220, background: 'var(--sa-surface2)' }}>
                  <svg width="100%" height="100%" style={{ position: 'absolute', inset: 0 }}>
                    <defs>
                      <pattern id={`pm-${prof.id}`} patternUnits="userSpaceOnUse" width="20" height="20" patternTransform="rotate(45)">
                        <rect width="20" height="20" fill="var(--sa-surface2)"/><rect width="10" height="20" fill="rgba(0,0,0,.025)"/>
                      </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill={`url(#pm-${prof.id})`}/>
                  </svg>
                  <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%,-50%)', textAlign: 'center' }}>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" strokeWidth="1.3" style={{ display: 'block', margin: '0 auto 6px' }}>
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <div style={{ fontFamily: 'monospace', fontSize: 10, color: 'var(--sa-text3)' }}>foto-{prof.name.split(' ')[0].toLowerCase()}.jpg</div>
                  </div>
                  {/* Rating badge */}
                  <div style={{ position: 'absolute', top: 12, right: 12, background: 'rgba(0,0,0,.7)', borderRadius: 20, padding: '4px 10px', display: 'flex', alignItems: 'center', gap: 4, backdropFilter: 'blur(4px)' }}>
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="#fbbf24" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span style={{ fontSize: 12, fontWeight: 700, color: '#fff' }}>{prof.rating}</span>
                  </div>
                </div>
                {/* Info */}
                <div style={{ padding: 24 }}>
                  <h3 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 18, fontWeight: 700, color: 'var(--sa-text1)', margin: '0 0 4px' }}>{prof.name}</h3>
                  <p style={{ fontSize: 12, color: 'var(--sa-secondary)', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '1px', margin: '0 0 12px' }}>{prof.role}</p>
                  <p style={{ fontSize: 13, color: 'var(--sa-text3)', lineHeight: 1.7, margin: '0 0 16px' }}>{prof.bio}</p>
                  {/* Specialties */}
                  <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginBottom: 20 }}>
                    {prof.specs.map(s => (
                      <span key={s} style={{ fontSize: 11, fontWeight: 600, color: 'var(--sa-secondary)', background: `var(--sa-secondary)12`, border: `1px solid var(--sa-secondary)30`, borderRadius: 20, padding: '3px 10px' }}>{s}</span>
                    ))}
                  </div>
                  <div style={{ display: 'flex', gap: 8 }}>
                    <Btn size="sm" fullWidth onClick={() => setSelProf(prof)} icon={<Icon name="clock" size={14}/>}>Ver Horários</Btn>
                    <Btn size="sm" variant="secondary" onClick={() => window.SA_TOAST('Portfólio em breve!', 'info')}>
                      <Icon name="eye" size={14}/>
                    </Btn>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* ── STORE SECTION ──────────────────────────────── */}
        {(window.SA_PRODUCTS||[]).filter(p=>p.active).length > 0 && (
          <div style={{ marginBottom:80 }}>
            <div style={{ textAlign:'center', marginBottom:40 }}>
              <p style={{ fontSize:12, fontWeight:600, color:'var(--sa-secondary)', letterSpacing:'2px', textTransform:'uppercase', marginBottom:12 }}>Disponível para compra</p>
              <h2 style={{ fontFamily:"'Poppins',sans-serif", fontSize:36, fontWeight:700, color:'var(--sa-text1)', margin:'0 0 12px' }}>Loja de Produtos</h2>
              <p style={{ fontSize:15, color:'var(--sa-text3)', margin:0 }}>Produtos selecionados para cuidar do visual em casa</p>
            </div>
            <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:16 }}>
              {(window.SA_PRODUCTS||[]).filter(p=>p.active).slice(0,8).map(p=>(
                <div key={p.id} style={{ background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:14, padding:20, transition:'all 220ms ease', cursor:'default' }}
                  onMouseEnter={e=>{e.currentTarget.style.boxShadow='0 8px 24px rgba(0,0,0,.1)';e.currentTarget.style.transform='translateY(-3px)';}}
                  onMouseLeave={e=>{e.currentTarget.style.boxShadow='none';e.currentTarget.style.transform='none';}}>
                  <div style={{ width:52, height:52, borderRadius:12, background:'color-mix(in srgb,var(--sa-secondary) 15%,transparent)', display:'flex', alignItems:'center', justifyContent:'center', marginBottom:14 }}>
                    <Icon name="star" size={22} style={{ color:'var(--sa-secondary)' }}/>
                  </div>
                  <div style={{ fontFamily:"'Poppins',sans-serif", fontSize:14, fontWeight:700, color:'var(--sa-text1)', marginBottom:4 }}>{p.name}</div>
                  <div style={{ fontSize:12, color:'var(--sa-text3)', marginBottom:12, lineHeight:1.5 }}>{p.desc?.slice(0,50)}{p.desc?.length>50?'…':''}</div>
                  <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center' }}>
                    <span style={{ fontFamily:"'Poppins',sans-serif", fontSize:20, fontWeight:800, color:'var(--sa-secondary)' }}>{SA_FMT.currency(p.price)}</span>
                    <Btn size="sm" onClick={()=>window.SA_TOAST(`${p.name} adicionado ao carrinho!`,'success')}>Comprar</Btn>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* ── TESTIMONIALS ──────────────────────────────── */}
        <div style={{ marginBottom: 80 }}>
          <div style={{ textAlign: 'center', marginBottom: 40 }}>
            <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--sa-secondary)', letterSpacing: '2px', textTransform: 'uppercase', marginBottom: 12 }}>Depoimentos</p>
            <h2 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 36, fontWeight: 700, color: 'var(--sa-text1)', margin: 0 }}>O que dizem nossos clientes</h2>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 20 }}>
            {TESTIMONIALS.map((t, i) => (
              <div key={i} style={{ background: 'var(--sa-surface)', border: '1px solid var(--sa-border)', borderRadius: 16, padding: 28 }}>
                <div style={{ display: 'flex', gap: 2, marginBottom: 16 }}>
                  {[1,2,3,4,5].map(s => (
                    <svg key={s} width="14" height="14" viewBox="0 0 24 24" fill="var(--sa-secondary)" stroke="none">
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                  ))}
                </div>
                <p style={{ fontSize: 15, color: 'var(--sa-text1)', lineHeight: 1.7, margin: '0 0 20px', fontStyle: 'italic' }}>"{t.text}"</p>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12, paddingTop: 16, borderTop: '1px solid var(--sa-border)' }}>
                  <Avt name={t.name} size={36} color="var(--sa-primary)" />
                  <div>
                    <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--sa-text1)' }}>{t.name}</div>
                    <div style={{ fontSize: 12, color: 'var(--sa-text3)' }}>{t.svc}</div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>

      {/* ── BOOKING CTA SECTION ───────────────────────────── */}
      <div style={{ background: 'var(--sa-primary)', position: 'relative', overflow: 'hidden' }}>
        <svg width="100%" height="100%" style={{ position: 'absolute', inset: 0, pointerEvents: 'none' }}>
          <defs>
            <pattern id="cta-stripe" patternUnits="userSpaceOnUse" width="32" height="32" patternTransform="rotate(45)">
              <rect width="32" height="32" fill="transparent"/><rect width="16" height="32" fill="rgba(255,255,255,.02)"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#cta-stripe)"/>
        </svg>
        <div style={{ position: 'relative', maxWidth: 700, margin: '0 auto', padding: '80px 48px', textAlign: 'center' }}>
          <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--sa-secondary)', letterSpacing: '2px', textTransform: 'uppercase', marginBottom: 16 }}>Pronto para uma nova experiência?</p>
          <h2 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 42, fontWeight: 800, color: '#fff', margin: '0 0 18px', lineHeight: 1.1 }}>Agende seu horário hoje mesmo</h2>
          <p style={{ fontSize: 16, color: 'rgba(255,255,255,.55)', margin: '0 0 36px', lineHeight: 1.7 }}>Confirmação imediata via WhatsApp. Sem filas, sem espera.</p>
          <div style={{ display: 'flex', gap: 14, justifyContent: 'center' }}>
            <Btn size="lg" onClick={() => setBook(true)} style={{ background: 'var(--sa-secondary)' }}>Agendar Agora</Btn>
            <button style={{ display: 'flex', alignItems: 'center', gap: 8, background: 'transparent', border: '1.5px solid rgba(255,255,255,.25)', borderRadius: 9, padding: '14px 28px', cursor: 'pointer', color: '#fff', fontSize: 15, fontWeight: 600, fontFamily: "'Inter',sans-serif" }}>
              <Icon name="phone" size={16}/> Ligar Agora
            </button>
          </div>
        </div>
      </div>

      {/* ── FOOTER ────────────────────────────────────────── */}
      <footer style={{ background: '#0a0a0a', padding: '48px 80px 28px' }}>
        <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', gap: 48, marginBottom: 40 }}>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
              <LogoMark size={36} />
              <div style={{ fontFamily: "'Poppins',sans-serif", fontSize: 18, fontWeight: 700, color: '#fff' }}>Barbearia Style</div>
            </div>
            <p style={{ fontSize: 13, color: 'rgba(255,255,255,.4)', lineHeight: 1.8, maxWidth: 280, margin: '0 0 20px' }}>
              Barbearia premium com atendimento personalizado. Onde estilo encontra tradição.
            </p>
            <div style={{ display: 'flex', gap: 10 }}>
              {['Instagram', 'WhatsApp', 'Facebook'].map(s => (
                <div key={s} style={{ width: 34, height: 34, borderRadius: 9, background: 'rgba(255,255,255,.06)', border: '1px solid rgba(255,255,255,.08)', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}>
                  <Icon name="globe" size={15} style={{ color: 'rgba(255,255,255,.4)' }} />
                </div>
              ))}
            </div>
          </div>
          <div>
            <div style={{ fontSize: 11, fontWeight: 700, color: 'rgba(255,255,255,.3)', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: 16 }}>Links</div>
            {['Serviços', 'Portfólio', 'Nossa Equipe', 'Agendamento', 'Contato'].map(l => (
              <div key={l} style={{ fontSize: 13, color: 'rgba(255,255,255,.45)', marginBottom: 10, cursor: 'pointer' }}>{l}</div>
            ))}
          </div>
          <div>
            <div style={{ fontSize: 11, fontWeight: 700, color: 'rgba(255,255,255,.3)', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: 16 }}>Contato</div>
            {[
              { icon: 'map',   v: 'Rua das Flores, 123\nJardim Paulista – SP' },
              { icon: 'phone', v: '(11) 99999-0000' },
              { icon: 'clock', v: 'Seg–Sex 8h–20h\nSáb 8h–16h' },
            ].map(({ icon, v }) => (
              <div key={v} style={{ display: 'flex', gap: 10, marginBottom: 12, alignItems: 'flex-start' }}>
                <Icon name={icon} size={14} style={{ color: 'var(--sa-secondary)', marginTop: 1, flexShrink: 0 }} />
                <span style={{ fontSize: 12, color: 'rgba(255,255,255,.45)', lineHeight: 1.6, whiteSpace: 'pre-line' }}>{v}</span>
              </div>
            ))}
          </div>
        </div>
        <div style={{ borderTop: '1px solid rgba(255,255,255,.06)', paddingTop: 20, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <span style={{ fontSize: 12, color: 'rgba(255,255,255,.2)' }}>© 2026 Barbearia Style. Todos os direitos reservados.</span>
          <span style={{ fontSize: 11, color: 'rgba(255,255,255,.15)' }}>Powered by suaAgenda.pro</span>
        </div>
      </footer>

      <QuickBookModal open={!!book} prefill={typeof book === 'object' && book !== null ? book : null} onClose={() => setBook(false)} />
      <ProfDetailModal
        open={!!selProf} prof={selProf}
        onClose={() => setSelProf(null)}
        onBook={data => { setSelProf(null); setBook(data); }}
      />
    </div>
  );
}

Object.assign(window, { PublicScreen });
