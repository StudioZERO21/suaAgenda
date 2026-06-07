// ============================================================
// suaAgenda.pro — Clients Screen
// ============================================================
const { useState, useMemo } = React;

function ClientsScreen() {
  const [search, setSearch] = useState('');
  const [sortCol, setSortCol] = useState('name');
  const [sortDir, setSortDir] = useState('asc');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selected, setSelected] = useState(new Set());
  const [detail, setDetail] = useState(null);
  const [page, setPage] = useState(0);
  const PER_PAGE = 8;

  const filtered = useMemo(() => {
    let list = [...SA_CLIENTS];
    if (search) list = list.filter(c =>
      c.name.toLowerCase().includes(search.toLowerCase()) ||
      c.email.toLowerCase().includes(search.toLowerCase()) ||
      c.phone.includes(search)
    );
    if (statusFilter !== 'all') list = list.filter(c => c.status === statusFilter);
    list.sort((a, b) => {
      let va = a[sortCol], vb = b[sortCol];
      if (typeof va === 'string') va = va.toLowerCase(), vb = vb.toLowerCase();
      if (va < vb) return sortDir === 'asc' ? -1 : 1;
      if (va > vb) return sortDir === 'asc' ? 1 : -1;
      return 0;
    });
    return list;
  }, [search, sortCol, sortDir, statusFilter]);

  const paged = filtered.slice(page * PER_PAGE, (page + 1) * PER_PAGE);
  const totalPages = Math.ceil(filtered.length / PER_PAGE);

  const handleSort = col => {
    if (sortCol === col) setSortDir(d => d === 'asc' ? 'desc' : 'asc');
    else { setSortCol(col); setSortDir('asc'); }
  };

  const toggleSel = id => setSelected(s => {
    const ns = new Set(s);
    ns.has(id) ? ns.delete(id) : ns.add(id);
    return ns;
  });

  const SortIcon = ({ col }) => {
    if (sortCol !== col) return <Icon name="chevD" size={12} style={{ opacity: .3 }} />;
    return <Icon name={sortDir === 'asc' ? 'arrowUp' : 'arrowDown'} size={12} style={{ color: 'var(--sa-secondary)' }} />;
  };

  const colStyle = { padding: '11px 14px', fontSize: 12, fontWeight: 600, color: 'var(--sa-text3)', textTransform: 'uppercase', letterSpacing: '.5px', cursor: 'pointer', userSelect: 'none', whiteSpace: 'nowrap' };
  const cellStyle = { padding: '14px', fontSize: 14, color: 'var(--sa-text1)', borderBottom: '1px solid var(--sa-border)', verticalAlign: 'middle' };

  return (
    <div style={{ flex: 1, padding: '0 0 32px' }}>
      <AppHeader
        title="Clientes"
        subtitle={`${filtered.length} cliente${filtered.length !== 1 ? 's' : ''} encontrado${filtered.length !== 1 ? 's' : ''}`}
        actions={
          <Btn icon={<Icon name="plus" size={15} />} size="md"
            onClick={() => window.SA_TOAST('Formulário de novo cliente em breve!', 'info')}>
            Novo Cliente
          </Btn>
        }
      />

      <div style={{ padding: '20px 32px 0' }}>
        {/* Search + filter bar */}
        <Card style={{ padding: '14px 20px', marginBottom: 16 }}>
          <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
            <Inp value={search} onChange={e => { setSearch(e.target.value); setPage(0); }}
              placeholder="Buscar por nome, e-mail ou telefone..."
              icon={<Icon name="search" size={14} />}
              style={{ flex: 1, marginBottom: 0 }} />
            <select value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(0); }}
              style={{ padding: '10px 32px 10px 13px', fontSize: 13, border: '1.5px solid var(--sa-border)', borderRadius: 8, background: 'var(--sa-surface)', color: 'var(--sa-text1)', cursor: 'pointer', fontFamily: "'Inter',sans-serif", appearance: 'none', backgroundImage: `url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e")`, backgroundRepeat: 'no-repeat', backgroundPosition: 'right 8px center', backgroundSize: 14, whiteSpace: 'nowrap' }}>
              <option value="all">Todos os status</option>
              <option value="active">Ativos</option>
              <option value="inactive">Inativos</option>
            </select>
            {selected.size > 0 && (
              <Btn variant="danger" size="sm" onClick={() => { setSelected(new Set()); window.SA_TOAST(`${selected.size} cliente(s) removido(s)`, 'success'); }}>
                Remover {selected.size}
              </Btn>
            )}
          </div>
        </Card>

        {/* Table */}
        <Card style={{ padding: 0, overflow: 'hidden' }}>
          <div style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <thead>
                <tr style={{ background: 'var(--sa-surface2)', borderBottom: '1px solid var(--sa-border)' }}>
                  <th style={{ ...colStyle, width: 40 }}>
                    <input type="checkbox" onChange={e => setSelected(e.target.checked ? new Set(paged.map(c => c.id)) : new Set())}
                      checked={paged.length > 0 && paged.every(c => selected.has(c.id))}
                      style={{ cursor: 'pointer', accentColor: 'var(--sa-primary)' }} />
                  </th>
                  {[['name', 'Cliente'], ['email', 'E-mail'], ['phone', 'Telefone'], ['lastDate', 'Último Agend.'], ['total', 'Total'], ['status', 'Status']].map(([col, lbl]) => (
                    <th key={col} style={colStyle} onClick={() => handleSort(col)}>
                      <span style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
                        {lbl} <SortIcon col={col} />
                      </span>
                    </th>
                  ))}
                  <th style={{ ...colStyle, width: 80 }}>Ações</th>
                </tr>
              </thead>
              <tbody>
                {paged.length === 0 ? (
                  <tr><td colSpan={8} style={{ padding: '48px 0', textAlign: 'center', color: 'var(--sa-text3)', fontSize: 14 }}>
                    Nenhum cliente encontrado
                  </td></tr>
                ) : paged.map(client => (
                  <tr key={client.id}
                    onMouseEnter={e => e.currentTarget.style.background = 'var(--sa-surface2)'}
                    onMouseLeave={e => e.currentTarget.style.background = 'transparent'}>
                    <td style={{ ...cellStyle, width: 40 }}>
                      <input type="checkbox" checked={selected.has(client.id)} onChange={() => toggleSel(client.id)}
                        style={{ cursor: 'pointer', accentColor: 'var(--sa-primary)' }} />
                    </td>
                    <td style={cellStyle}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <Avt name={client.name} size={32} color="var(--sa-primary)" />
                        <span style={{ fontWeight: 600, cursor: 'pointer', color: 'var(--sa-primary)' }}
                          onClick={() => setDetail(client)}>{client.name}</span>
                      </div>
                    </td>
                    <td style={{ ...cellStyle, color: 'var(--sa-text2)' }}>{client.email}</td>
                    <td style={{ ...cellStyle, color: 'var(--sa-text2)' }}>{client.phone}</td>
                    <td style={{ ...cellStyle, color: 'var(--sa-text2)' }}>{SA_FMT.date(client.lastDate)}</td>
                    <td style={cellStyle}><span style={{ fontWeight: 600 }}>{client.total}</span></td>
                    <td style={cellStyle}><Badge status={client.status} /></td>
                    <td style={cellStyle}>
                      <div style={{ display: 'flex', gap: 6 }}>
                        <button onClick={() => setDetail(client)} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--sa-text3)', padding: 4, borderRadius: 4, display: 'flex' }}>
                          <Icon name="eye" size={15} />
                        </button>
                        <button onClick={() => window.SA_TOAST('Edição em breve!', 'info')} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--sa-text3)', padding: 4, borderRadius: 4, display: 'flex' }}>
                          <Icon name="edit" size={15} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {totalPages > 1 && (
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 20px', borderTop: '1px solid var(--sa-border)' }}>
              <span style={{ fontSize: 13, color: 'var(--sa-text3)' }}>
                {page * PER_PAGE + 1}–{Math.min((page + 1) * PER_PAGE, filtered.length)} de {filtered.length}
              </span>
              <div style={{ display: 'flex', gap: 6 }}>
                <Btn variant="muted" size="sm" disabled={page === 0} onClick={() => setPage(p => p - 1)} icon={<Icon name="chevL" size={14} />} />
                {Array.from({ length: totalPages }, (_, i) => (
                  <Btn key={i} variant={i === page ? 'primary' : 'muted'} size="sm" onClick={() => setPage(i)}>{i + 1}</Btn>
                ))}
                <Btn variant="muted" size="sm" disabled={page >= totalPages - 1} onClick={() => setPage(p => p + 1)} icon={<Icon name="chevR" size={14} />} />
              </div>
            </div>
          )}
        </Card>
      </div>

      {/* Client detail modal */}
      {detail && (
        <Modal open={!!detail} onClose={() => setDetail(null)} title={detail.name} subtitle="Perfil do Cliente" size="lg"
          footer={<>
            <Btn variant="secondary" size="sm" onClick={() => setDetail(null)}>Fechar</Btn>
            <Btn variant="muted" size="sm" icon={<Icon name="phone" size={14}/>}
              onClick={() => window.open(`https://wa.me/${detail.phone.replace(/\D/g,'')}`, '_blank')}>
              WhatsApp
            </Btn>
            <Btn size="sm" onClick={() => { setDetail(null); window.SA_TOAST('Novo agendamento iniciado!', 'success'); }}>
              Novo Agendamento
            </Btn>
          </>}>
          <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
            {/* Header with photo */}
            <div style={{ display:'flex', alignItems:'center', gap:16 }}>
              <div style={{ position:'relative' }}>
                <Avt name={detail.name} size={72} color="var(--sa-primary)" />
                <button onClick={() => window.SA_TOAST('Upload de foto em breve!','info')}
                  style={{ position:'absolute', bottom:-2, right:-2, width:22, height:22, borderRadius:'50%', background:'var(--sa-secondary)', border:'2px solid var(--sa-surface)', display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer' }}>
                  <Icon name="arrowUp" size={10} style={{ color:'#fff' }}/>
                </button>
              </div>
              <div style={{ flex:1 }}>
                <div style={{ fontSize:20, fontWeight:700, color:'var(--sa-text1)', fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)" }}>{detail.name}</div>
                <div style={{ display:'flex', alignItems:'center', gap:10, marginTop:6 }}>
                  <Badge status={detail.status} />
                  <button onClick={() => window.open(`https://wa.me/${detail.phone.replace(/\D/g,'')}`, '_blank')}
                    style={{ display:'flex', alignItems:'center', gap:6, fontSize:12, fontWeight:600, color:'#25D366', background:'rgba(37,211,102,.1)', border:'1px solid rgba(37,211,102,.25)', borderRadius:20, padding:'4px 12px', cursor:'pointer' }}>
                    <Icon name="phone" size={12}/> Abrir WhatsApp
                  </button>
                  <a href={`mailto:${detail.email}`} style={{ display:'flex', alignItems:'center', gap:6, fontSize:12, fontWeight:600, color:'var(--sa-secondary)', background:'color-mix(in srgb,var(--sa-secondary) 10%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-secondary) 25%,transparent)', borderRadius:20, padding:'4px 12px', textDecoration:'none' }}>
                    <Icon name="user" size={12}/> E-mail
                  </a>
                </div>
              </div>
            </div>
            {/* Info grid */}
            <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:10 }}>
              {[
                { l:'E-mail',         v:detail.email },
                { l:'Telefone',       v:detail.phone },
                { l:'Último Agend.',  v:SA_FMT.date(detail.lastDate) },
                { l:'Total de Visitas',v:detail.total },
              ].map(({ l, v }) => (
                <div key={l} style={{ background:'var(--sa-surface2)', borderRadius:8, padding:'10px 14px' }}>
                  <div style={{ fontSize:11, color:'var(--sa-text3)', fontWeight:600, textTransform:'uppercase', letterSpacing:'.4px' }}>{l}</div>
                  <div style={{ fontSize:14, fontWeight:600, color:'var(--sa-text1)', marginTop:3 }}>{v}</div>
                </div>
              ))}
            </div>
            {/* Before & After photos */}
            <div>
              <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:10 }}>
                <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>Fotos de Atendimento (Antes & Depois)</div>
                <Btn variant="muted" size="sm" onClick={() => window.SA_TOAST('Upload de foto em breve!','info')} icon={<Icon name="arrowUp" size={13}/>}>Adicionar</Btn>
              </div>
              <div style={{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:8 }}>
                {['Antes — Corte','Depois — Corte','Antes — Barba'].map((lbl,i) => {
                  const uid = `cli-ph-${detail.id}-${i}`;
                  return (
                    <div key={lbl} style={{ aspectRatio:'4/3', borderRadius:10, overflow:'hidden', position:'relative', background:'var(--sa-surface2)', border:'1px solid var(--sa-border)', cursor:'pointer' }}
                      onClick={() => window.SA_TOAST(`${lbl} — em breve!`,'info')}>
                      <svg width="100%" height="100%" style={{ position:'absolute', inset:0 }}>
                        <defs><pattern id={uid} patternUnits="userSpaceOnUse" width="14" height="14" patternTransform="rotate(45)">
                          <rect width="14" height="14" fill="var(--sa-surface2)"/><rect width="7" height="14" fill="rgba(0,0,0,.025)"/>
                        </pattern></defs>
                        <rect width="100%" height="100%" fill={`url(#${uid})`}/>
                      </svg>
                      <div style={{ position:'absolute', inset:0, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:4 }}>
                        <Icon name="arrowUp" size={16} style={{ color:'var(--sa-text3)', opacity:.4 }}/>
                        <div style={{ fontSize:9, fontFamily:'monospace', color:'var(--sa-text3)', textAlign:'center', padding:'0 6px' }}>{lbl}</div>
                      </div>
                    </div>
                  );
                })}
                {/* Add slot */}
                <div onClick={() => window.SA_TOAST('Upload de foto em breve!','info')}
                  style={{ aspectRatio:'4/3', borderRadius:10, border:'2px dashed var(--sa-border)', display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:4, cursor:'pointer', transition:'all 160ms' }}
                  onMouseEnter={e=>{e.currentTarget.style.borderColor='var(--sa-primary)';e.currentTarget.style.background='color-mix(in srgb,var(--sa-primary) 4%,transparent)';}}
                  onMouseLeave={e=>{e.currentTarget.style.borderColor='var(--sa-border)';e.currentTarget.style.background='transparent';}}>
                  <Icon name="plus" size={18} style={{ color:'var(--sa-text3)' }}/>
                  <div style={{ fontSize:10, color:'var(--sa-text3)', fontFamily:'monospace' }}>Adicionar foto</div>
                </div>
              </div>
              <p style={{ fontSize:11, color:'var(--sa-text3)', marginTop:8 }}>Fotos adicionadas aqui aparecem automaticamente no portfólio da equipe.</p>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
}

Object.assign(window, { ClientsScreen });
