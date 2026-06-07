// ============================================================
// suaAgenda.pro — Products Screen (Cadastro de Produtos)
// ============================================================
const { useState } = React;

const PROD_CATS = ['Todos','Cabelo','Barba','Skincare','Cosméticos','Acessórios','Higiene','Outros'];
const PROD_UNITS = ['un.','ml','g','L','kg','caixa','par'];

const INIT_PRODS = [
  { id:1, name:'Pomada modeladora',   category:'Cabelo',    price:45.90, cost:22.00, stock:18, unit:'un.', active:true,  sku:'POB001', desc:'Fixação forte, efeito matte. 100g' },
  { id:2, name:'Óleo de barba',       category:'Barba',     price:38.00, cost:15.00, stock:24, unit:'un.', active:true,  sku:'OBB002', desc:'Hidratação e brilho para barba. 30ml' },
  { id:3, name:'Shampoo profissional',category:'Cabelo',    price:65.00, cost:28.00, stock:12, unit:'un.', active:true,  sku:'SHP003', desc:'Shampoo sem sulfato 300ml' },
  { id:4, name:'Cera capilar',        category:'Cabelo',    price:32.00, cost:14.00, stock:9,  unit:'un.', active:true,  sku:'CRC004', desc:'Cera para acabamento texturizado. 80g' },
  { id:5, name:'Balm pós-barba',      category:'Barba',     price:42.00, cost:18.00, stock:15, unit:'un.', active:true,  sku:'BLM005', desc:'Hidratação e calmante pós-barba. 100ml' },
  { id:6, name:'Loção facial',        category:'Skincare',  price:78.00, cost:35.00, stock:6,  unit:'un.', active:false, sku:'LCF006', desc:'Hidratante facial masculino. 50ml' },
  { id:7, name:'Pente de madeira',    category:'Acessórios',price:24.90, cost:8.00,  stock:30, unit:'un.', active:true,  sku:'PMD007', desc:'Pente artesanal anti-estático' },
];

window.SA_PRODUCTS = INIT_PRODS;

function ProductModal({ open, onClose, prod, onSave }) {
  const blank = { name:'', category:'Cabelo', price:0, cost:0, stock:0, unit:'un.', active:true, sku:'', desc:'', photos:[], coverIdx:0 };
  const [form, setForm] = useState(prod || blank);
  const [saving, setSaving] = useState(false);
  React.useEffect(() => { setForm(prod || blank); }, [prod]);
  const set = (k,v) => setForm(f=>({...f,[k]:v}));
  const margin = form.price > 0 && form.cost > 0 ? Math.round(((form.price-form.cost)/form.price)*100) : 0;

  // Fake photo slots (placeholders)
  const addPhoto = () => {
    const idx = (form.photos||[]).length;
    set('photos', [...(form.photos||[]), { id:Date.now(), label:`Foto ${idx+1}` }]);
    window.SA_TOAST('Slot de foto adicionado — arraste uma imagem aqui!','info');
  };
  const removePhoto = (id) => set('photos', form.photos.filter(p=>p.id!==id));
  const setCover = (idx) => { set('coverIdx', idx); window.SA_TOAST('Foto de capa definida!','success'); };

  const save = () => {
    if (!form.name.trim() || form.price <= 0) return window.SA_TOAST('Preencha nome e preço','error');
    setSaving(true);
    setTimeout(() => {
      setSaving(false); onSave(form); onClose();
      window.SA_TOAST(prod?'Produto atualizado!':'Produto cadastrado!','success');
    }, 600);
  };

  return (
    <Modal open={open} onClose={onClose} size="md"
      title={prod?'Editar Produto':'Novo Produto'} subtitle="Preencha as informações do produto"
      footer={<><Btn variant="secondary" size="sm" onClick={onClose}>Cancelar</Btn><Btn size="sm" loading={saving} onClick={save} icon={<Icon name="check" size={14}/>}>{prod?'Salvar':'Cadastrar'}</Btn></>}>
      <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
        {/* Preview card */}
        <div style={{ display:'flex', gap:14, padding:14, background:'var(--sa-surface2)', borderRadius:12, border:'1px solid var(--sa-border)', alignItems:'center' }}>
          <div style={{ width:56, height:56, borderRadius:12, background:'color-mix(in srgb,var(--sa-secondary) 15%,transparent)', border:'1px dashed var(--sa-border)', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0, cursor:'pointer' }}
            onClick={()=>window.SA_TOAST('Upload de foto em breve!','info')}>
            <Icon name="arrowUp" size={20} style={{ color:'var(--sa-secondary)', opacity:.5 }}/>
          </div>
          <div style={{ flex:1 }}>
            <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:16, fontWeight:700, color:'var(--sa-text1)' }}>{form.name||'Nome do produto'}</div>
            <div style={{ display:'flex', gap:10, marginTop:4 }}>
              <span style={{ fontSize:13, fontWeight:800, color:'var(--sa-secondary)' }}>{SA_FMT.currency(form.price)}</span>
              {margin>0&&<span style={{ fontSize:12, color:'#10b981', fontWeight:600 }}>Margem {margin}%</span>}
            </div>
          </div>
          <div style={{ textAlign:'right' }}>
            <div style={{ fontSize:11, color:'var(--sa-text3)' }}>Estoque</div>
            <div style={{ fontFamily:"var(--sa-font-heading)", fontSize:22, fontWeight:800, color:form.stock<5?'#ef4444':form.stock<10?'#f59e0b':'#10b981' }}>{form.stock}</div>
          </div>
        </div>

        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
          <Inp label="Nome do produto" value={form.name} onChange={e=>set('name',e.target.value)} required placeholder="Ex: Pomada modeladora"/>
          <Inp label="SKU / Código" value={form.sku} onChange={e=>set('sku',e.target.value)} placeholder="Ex: POB001"/>
        </div>
        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
          <Sel label="Categoria" value={form.category} onChange={e=>set('category',e.target.value)} options={PROD_CATS.slice(1).map(c=>({value:c,label:c}))}/>
          <Sel label="Unidade" value={form.unit} onChange={e=>set('unit',e.target.value)} options={PROD_UNITS.map(u=>({value:u,label:u}))}/>
        </div>
        <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr 1fr', gap:12 }}>
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:6 }}>Preço de venda</label>
            <div style={{ position:'relative' }}>
              <span style={{ position:'absolute', left:10, top:'50%', transform:'translateY(-50%)', fontSize:12, color:'var(--sa-text3)' }}>R$</span>
              <input type="number" value={form.price} onChange={e=>set('price',Number(e.target.value))} min={0} step={0.01}
                style={{ width:'100%', padding:'9px 10px 9px 32px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
            </div>
          </div>
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:6 }}>Custo</label>
            <div style={{ position:'relative' }}>
              <span style={{ position:'absolute', left:10, top:'50%', transform:'translateY(-50%)', fontSize:12, color:'var(--sa-text3)' }}>R$</span>
              <input type="number" value={form.cost} onChange={e=>set('cost',Number(e.target.value))} min={0} step={0.01}
                style={{ width:'100%', padding:'9px 10px 9px 32px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
            </div>
          </div>
          <div>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', display:'block', marginBottom:6 }}>Estoque</label>
            <input type="number" value={form.stock} onChange={e=>set('stock',Number(e.target.value))} min={0}
              style={{ width:'100%', padding:'9px 12px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
          </div>
        </div>
        {/* Photo gallery section */}
        <div>
          <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:10 }}>
            <label style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>Fotos do Produto</label>
            <Btn variant="muted" size="sm" onClick={addPhoto} icon={<Icon name="plus" size={13}/>}>Adicionar Foto</Btn>
          </div>
          <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:8 }}>
            {/* Cover / main slot */}
            <div style={{ position:'relative', aspectRatio:'1', borderRadius:10, overflow:'hidden', background:'color-mix(in srgb,var(--sa-secondary) 12%,transparent)', border:`2px solid ${(form.photos||[]).length===0?'var(--sa-border)':'var(--sa-secondary)'}`, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:4, cursor:'pointer' }}
              onClick={()=>window.SA_TOAST('Upload de foto de capa em breve!','info')}>
              <Icon name="star" size={22} style={{ color:'var(--sa-secondary)', opacity:.5 }}/>
              <span style={{ fontSize:10, fontWeight:700, color:'var(--sa-secondary)', letterSpacing:'.5px' }}>CAPA</span>
              {(form.photos||[]).length>0 && (
                <div style={{ position:'absolute', top:4, right:4, background:'var(--sa-secondary)', borderRadius:20, padding:'2px 6px' }}>
                  <span style={{ fontSize:9, fontWeight:700, color:'#fff' }}>CAPA</span>
                </div>
              )}
            </div>
            {/* Additional photo slots */}
            {(form.photos||[]).map((ph,i) => (
              <div key={ph.id} style={{ position:'relative', aspectRatio:'1', borderRadius:10, overflow:'hidden', border:`2px solid ${form.coverIdx===i+1?'var(--sa-secondary)':'var(--sa-border)'}`, background:'var(--sa-surface2)', display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:4, cursor:'pointer' }}
                onClick={()=>window.SA_TOAST('Upload em breve!','info')}>
                <svg width="100%" height="100%" style={{ position:'absolute', inset:0 }}>
                  <defs><pattern id={`pp${ph.id}`} patternUnits="userSpaceOnUse" width="12" height="12" patternTransform="rotate(45)">
                    <rect width="12" height="12" fill="var(--sa-surface2)"/>
                    <rect width="6" height="12" fill="rgba(0,0,0,.025)"/>
                  </pattern></defs>
                  <rect width="100%" height="100%" fill={`url(#pp${ph.id})`}/>
                </svg>
                <div style={{ position:'absolute', inset:0, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:2 }}>
                  <Icon name="arrowUp" size={14} style={{ color:'var(--sa-text3)', opacity:.4 }}/>
                  <span style={{ fontSize:9, fontFamily:'monospace', color:'var(--sa-text3)' }}>{ph.label}</span>
                </div>
                {/* Cover + delete controls */}
                <div style={{ position:'absolute', bottom:0, left:0, right:0, background:'rgba(0,0,0,.5)', padding:'4px 6px', display:'flex', gap:4, justifyContent:'space-between', opacity:0, transition:'opacity 150ms' }}
                  onMouseEnter={e=>e.currentTarget.style.opacity='1'} onMouseLeave={e=>e.currentTarget.style.opacity='0'}>
                  <button onClick={e=>{e.stopPropagation();setCover(i+1);}} style={{ fontSize:9, background:'var(--sa-secondary)', color:'#fff', border:'none', borderRadius:4, padding:'2px 5px', cursor:'pointer', fontWeight:700 }}>Capa</button>
                  <button onClick={e=>{e.stopPropagation();removePhoto(ph.id);}} style={{ fontSize:9, background:'#ef4444', color:'#fff', border:'none', borderRadius:4, padding:'2px 5px', cursor:'pointer' }}>✕</button>
                </div>
              </div>
            ))}
            {/* Add slot */}
            {(form.photos||[]).length < 7 && (
              <div onClick={addPhoto} style={{ aspectRatio:'1', borderRadius:10, border:'2px dashed var(--sa-border)', display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', gap:4, cursor:'pointer', transition:'all 160ms' }}
                onMouseEnter={e=>{e.currentTarget.style.borderColor='var(--sa-primary)';e.currentTarget.style.background='color-mix(in srgb,var(--sa-primary) 4%,transparent)';}}
                onMouseLeave={e=>{e.currentTarget.style.borderColor='var(--sa-border)';e.currentTarget.style.background='transparent';}}>
                <Icon name="plus" size={16} style={{ color:'var(--sa-text3)' }}/>
                <span style={{ fontSize:9, fontFamily:'monospace', color:'var(--sa-text3)' }}>Adicionar</span>
              </div>
            )}
          </div>
          <p style={{ fontSize:11, color:'var(--sa-text3)', marginTop:6 }}>Passe o mouse nas fotos para definir como capa ou remover. Máx. 8 fotos.</p>
        </div>
        <Txta label="Descrição" value={form.desc} onChange={e=>set('desc',e.target.value)} placeholder="Descreva o produto..." rows={2}/>
        <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'10px 14px', background:'var(--sa-surface2)', borderRadius:9, border:'1px solid var(--sa-border)' }}>
          <div>
            <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>Produto ativo</div>
            <div style={{ fontSize:12, color:'var(--sa-text3)' }}>Aparece no PDV e na loja online</div>
          </div>
          <button onClick={()=>set('active',!form.active)}
            style={{ width:42, height:24, borderRadius:12, border:'none', cursor:'pointer', background:form.active?'var(--sa-primary)':'var(--sa-border)', position:'relative', padding:0, transition:'background 200ms' }}>
            <div style={{ position:'absolute', top:3, left:form.active?20:3, width:18, height:18, borderRadius:'50%', background:'#fff', transition:'left 200ms', boxShadow:'0 1px 3px rgba(0,0,0,.2)' }}/>
          </button>
        </div>
        {/* Margin display */}
        {form.price>0&&form.cost>0&&(
          <div style={{ background:margin>=40?'rgba(16,185,129,.07)':margin>=20?'rgba(245,158,11,.07)':'rgba(239,68,68,.06)', border:`1px solid ${margin>=40?'rgba(16,185,129,.2)':margin>=20?'rgba(245,158,11,.2)':'rgba(239,68,68,.15)'}`, borderRadius:9, padding:'10px 14px' }}>
            <div style={{ fontSize:12, fontWeight:700, color:margin>=40?'#059669':margin>=20?'#d97706':'#dc2626' }}>
              Margem de lucro: {margin}% · Lucro por unidade: {SA_FMT.currency(form.price-form.cost)}
            </div>
          </div>
        )}
      </div>
    </Modal>
  );
}

function ProductsScreen() {
  const [prods, setProds]   = useState(INIT_PRODS);
  const [modal, setModal]   = useState(false);
  const [editing, setEditing] = useState(null);
  const [search, setSearch] = useState('');
  const [catFilter, setCatFilter] = useState('Todos');

  const filtered = prods.filter(p => {
    if (catFilter!=='Todos'&&p.category!==catFilter) return false;
    if (search&&!p.name.toLowerCase().includes(search.toLowerCase())&&!p.sku.toLowerCase().includes(search.toLowerCase())) return false;
    return true;
  });

  const openNew  = () => { setEditing(null); setModal(true); };
  const openEdit = (p) => { setEditing(p); setModal(true); };
  const doDelete = (id) => { setProds(prev=>prev.filter(x=>x.id!==id)); window.SA_TOAST('Produto removido','error'); };
  const doToggle = (id) => setProds(prev=>prev.map(x=>x.id===id?{...x,active:!x.active}:x));
  const doSave   = (form) => {
    if (editing) setProds(prev=>prev.map(x=>x.id===editing.id?{...x,...form}:x));
    else { const newProd={...form,id:Date.now()}; setProds(prev=>[...prev,newProd]); window.SA_PRODUCTS=prods; }
  };

  const totalStock  = prods.reduce((s,p)=>s+p.stock,0);
  const stockValue  = prods.reduce((s,p)=>s+p.stock*p.cost,0);
  const lowStock    = prods.filter(p=>p.stock<5&&p.active).length;
  const totalActive = prods.filter(p=>p.active).length;

  const colSt = { padding:'11px 14px', fontSize:12, fontWeight:600, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', borderBottom:'1px solid var(--sa-border)', background:'var(--sa-surface2)' };
  const cellSt= { padding:'12px 14px', fontSize:13, color:'var(--sa-text1)', borderBottom:'1px solid var(--sa-border)', verticalAlign:'middle' };

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Produtos" subtitle="Gerencie o estoque e preços"
        actions={<Btn onClick={openNew} icon={<Icon name="plus" size={15}/>}>Novo Produto</Btn>}/>

      <div style={{ padding:'20px 32px 0' }}>
        {/* Stat cards */}
        <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:16, marginBottom:20 }}>
          {[
            { label:'Produtos ativos', value:totalActive,              icon:'check'  },
            { label:'Total em estoque',value:totalStock,               icon:'filter' },
            { label:'Valor em estoque',value:SA_FMT.currency(stockValue), icon:'dollar' },
            { label:'Estoque baixo',   value:lowStock,                 icon:'clock'  },
          ].map(c=>{
            const num=parseFloat(String(c.value).replace(/[^0-9.]/g,''))||0;
            const counted=useCountUp(num);
            const prefix=String(c.value).match(/^[^0-9]*/)?.[0]||'';
            const suffix=String(c.value).match(/[^0-9]*$/)?.[0]||'';
            const display=num?`${prefix}${counted.toLocaleString('pt-BR',{minimumFractionDigits:prefix.includes('R')?2:0})}${suffix}`:c.value;
            return (
              <div key={c.label} style={{ background:'color-mix(in srgb,var(--sa-primary) 8%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent)', borderRadius:16, padding:'22px 22px 0', position:'relative', overflow:'hidden', minHeight:128, display:'flex', flexDirection:'column' }}>
                <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-primary)', letterSpacing:'1px', textTransform:'uppercase', marginBottom:8, opacity:.75 }}>{c.label}</div>
                <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:30, fontWeight:800, color: c.label==='Estoque baixo'&&num>0?'#ef4444':'var(--sa-text1)', lineHeight:1 }}>{display}</div>
                <div style={{ position:'absolute', bottom:-32, right:-26, opacity:.07 }}><Icon name={c.icon} size={130} style={{ color:'var(--sa-primary)' }}/></div>
              </div>
            );
          })}
        </div>

        {/* Low stock alert */}
        {lowStock>0&&<div style={{ display:'flex', gap:10, alignItems:'center', padding:'10px 16px', background:'rgba(239,68,68,.06)', border:'1px solid rgba(239,68,68,.2)', borderRadius:10, marginBottom:16 }}>
          <Icon name="clock" size={15} style={{ color:'#ef4444', flexShrink:0 }}/>
          <span style={{ fontSize:13, color:'#dc2626', fontWeight:600 }}>{lowStock} produto{lowStock>1?'s':''} com estoque baixo (&lt;5 unidades)</span>
          <Btn variant="ghost" size="sm" style={{ marginLeft:'auto', color:'#dc2626' }}>Ver</Btn>
        </div>}

        {/* Filters */}
        <div style={{ display:'flex', gap:10, marginBottom:16, alignItems:'center', flexWrap:'wrap' }}>
          <div style={{ position:'relative', flex:1, maxWidth:300 }}>
            <Icon name="search" size={14} style={{ position:'absolute', left:11, top:'50%', transform:'translateY(-50%)', color:'var(--sa-text3)', pointerEvents:'none' }}/>
            <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Buscar produto ou SKU..."
              style={{ width:'100%', padding:'8px 12px 8px 34px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
          </div>
          <div style={{ display:'flex', gap:6 }}>
            {PROD_CATS.map(c=>(
              <button key={c} onClick={()=>setCatFilter(c)} style={{ padding:'7px 12px', borderRadius:8, border:`1px solid ${catFilter===c?'var(--sa-primary)':'var(--sa-border)'}`, background:catFilter===c?'var(--sa-primary)':'var(--sa-surface)', color:catFilter===c?'#fff':'var(--sa-text2)', fontSize:12, fontWeight:catFilter===c?600:400, cursor:'pointer', fontFamily:'var(--sa-font-body)', whiteSpace:'nowrap', transition:'all 150ms' }}>{c}</button>
            ))}
          </div>
        </div>

        {/* Table */}
        <Card style={{ padding:0, overflow:'hidden' }}>
          <table style={{ width:'100%', borderCollapse:'collapse' }}>
            <thead><tr>{['Produto','SKU','Categoria','Preço','Custo','Margem','Estoque','Status','Ações'].map(h=><th key={h} style={colSt}>{h}</th>)}</tr></thead>
            <tbody>
              {filtered.map(p=>{
                const mg=p.price>0&&p.cost>0?Math.round(((p.price-p.cost)/p.price)*100):0;
                const lowSt=p.stock<5;
                return (
                  <tr key={p.id} onMouseEnter={e=>e.currentTarget.style.background='var(--sa-surface2)'} onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
                    <td style={cellSt}>
                      <div style={{ display:'flex', alignItems:'center', gap:10 }}>
                        <div style={{ width:34, height:34, borderRadius:9, background:'color-mix(in srgb,var(--sa-secondary) 15%,transparent)', border:'1px solid color-mix(in srgb,var(--sa-secondary) 25%,transparent)', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
                          <Icon name="star" size={15} style={{ color:'var(--sa-secondary)' }}/>
                        </div>
                        <div>
                          <div style={{ fontWeight:700 }}>{p.name}</div>
                          <div style={{ fontSize:11, color:'var(--sa-text3)' }}>{p.desc?.slice(0,35)}{p.desc?.length>35?'…':''}</div>
                        </div>
                      </div>
                    </td>
                    <td style={{ ...cellSt, fontFamily:'monospace', fontSize:12 }}>{p.sku||'—'}</td>
                    <td style={cellSt}><span style={{ fontSize:11, fontWeight:600, padding:'2px 8px', borderRadius:20, background:'var(--sa-surface2)', color:'var(--sa-text2)' }}>{p.category}</span></td>
                    <td style={{ ...cellSt, fontWeight:700 }}>{SA_FMT.currency(p.price)}</td>
                    <td style={{ ...cellSt, color:'var(--sa-text3)' }}>{SA_FMT.currency(p.cost)}</td>
                    <td style={cellSt}><span style={{ fontSize:12, fontWeight:700, color:mg>=40?'#059669':mg>=20?'#d97706':'#dc2626' }}>{mg}%</span></td>
                    <td style={cellSt}>
                      <span style={{ fontWeight:700, color:lowSt?'#ef4444':'var(--sa-text1)' }}>{p.stock} {p.unit}</span>
                      {lowSt&&<span style={{ fontSize:10, color:'#ef4444', display:'block', lineHeight:1.2 }}>⚠ estoque baixo</span>}
                    </td>
                    <td style={cellSt}>
                      <button onClick={()=>doToggle(p.id)} style={{ position:'relative', width:38, height:22, borderRadius:11, border:'none', cursor:'pointer', background:p.active?'var(--sa-primary)':'var(--sa-border)', transition:'background 200ms', padding:0 }}>
                        <div style={{ position:'absolute', top:2, left:p.active?18:2, width:18, height:18, borderRadius:'50%', background:'#fff', transition:'left 200ms' }}/>
                      </button>
                    </td>
                    <td style={cellSt}>
                      <div style={{ display:'flex', gap:5 }}>
                        <Btn size="sm" variant="muted" onClick={()=>openEdit(p)} icon={<Icon name="edit" size={13}/>}>Editar</Btn>
                        <Btn size="sm" variant="ghost" onClick={()=>doDelete(p.id)} icon={<Icon name="trash" size={13}/>}/>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </Card>
      </div>
      <ProductModal open={modal} onClose={()=>setModal(false)} prod={editing} onSave={doSave}/>
    </div>
  );
}

Object.assign(window, { ProductsScreen, INIT_PRODS });
