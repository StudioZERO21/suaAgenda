// ============================================================
// suaAgenda.pro — New Appointment Modal
// ============================================================
const { useState, useEffect, useMemo } = React;

function ClientSearch({ value, onChange }) {
  const [query, setQuery]     = useState(value?.name || '');
  const [open, setOpen]       = useState(false);
  const [focused, setFocused] = useState(false);

  const results = useMemo(() => {
    if (!query.trim()) return SA_CLIENTS.slice(0, 5);
    return SA_CLIENTS.filter(c =>
      c.name.toLowerCase().includes(query.toLowerCase()) ||
      c.phone.includes(query)
    ).slice(0, 6);
  }, [query]);

  const select = (c) => { onChange(c); setQuery(c.name); setOpen(false); };

  return (
    <div style={{ position: 'relative' }}>
      <label style={{ fontSize: 13, fontWeight: 600, color: 'var(--sa-text1)', display: 'block', marginBottom: 5 }}>
        Cliente <span style={{ color: '#ef4444' }}>*</span>
      </label>
      <div style={{ position: 'relative' }}>
        <span style={{ position: 'absolute', left: 11, top: '50%', transform: 'translateY(-50%)', color: 'var(--sa-text3)', display: 'flex', pointerEvents: 'none' }}>
          <Icon name="search" size={14} />
        </span>
        <input
          value={query}
          onChange={e => { setQuery(e.target.value); setOpen(true); onChange(null); }}
          onFocus={() => { setOpen(true); setFocused(true); }}
          onBlur={() => { setTimeout(() => setOpen(false), 180); setFocused(false); }}
          placeholder="Buscar cliente por nome ou telefone..."
          style={{
            width: '100%', padding: '10px 13px 10px 34px', fontSize: 14,
            border: `1.5px solid ${focused ? 'var(--sa-primary)' : 'var(--sa-border)'}`,
            borderRadius: 8, background: 'var(--sa-surface)', color: 'var(--sa-text1)',
            outline: focused ? '3px solid rgba(0,0,0,.06)' : 'none',
            fontFamily: "'Inter',sans-serif", boxSizing: 'border-box',
          }}
        />
      </div>
      {open && results.length > 0 && (
        <div style={{
          position: 'absolute', top: '100%', left: 0, right: 0, marginTop: 4,
          background: 'var(--sa-surface)', border: '1.5px solid var(--sa-border)',
          borderRadius: 10, boxShadow: '0 8px 24px rgba(0,0,0,.12)', zIndex: 50, overflow: 'hidden',
        }}>
          {results.map(c => (
            <div key={c.id} onMouseDown={() => select(c)}
              style={{ padding: '10px 14px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 10 }}
              onMouseEnter={e => e.currentTarget.style.background = 'var(--sa-surface2)'}
              onMouseLeave={e => e.currentTarget.style.background = 'transparent'}>
              <Avt name={c.name} size={30} color="var(--sa-primary)" />
              <div>
                <div style={{ fontSize: 14, fontWeight: 600, color: 'var(--sa-text1)' }}>{c.name}</div>
                <div style={{ fontSize: 12, color: 'var(--sa-text3)' }}>{c.phone} · {c.total} visitas</div>
              </div>
            </div>
          ))}
          <div onMouseDown={() => { onChange({ id: Date.now(), name: query, isNew: true }); setOpen(false); }}
            style={{ padding: '10px 14px', cursor: 'pointer', borderTop: '1px solid var(--sa-border)', display: 'flex', alignItems: 'center', gap: 8 }}
            onMouseEnter={e => e.currentTarget.style.background = 'var(--sa-surface2)'}
            onMouseLeave={e => e.currentTarget.style.background = 'transparent'}>
            <Icon name="plus" size={14} style={{ color: 'var(--sa-secondary)' }} />
            <span style={{ fontSize: 13, color: 'var(--sa-secondary)', fontWeight: 600 }}>Cadastrar "{query}"</span>
          </div>
        </div>
      )}
    </div>
  );
}

function TimePicker({ value, onChange, serviceId }) {
  const slots = [];
  for (let h = 8; h < 20; h++) {
    for (let m = 0; m < 60; m += 30) {
      slots.push({ h, m, label: SA_FMT.time(h, m) });
    }
  }
  return (
    <div>
      <label style={{ fontSize: 13, fontWeight: 600, color: 'var(--sa-text1)', display: 'block', marginBottom: 5 }}>
        Horário <span style={{ color: '#ef4444' }}>*</span>
      </label>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 6, maxHeight: 180, overflowY: 'auto', padding: 2 }}>
        {slots.map(s => {
          const val = `${s.h}:${String(s.m).padStart(2, '0')}`;
          const active = value === val;
          return (
            <button key={val} onClick={() => onChange(val)} style={{
              padding: '8px 0', border: `1.5px solid ${active ? 'var(--sa-primary)' : 'var(--sa-border)'}`,
              borderRadius: 8, background: active ? 'var(--sa-primary)' : 'var(--sa-surface)',
              color: active ? '#fff' : 'var(--sa-text2)', fontSize: 13, fontWeight: active ? 700 : 500,
              cursor: 'pointer', fontFamily: "'Inter',sans-serif", transition: 'all 150ms',
            }}>{s.label}</button>
          );
        })}
      </div>
    </div>
  );
}

function BookingModal({ open, onClose, onSave, initialData }) {
  const [client,   setClient]   = useState(null);
  const [service,  setService]  = useState('');
  const [prof,     setProf]     = useState('');
  const [date,     setDate]     = useState('');
  const [time,     setTime]     = useState('');
  const [notes,    setNotes]    = useState('');
  const [reminder, setReminder] = useState(true);
  const [status,   setStatus]   = useState('confirmed');
  const [errors,   setErrors]   = useState({});
  const [saving,   setSaving]   = useState(false);

  // Pre-fill from calendar click
  useEffect(() => {
    if (open && initialData && typeof initialData === 'object') {
      if (initialData.date) setDate(initialData.date);
      if (initialData.hour != null) setTime(`${initialData.hour}:${String(initialData.min || 0).padStart(2,'0')}`);
    }
    if (!open) {
      setClient(null); setService(''); setProf(''); setDate(''); setTime('');
      setNotes(''); setErrors({}); setSaving(false); setStatus('confirmed');
    }
  }, [open, initialData]);

  const svc = SA_SERVICES.find(s => String(s.id) === service);
  const availableProfs = svc
    ? SA_PROFESSIONALS.filter(p => svc.profIds.includes(p.id))
    : SA_PROFESSIONALS;

  const validate = () => {
    const e = {};
    if (!client)    e.client  = 'Selecione um cliente';
    if (!service)   e.service = 'Selecione um serviço';
    if (!prof)      e.prof    = 'Selecione um profissional';
    if (!date)      e.date    = 'Selecione uma data';
    if (!time)      e.time    = 'Selecione um horário';
    return e;
  };

  const handleSave = () => {
    const e = validate();
    if (Object.keys(e).length) { setErrors(e); return; }
    setSaving(true);
    const [h, m] = time.split(':').map(Number);
    setTimeout(() => {
      const profObj = SA_PROFESSIONALS.find(p => String(p.id) === prof);
      const svcObj  = SA_SERVICES.find(s => String(s.id) === service);
      onSave({
        id: Date.now(),
        clientId: client.id, clientName: client.name,
        professionalId: profObj.id, professionalName: profObj.name, professionalColor: profObj.color,
        serviceId: svcObj.id, serviceName: svcObj.name,
        price: svcObj.price, duration: svcObj.duration,
        date, startHour: h, startMin: m, status,
        notes,
      });
      setSaving(false);
    }, 900);
  };

  const today = new Date().toISOString().split('T')[0];

  return (
    <Modal open={open} onClose={onClose} title="Novo Agendamento" subtitle="Preencha os dados abaixo" size="md"
      footer={<>
        <Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn>
        <Btn size="sm" loading={saving} onClick={handleSave} icon={<Icon name="check" size={14} />}>
          Agendar
        </Btn>
      </>}>

      <div style={{ display: 'flex', flexDirection: 'column', gap: 18 }}>
        {/* Client */}
        <div>
          <ClientSearch value={client} onChange={setClient} />
          {errors.client && <span style={{ fontSize: 12, color: '#ef4444', marginTop: 4, display: 'block' }}>{errors.client}</span>}
        </div>

        {/* Service */}
        <Sel label="Serviço" value={service} required error={errors.service}
          onChange={e => { setService(e.target.value); setProf(''); }}
          placeholder="Selecionar serviço..."
          options={SA_SERVICES.map(s => ({ value: String(s.id), label: `${s.name} — ${s.duration}min · ${SA_FMT.currency(s.price)}` }))} />

        {/* Professional */}
        <Sel label="Profissional" value={prof} required error={errors.prof}
          onChange={e => setProf(e.target.value)}
          placeholder={service ? 'Selecionar profissional...' : 'Selecione um serviço primeiro'}
          options={availableProfs.map(p => ({ value: String(p.id), label: `${p.name} · ${p.role}` }))} />

        {/* Date & Time */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14 }}>
          <Inp label="Data" value={date} onChange={e => setDate(e.target.value)}
            type="date" required error={errors.date}
            inputStyle={{ colorScheme: 'light' }} />
          <div>
            {!date
              ? <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--sa-text3)', marginTop: 8 }}>Selecione a data primeiro</div>
              : <TimePicker value={time} onChange={setTime} serviceId={service} />
            }
            {errors.time && <span style={{ fontSize: 12, color: '#ef4444', marginTop: 4, display: 'block' }}>{errors.time}</span>}
          </div>
        </div>

        {/* Duration info */}
        {svc && (
          <div style={{ background: 'var(--sa-surface2)', borderRadius: 8, padding: '10px 14px', display: 'flex', gap: 16 }}>
            <span style={{ fontSize: 13, color: 'var(--sa-text2)' }}>
              <strong>Duração:</strong> {svc.duration} min
            </span>
            <span style={{ fontSize: 13, color: 'var(--sa-text2)' }}>
              <strong>Valor:</strong> {SA_FMT.currency(svc.price)}
            </span>
            {time && <span style={{ fontSize: 13, color: 'var(--sa-text2)' }}>
              <strong>Término:</strong> {(() => {
                const [h, m] = time.split(':').map(Number);
                const end = h * 60 + m + svc.duration;
                return SA_FMT.time(Math.floor(end / 60), end % 60);
              })()}
            </span>}
          </div>
        )}

        {/* Notes */}
        <Txta label="Observações" value={notes} onChange={e => setNotes(e.target.value)}
          placeholder="Preferências, alergias, informações extras..." rows={2} />

        {/* Options row */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 24 }}>
          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 13, color: 'var(--sa-text2)' }}>
            <input type="checkbox" checked={reminder} onChange={e => setReminder(e.target.checked)}
              style={{ width: 14, height: 14, accentColor: 'var(--sa-primary)', cursor: 'pointer' }} />
            Enviar lembrete ao cliente
          </label>
          <div style={{ display: 'flex', gap: 8 }}>
            {[['confirmed', 'Confirmado'], ['pending', 'Pendente']].map(([val, lbl]) => (
              <label key={val} style={{ display: 'flex', alignItems: 'center', gap: 6, cursor: 'pointer', fontSize: 13, color: status === val ? 'var(--sa-primary)' : 'var(--sa-text3)', fontWeight: status === val ? 600 : 400 }}>
                <input type="radio" value={val} checked={status === val} onChange={() => setStatus(val)}
                  style={{ accentColor: 'var(--sa-primary)', cursor: 'pointer' }} />
                {lbl}
              </label>
            ))}
          </div>
        </div>
      </div>
    </Modal>
  );
}

Object.assign(window, { BookingModal });
