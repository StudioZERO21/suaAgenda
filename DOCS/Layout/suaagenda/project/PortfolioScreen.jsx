// ============================================================
// suaAgenda.pro — Portfolio Screen (Fotos)
// ============================================================
const { useState, useRef } = React;

const CATEGORIES = ['Todos','Corte','Barba','Coloração','Penteado','Antes & Depois','Ambiente'];

const INIT_PHOTOS = [
  { id:1,  prof:1, profName:'João Silva',   category:'Corte',    title:'Degradê moderno',      date:'2026-06-05', featured:true,  tags:['degradê','fade'] },
  { id:2,  prof:1, profName:'João Silva',   category:'Corte',    title:'Corte clássico',       date:'2026-06-04', featured:false, tags:['clássico'] },
  { id:3,  prof:2, profName:'Carlos Mendes',category:'Barba',    title:'Barba estilizada',     date:'2026-06-03', featured:true,  tags:['barba','navalha'] },
  { id:4,  prof:2, profName:'Carlos Mendes',category:'Barba',    title:'Bigode + barba',       date:'2026-06-02', featured:false, tags:['barba'] },
  { id:5,  prof:3, profName:'Ana Costa',    category:'Coloração',title:'Mechas douradas',      date:'2026-06-01', featured:true,  tags:['mechas','loiro'] },
  { id:6,  prof:3, profName:'Ana Costa',    category:'Coloração',title:'Coloração ruivo',      date:'2026-05-30', featured:false, tags:['coloração','ruivo'] },
  { id:7,  prof:1, profName:'João Silva',   category:'Corte',    title:'Undercut',             date:'2026-05-28', featured:false, tags:['undercut'] },
  { id:8,  prof:2, profName:'Carlos Mendes',category:'Barba',    title:'Barba quadrada',       date:'2026-05-27', featured:false, tags:['barba'] },
  { id:9,  prof:3, profName:'Ana Costa',    category:'Antes & Depois',title:'Transformação completa',date:'2026-05-25',featured:true,tags:['antes-depois'] },
  { id:10, prof:1, profName:'João Silva',   category:'Corte',    title:'Buzz cut texturizado', date:'2026-05-22', featured:false, tags:['buzz','textura'] },
  { id:11, prof:2, profName:'Carlos Mendes',category:'Barba',    title:'Acabamento navalha',   date:'2026-05-20', featured:false, tags:['navalha'] },
  { id:12, prof:3, profName:'Ana Costa',    category:'Penteado', title:'Penteado noiva',       date:'2026-05-18', featured:false, tags:['penteado','festa'] },
];

const PORTFOLIO_PROF_COLORS = { 1:'#1a1a1a', 2:'#d4a574', 3:'#6366f1' };

// ── PHOTO PLACEHOLDER ─────────────────────────────────────────
function PhotoSlot({ photo, idx, onDelete, onToggleFeatured, onClick }) {
  const col = PORTFOLIO_PROF_COLORS[photo.prof] || '#888';
  const uid = `ph-${photo.id}`;
  return (
    <div style={{ position:'relative', borderRadius:12, overflow:'hidden', cursor:'pointer', background:'var(--sa-surface2)', border:'1px solid var(--sa-border)', aspectRatio:'4/3' }}
      onClick={() => onClick(photo)}
      onMouseEnter={e => { e.currentTarget.querySelector('.photo-overlay').style.opacity = '1'; }}
      onMouseLeave={e => { e.currentTarget.querySelector('.photo-overlay').style.opacity = '0'; }}>
      {/* Striped placeholder */}
      <svg width="100%" height="100%" style={{ position:'absolute', inset:0 }}>
        <defs>
          <pattern id={uid} patternUnits="userSpaceOnUse" width="20" height="20" patternTransform="rotate(45)">
            <rect width="20" height="20" fill="var(--sa-surface2)"/>
            <rect width="10" height="20" fill={`${col}08`}/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill={`url(#${uid})`}/>
      </svg>
      {/* Photo icon */}
      <div style={{ position:'absolute', inset:0, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', opacity:.25 }}>
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke={col} strokeWidth="1.3">
          <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
          <circle cx="12" cy="13" r="4"/>
        </svg>
        <span style={{ fontFamily:'monospace', fontSize:9, color:col, marginTop:4 }}>foto-{photo.id}.jpg</span>
      </div>
      {/* Gradient overlay */}
      <div style={{ position:'absolute', inset:0, background:'linear-gradient(to top, rgba(0,0,0,.7) 0%, transparent 60%)', pointerEvents:'none' }}/>
      {/* Info bar */}
      <div style={{ position:'absolute', bottom:0, left:0, right:0, padding:'8px 10px' }}>
        <div style={{ fontSize:11, fontWeight:700, color:'#fff', marginBottom:2, textShadow:'0 1px 2px rgba(0,0,0,.6)' }}>{photo.title}</div>
        <div style={{ fontSize:10, color:'rgba(255,255,255,.6)' }}>{photo.profName} · {photo.category}</div>
      </div>
      {/* Featured badge */}
      {photo.featured && (
        <div style={{ position:'absolute', top:8, left:8, background:'var(--sa-secondary)', borderRadius:20, padding:'2px 8px', fontSize:10, fontWeight:700, color:'#fff' }}>★ Destaque</div>
      )}
      {/* Hover overlay */}
      <div className="photo-overlay" style={{ position:'absolute', inset:0, background:'rgba(0,0,0,.45)', opacity:0, transition:'opacity 180ms', display:'flex', alignItems:'center', justifyContent:'center', gap:8, pointerEvents:'none' }}>
        <div style={{ fontSize:12, color:'#fff', fontWeight:600 }}>Ver detalhes</div>
      </div>
    </div>
  );
}

// ── UPLOAD ZONE ───────────────────────────────────────────────
function UploadZone({ onAdd }) {
  const [drag, setDrag] = useState(false);
  const [category, setCategory] = useState('Corte');
  const [profId, setProfId] = useState('1');
  const [title, setTitle] = useState('');

  const handleAdd = () => {
    if (!title.trim()) return window.SA_TOAST('Adicione um título para a foto','error');
    const profs = { 1:{name:'João Silva'}, 2:{name:'Carlos Mendes'}, 3:{name:'Ana Costa'} };
    onAdd({
      id: Date.now(), prof: Number(profId), profName: profs[profId].name,
      category, title: title.trim(), date: '2026-06-06', featured: false, tags:[],
    });
    setTitle('');
    window.SA_TOAST('Foto adicionada ao portfólio!','success');
  };

  return (
    <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
      {/* Drop zone */}
      <div
        onDragOver={e=>{e.preventDefault();setDrag(true);}}
        onDragLeave={()=>setDrag(false)}
        onDrop={e=>{e.preventDefault();setDrag(false);window.SA_TOAST('Foto recebida! Processando…','info');}}
        style={{ border:`2px dashed ${drag?'var(--sa-primary)':'var(--sa-border)'}`, borderRadius:12, padding:'32px 20px', textAlign:'center', background:drag?'color-mix(in srgb,var(--sa-primary) 5%,transparent)':'var(--sa-surface2)', transition:'all 200ms', cursor:'pointer' }}>
        <div style={{ width:48, height:48, borderRadius:'50%', background:'color-mix(in srgb,var(--sa-primary) 10%,transparent)', display:'flex', alignItems:'center', justifyContent:'center', margin:'0 auto 12px' }}>
          <Icon name="arrowUp" size={22} style={{ color:'var(--sa-primary)' }}/>
        </div>
        <p style={{ fontSize:14, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Arraste fotos aqui</p>
        <p style={{ fontSize:12, color:'var(--sa-text3)', margin:'0 0 12px' }}>ou clique para selecionar</p>
        <Btn variant="muted" size="sm" onClick={()=>window.SA_TOAST('Seletor de arquivo em breve!','info')} icon={<Icon name="arrowUp" size={13}/>}>Selecionar arquivos</Btn>
        <p style={{ fontSize:11, color:'var(--sa-text3)', marginTop:8 }}>JPG, PNG, WebP · Máx. 10MB cada · Múltiplos arquivos</p>
      </div>
      {/* Quick add form */}
      <div style={{ padding:'16px', background:'var(--sa-surface)', borderRadius:12, border:'1px solid var(--sa-border)' }}>
        <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', marginBottom:12 }}>Adicionar foto de demonstração</div>
        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:10, marginBottom:10 }}>
          <Inp label="Título" value={title} onChange={e=>setTitle(e.target.value)} placeholder="Ex: Degradê moderno" required/>
          <Sel label="Categoria" value={category} onChange={e=>setCategory(e.target.value)}
            options={CATEGORIES.slice(1).map(c=>({value:c,label:c}))}/>
        </div>
        <div style={{ display:'grid', gridTemplateColumns:'1fr auto', gap:10, alignItems:'flex-end' }}>
          <Sel label="Profissional" value={profId} onChange={e=>setProfId(e.target.value)}
            options={[{value:'1',label:'João Silva'},{value:'2',label:'Carlos Mendes'},{value:'3',label:'Ana Costa'}]}/>
          <Btn onClick={handleAdd} icon={<Icon name="plus" size={14}/>}>Adicionar</Btn>
        </div>
      </div>
    </div>
  );
}

// ── PHOTO DETAIL MODAL ────────────────────────────────────────
function PhotoModal({ photo, open, onClose, onDelete, onToggleFeatured }) {
  if (!photo) return null;
  const col = PORTFOLIO_PROF_COLORS[photo.prof] || '#888';
  const uid = `phm-${photo.id}`;
  return (
    <Modal open={open} onClose={onClose} title={photo.title} subtitle={`${photo.profName} · ${photo.category}`} size="md"
      footer={<>
        <Btn variant="danger" size="sm" onClick={()=>{onDelete(photo.id);onClose();}} icon={<Icon name="trash" size={14}/>}>Excluir</Btn>
        <Btn variant="secondary" size="sm" onClick={()=>{onToggleFeatured(photo.id);onClose();}} icon={<Icon name="star" size={14}/>}>
          {photo.featured?'Remover destaque':'Marcar como destaque'}
        </Btn>
        <Btn size="sm" onClick={onClose}>Fechar</Btn>
      </>}>
      <div>
        {/* Large photo placeholder */}
        <div style={{ width:'100%', borderRadius:12, overflow:'hidden', marginBottom:16, aspectRatio:'16/10', position:'relative', background:'var(--sa-surface2)', border:'1px solid var(--sa-border)' }}>
          <svg width="100%" height="100%" style={{ position:'absolute', inset:0 }}>
            <defs>
              <pattern id={uid} patternUnits="userSpaceOnUse" width="20" height="20" patternTransform="rotate(45)">
                <rect width="20" height="20" fill="var(--sa-surface2)"/>
                <rect width="10" height="20" fill={`${col}10`}/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill={`url(#${uid})`}/>
          </svg>
          <div style={{ position:'absolute', inset:0, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', opacity:.2 }}>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke={col} strokeWidth="1">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
            <div style={{ fontFamily:'monospace', fontSize:12, color:col, marginTop:8 }}>foto-{photo.id}.jpg</div>
          </div>
          {photo.featured && (
            <div style={{ position:'absolute', top:12, left:12, background:'var(--sa-secondary)', borderRadius:20, padding:'3px 12px', fontSize:11, fontWeight:700, color:'#fff' }}>★ Destaque</div>
          )}
        </div>
        {/* Meta */}
        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:10 }}>
          {[
            { l:'Profissional', v:photo.profName },
            { l:'Categoria',    v:photo.category },
            { l:'Data',         v:SA_FMT.date(photo.date) },
            { l:'Tags',         v:photo.tags.join(', ') || '—' },
          ].map(({ l, v }) => (
            <div key={l} style={{ background:'var(--sa-surface2)', borderRadius:8, padding:'10px 14px' }}>
              <div style={{ fontSize:11, color:'var(--sa-text3)', fontWeight:600, textTransform:'uppercase', letterSpacing:'.4px' }}>{l}</div>
              <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', marginTop:3 }}>{v}</div>
            </div>
          ))}
        </div>
      </div>
    </Modal>
  );
}

// ── MAIN SCREEN ───────────────────────────────────────────────
function PortfolioScreen() {
  const [photos, setPhotos]       = useState(INIT_PHOTOS);
  const [category, setCategory]   = useState('Todos');
  const [profFilter, setProfFilter]= useState('all');
  const [search, setSearch]       = useState('');
  const [selPhoto, setSelPhoto]   = useState(null);
  const [showUpload, setShowUpload] = useState(false);

  const filtered = photos.filter(p => {
    if (category !== 'Todos' && p.category !== category) return false;
    if (profFilter !== 'all' && String(p.prof) !== profFilter) return false;
    if (search && !p.title.toLowerCase().includes(search.toLowerCase()) && !p.profName.toLowerCase().includes(search.toLowerCase())) return false;
    return true;
  });

  const featured = photos.filter(p => p.featured);
  const addPhoto = (photo) => setPhotos(prev => [photo, ...prev]);
  const deletePhoto = (id) => { setPhotos(p => p.filter(x => x.id !== id)); window.SA_TOAST('Foto removida','error'); };
  const toggleFeatured = (id) => setPhotos(p => p.map(x => x.id === id ? { ...x, featured: !x.featured } : x));

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Portfólio" subtitle="Gerencie as fotos dos trabalhos realizados"
        actions={
          <div style={{ display:'flex', gap:8 }}>
            <Btn variant="secondary" onClick={()=>setShowUpload(s=>!s)} icon={<Icon name={showUpload?'x':'arrowUp'} size={14}/>}>
              {showUpload ? 'Fechar upload' : 'Fazer upload'}
            </Btn>
            <Btn onClick={()=>window.SA_TOAST('Portfólio público atualizado!','success')} icon={<Icon name="globe" size={14}/>}>
              Publicar na página
            </Btn>
          </div>
        }/>

      <div style={{ padding:'20px 32px 0' }}>
        {/* Stat cards */}
        <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:16, marginBottom:20 }}>
          {[
            { label:'Total de fotos',  value:photos.length, icon:'eye'     },
            { label:'Destaques',       value:featured.length, icon:'star'  },
            { label:'Categorias',      value:CATEGORIES.length-1, icon:'filter' },
            { label:'Profissionais',   value:3, icon:'users'  },
          ].map(c => {
            const counted = useCountUp(c.value);
            return (
              <div key={c.label} style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, padding:'22px 22px 0', position:'relative', overflow:'hidden', minHeight:120, display:'flex', flexDirection:'column' }}>
                <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:8, opacity:.75 }}>{c.label}</div>
                <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:32, fontWeight:800, color:'var(--sa-text1)', lineHeight:1 }}>{counted}</div>
                <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.07 }}><Icon name={c.icon} size={130} style={{ color:'var(--sa-primary)' }}/></div>
              </div>
            );
          })}
        </div>

        {/* Upload panel */}
        {showUpload && (
          <div style={{ marginBottom:20 }}>
            <Card style={{ padding:20 }}>
              <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Adicionar Fotos</h3>
              <UploadZone onAdd={addPhoto}/>
            </Card>
          </div>
        )}

        {/* Filters */}
        <div style={{ display:'flex', gap:10, alignItems:'center', marginBottom:20, flexWrap:'wrap' }}>
          <div style={{ position:'relative', flex:1, maxWidth:280 }}>
            <Icon name="search" size={14} style={{ position:'absolute', left:11, top:'50%', transform:'translateY(-50%)', color:'var(--sa-text3)', pointerEvents:'none' }}/>
            <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Buscar fotos..."
              style={{ width:'100%', padding:'8px 12px 8px 34px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
          </div>
          {/* Category pills */}
          <div style={{ display:'flex', gap:6, overflowX:'auto' }}>
            {CATEGORIES.map(c => (
              <button key={c} onClick={()=>setCategory(c)} style={{
                padding:'6px 14px', borderRadius:20, border:'1.5px solid',
                borderColor: category===c ? 'var(--sa-primary)' : 'var(--sa-border)',
                background: category===c ? 'var(--sa-primary)' : 'var(--sa-surface)',
                color: category===c ? '#fff' : 'var(--sa-text2)',
                fontSize:12, fontWeight:category===c?700:400, cursor:'pointer',
                fontFamily:'var(--sa-font-body)', whiteSpace:'nowrap', transition:'all 160ms',
              }}>{c}</button>
            ))}
          </div>
          <select value={profFilter} onChange={e=>setProfFilter(e.target.value)}
            style={{ fontSize:13, padding:'8px 12px', border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', cursor:'pointer' }}>
            <option value="all">Todos os profissionais</option>
            <option value="1">João Silva</option>
            <option value="2">Carlos Mendes</option>
            <option value="3">Ana Costa</option>
          </select>
          <span style={{ fontSize:12, color:'var(--sa-text3)', marginLeft:'auto', flexShrink:0 }}>{filtered.length} foto{filtered.length!==1?'s':''}</span>
        </div>

        {/* Grid */}
        {filtered.length === 0 ? (
          <div style={{ textAlign:'center', padding:'60px', color:'var(--sa-text3)' }}>
            <Icon name="eye" size={40} style={{ margin:'0 auto 16px', display:'block', opacity:.3 }}/>
            <div style={{ fontSize:14 }}>Nenhuma foto encontrada para este filtro</div>
          </div>
        ) : (
          <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:12 }}>
            {filtered.map(p => (
              <PhotoSlot key={p.id} photo={p}
                onClick={setSelPhoto}
                onDelete={deletePhoto}
                onToggleFeatured={toggleFeatured}/>
            ))}
          </div>
        )}
      </div>

      <PhotoModal photo={selPhoto} open={!!selPhoto} onClose={()=>setSelPhoto(null)}
        onDelete={deletePhoto} onToggleFeatured={toggleFeatured}/>
    </div>
  );
}

Object.assign(window, { PortfolioScreen });
