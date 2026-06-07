// ============================================================
// suaAgenda.pro — POS Screen (PDV — Ponto de Venda)
// ============================================================
const { useState, useMemo } = React;

const PAYMENT_METHODS_POS = [
  { id:'pix',    label:'Pix',    icon:'check',  color:'#10b981' },
  { id:'credit', label:'Crédito',icon:'dollar', color:'#6366f1' },
  { id:'debit',  label:'Débito', icon:'dollar', color:'#f59e0b' },
  { id:'cash',   label:'Dinheiro',icon:'dollar',color:'#1a1a1a' },
];

function CartItem({ item, onQty, onRemove }) {
  return (
    <div style={{ display:'flex', alignItems:'center', gap:10, padding:'10px 14px', borderBottom:'1px solid var(--sa-border)' }}>
      <div style={{ flex:1, minWidth:0 }}>
        <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' }}>{item.name}</div>
        <div style={{ fontSize:11, color:'var(--sa-text3)' }}>{SA_FMT.currency(item.price)} cada</div>
      </div>
      <div style={{ display:'flex', alignItems:'center', gap:6, flexShrink:0 }}>
        <button onClick={()=>onQty(item.id,-1)} style={{ width:24, height:24, borderRadius:6, border:'1px solid var(--sa-border)', background:'var(--sa-surface2)', cursor:'pointer', display:'flex', alignItems:'center', justifyContent:'center', fontSize:16, color:'var(--sa-text1)', fontWeight:700 }}>-</button>
        <span style={{ fontSize:14, fontWeight:700, color:'var(--sa-text1)', width:24, textAlign:'center' }}>{item.qty}</span>
        <button onClick={()=>onQty(item.id,+1)} style={{ width:24, height:24, borderRadius:6, border:'1px solid var(--sa-border)', background:'var(--sa-surface2)', cursor:'pointer', display:'flex', alignItems:'center', justifyContent:'center', fontSize:16, color:'var(--sa-text1)', fontWeight:700 }}>+</button>
      </div>
      <div style={{ fontSize:14, fontWeight:700, color:'var(--sa-secondary)', width:64, textAlign:'right' }}>{SA_FMT.currency(item.price * item.qty)}</div>
      <button onClick={()=>onRemove(item.id)} style={{ background:'none', border:'none', cursor:'pointer', color:'var(--sa-text3)', padding:2, borderRadius:4, display:'flex', alignItems:'center' }}>
        <Icon name="x" size={14}/>
      </button>
    </div>
  );
}

function POSScreen() {
  const [cart, setCart] = useState([]);
  const [tab, setTab] = useState('products'); // 'products' | 'services'
  const [search, setSearch] = useState('');
  const [method, setMethod] = useState('pix');
  const [client, setClient] = useState('');
  const [discount, setDiscount] = useState(0);
  const [paid, setPaid] = useState(false);
  const [change, setChange] = useState(0);
  const [cashGiven, setCashGiven] = useState('');

  const products = (window.SA_PRODUCTS || INIT_PRODS).filter(p=>p.active);
  const services = window.SA_SERVICES || [];

  const items = tab==='products'
    ? products.filter(p=>!search||p.name.toLowerCase().includes(search.toLowerCase()))
    : services.filter(s=>!search||s.name.toLowerCase().includes(search.toLowerCase()));

  const addToCart = (item, isService=false) => {
    setCart(prev => {
      const key = `${isService?'svc':'prd'}-${item.id}`;
      const existing = prev.find(c=>c.id===key);
      if (existing) return prev.map(c=>c.id===key?{...c,qty:c.qty+1}:c);
      return [...prev, { id:key, name:item.name, price:isService?item.price:item.price, qty:1, type:isService?'service':'product' }];
    });
    window.SA_TOAST(`${item.name} adicionado`,'success');
  };

  const adjustQty = (id, delta) => setCart(prev => prev.map(c=>c.id===id?{...c,qty:Math.max(0,c.qty+delta)}:c).filter(c=>c.qty>0));
  const removeItem = (id) => setCart(prev=>prev.filter(c=>c.id!==id));
  const clearCart = () => { setCart([]); setDiscount(0); setClient(''); setCashGiven(''); setPaid(false); };

  const subtotal  = cart.reduce((s,c)=>s+c.price*c.qty,0);
  const discAmt   = Math.round(subtotal*discount/100*100)/100;
  const total     = Math.max(subtotal-discAmt,0);
  const chg       = Math.max((parseFloat(cashGiven)||0)-total,0);

  const finalize = () => {
    if (cart.length===0) return window.SA_TOAST('Adicione itens ao carrinho','error');
    setPaid(true);
    if (method==='cash') setChange(chg);
    window.SA_TOAST('Venda finalizada com sucesso!','success');
  };

  return (
    <div style={{ flex:1, display:'flex', flexDirection:'column', height:'100%', overflow:'hidden' }}>
      <AppHeader title="PDV — Ponto de Venda" subtitle="Registre vendas de produtos e serviços"/>
      <div style={{ display:'grid', gridTemplateColumns:'1fr 360px', gap:0, flex:1, overflow:'hidden', padding:'16px 32px 24px' }}>

        {/* Left: catalog */}
        <div style={{ display:'flex', flexDirection:'column', gap:12, overflow:'hidden', paddingRight:20 }}>
          {/* Search + tabs */}
          <div style={{ display:'flex', gap:10 }}>
            <div style={{ position:'relative', flex:1 }}>
              <Icon name="search" size={14} style={{ position:'absolute', left:11, top:'50%', transform:'translateY(-50%)', color:'var(--sa-text3)', pointerEvents:'none' }}/>
              <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Buscar produto ou serviço..."
                style={{ width:'100%', padding:'9px 12px 9px 34px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none', boxSizing:'border-box' }}/>
            </div>
            <div style={{ display:'flex', background:'var(--sa-surface2)', border:'1px solid var(--sa-border)', borderRadius:8, overflow:'hidden' }}>
              {[['products','Produtos'],['services','Serviços']].map(([v,l])=>(
                <button key={v} onClick={()=>setTab(v)} style={{ padding:'9px 16px', border:'none', borderRight:v==='products'?'1px solid var(--sa-border)':'none', background:tab===v?'var(--sa-primary)':'transparent', color:tab===v?'#fff':'var(--sa-text2)', cursor:'pointer', fontSize:13, fontWeight:tab===v?600:400, fontFamily:'var(--sa-font-body)', transition:'all 150ms' }}>{l}</button>
              ))}
            </div>
          </div>

          {/* Item grid */}
          <div style={{ flex:1, overflowY:'auto', display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:10, alignContent:'start' }}>
            {items.map(item => {
              const isSvc = tab==='services';
              const stock = isSvc ? null : item.stock;
              const inCart = cart.find(c=>c.id===`${isSvc?'svc':'prd'}-${item.id}`);
              return (
                <button key={item.id} onClick={()=>addToCart(item,isSvc)}
                  disabled={!isSvc&&stock===0}
                  style={{ background:inCart?'color-mix(in srgb,var(--sa-primary) 8%,transparent)':'var(--sa-surface)', border:`1.5px solid ${inCart?'var(--sa-primary)':'var(--sa-border)'}`, borderRadius:12, padding:14, cursor:stock===0?'not-allowed':'pointer', textAlign:'left', display:'flex', flexDirection:'column', gap:6, transition:'all 160ms', opacity:stock===0?.5:1, fontFamily:'var(--sa-font-body)' }}>
                  <div style={{ width:40, height:40, borderRadius:10, background:isSvc?`${item.color||'#888'}18`:'color-mix(in srgb,var(--sa-secondary) 15%,transparent)', display:'flex', alignItems:'center', justifyContent:'center' }}>
                    <Icon name={isSvc?'scissors':'star'} size={18} style={{ color:isSvc?item.color||'var(--sa-primary)':'var(--sa-secondary)' }}/>
                  </div>
                  <div style={{ fontSize:13, fontWeight:700, color:'var(--sa-text1)', lineHeight:1.3 }}>{item.name}</div>
                  {!isSvc&&<div style={{ fontSize:11, color:stock<5?'#ef4444':'var(--sa-text3)' }}>{stock} em estoque</div>}
                  {isSvc&&<div style={{ fontSize:11, color:'var(--sa-text3)' }}>{item.duration}min</div>}
                  <div style={{ fontSize:15, fontWeight:800, color:'var(--sa-secondary)', fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)" }}>{SA_FMT.currency(item.price)}</div>
                  {inCart&&<div style={{ position:'absolute', top:8, right:8, width:20, height:20, borderRadius:'50%', background:'var(--sa-primary)', display:'flex', alignItems:'center', justifyContent:'center' }}>
                    <span style={{ fontSize:11, fontWeight:800, color:'#fff' }}>{inCart.qty}</span>
                  </div>}
                </button>
              );
            })}
            {items.length===0&&<div style={{ gridColumn:'1/-1', textAlign:'center', padding:40, color:'var(--sa-text3)', fontSize:13 }}>Nenhum item encontrado</div>}
          </div>
        </div>

        {/* Right: cart + payment */}
        <div style={{ display:'flex', flexDirection:'column', background:'var(--sa-surface)', border:'1px solid var(--sa-border)', borderRadius:16, overflow:'hidden' }}>
          {/* Cart header */}
          <div style={{ padding:'16px 16px 12px', borderBottom:'1px solid var(--sa-border)', display:'flex', justifyContent:'space-between', alignItems:'center' }}>
            <div>
              <div style={{ fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", fontSize:15, fontWeight:700, color:'var(--sa-text1)' }}>Carrinho</div>
              <div style={{ fontSize:12, color:'var(--sa-text3)' }}>{cart.reduce((s,c)=>s+c.qty,0)} ite{cart.reduce((s,c)=>s+c.qty,0)===1?'m':'ns'}</div>
            </div>
            {cart.length>0&&<Btn variant="ghost" size="sm" onClick={clearCart} icon={<Icon name="trash" size={13}/>}>Limpar</Btn>}
          </div>

          {/* Client select */}
          <div style={{ padding:'10px 14px', borderBottom:'1px solid var(--sa-border)' }}>
            <select value={client} onChange={e=>setClient(e.target.value)}
              style={{ width:'100%', fontSize:13, padding:'8px 10px', border:'1px solid var(--sa-border)', borderRadius:8, background:'var(--sa-surface2)', color:client?'var(--sa-text1)':'var(--sa-text3)', fontFamily:'var(--sa-font-body)', outline:'none' }}>
              <option value="">Selecionar cliente (opcional)</option>
              {SA_CLIENTS.map(c=><option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </div>

          {/* Cart items */}
          <div style={{ flex:1, overflowY:'auto' }}>
            {cart.length===0
              ? <div style={{ padding:40, textAlign:'center', color:'var(--sa-text3)', fontSize:13 }}>
                  <Icon name="filter" size={32} style={{ margin:'0 auto 12px', display:'block', opacity:.25 }}/>
                  Nenhum item no carrinho
                </div>
              : cart.map(item=><CartItem key={item.id} item={item} onQty={adjustQty} onRemove={removeItem}/>)
            }
          </div>

          {/* Totals + discount */}
          <div style={{ borderTop:'1px solid var(--sa-border)', padding:'12px 14px' }}>
            <div style={{ display:'flex', alignItems:'center', gap:8, marginBottom:10 }}>
              <span style={{ fontSize:13, color:'var(--sa-text2)', flex:1 }}>Desconto (%)</span>
              <input type="number" value={discount} onChange={e=>setDiscount(Math.min(100,Math.max(0,Number(e.target.value))))} min={0} max={100}
                style={{ width:64, padding:'5px 8px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:7, background:'var(--sa-surface)', color:'var(--sa-text1)', textAlign:'center', fontFamily:'var(--sa-font-body)', outline:'none' }}/>
            </div>
            {[
              { l:'Subtotal',  v:SA_FMT.currency(subtotal), bold:false },
              discount>0&&{ l:`Desconto (${discount}%)`, v:`- ${SA_FMT.currency(discAmt)}`, bold:false, red:true },
              { l:'Total',     v:SA_FMT.currency(total),    bold:true },
            ].filter(Boolean).map(row=>(
              <div key={row.l} style={{ display:'flex', justifyContent:'space-between', marginBottom:4 }}>
                <span style={{ fontSize:13, color:'var(--sa-text2)', fontWeight:row.bold?600:400 }}>{row.l}</span>
                <span style={{ fontSize:row.bold?17:13, fontWeight:row.bold?800:500, color:row.red?'#ef4444':row.bold?'var(--sa-secondary)':'var(--sa-text1)', fontFamily:row.bold?"var(--sa-font-heading,'Poppins',sans-serif)":undefined }}>{row.v}</span>
              </div>
            ))}
          </div>

          {/* Payment method */}
          <div style={{ borderTop:'1px solid var(--sa-border)', padding:'10px 14px' }}>
            <div style={{ fontSize:11, fontWeight:700, color:'var(--sa-text3)', textTransform:'uppercase', letterSpacing:'.5px', marginBottom:8 }}>Forma de Pagamento</div>
            <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:6 }}>
              {PAYMENT_METHODS_POS.map(m=>(
                <button key={m.id} onClick={()=>setMethod(m.id)} style={{ padding:'7px 4px', borderRadius:8, border:`1.5px solid ${method===m.id?m.color:'var(--sa-border)'}`, background:method===m.id?`${m.color}12`:'transparent', cursor:'pointer', fontSize:11, fontWeight:method===m.id?700:500, color:method===m.id?m.color:'var(--sa-text2)', fontFamily:'var(--sa-font-body)', transition:'all 150ms' }}>{m.label}</button>
              ))}
            </div>
            {method==='cash'&&(
              <div style={{ marginTop:10, display:'flex', alignItems:'center', gap:8 }}>
                <span style={{ fontSize:12, color:'var(--sa-text3)', whiteSpace:'nowrap' }}>Valor recebido:</span>
                <input type="number" value={cashGiven} onChange={e=>setCashGiven(e.target.value)} placeholder="0,00"
                  style={{ flex:1, padding:'6px 10px', fontSize:13, border:'1px solid var(--sa-border)', borderRadius:7, background:'var(--sa-surface)', color:'var(--sa-text1)', fontFamily:'var(--sa-font-body)', outline:'none' }}/>
                {cashGiven&&parseFloat(cashGiven)>=total&&<span style={{ fontSize:12, fontWeight:700, color:'#10b981', whiteSpace:'nowrap' }}>Troco: {SA_FMT.currency(chg)}</span>}
              </div>
            )}
          </div>

          {/* Finalize */}
          <div style={{ padding:'10px 14px 16px' }}>
            {paid
              ? <div style={{ textAlign:'center', padding:'16px', background:'rgba(16,185,129,.08)', borderRadius:12, border:'1px solid rgba(16,185,129,.2)' }}>
                  <Icon name="check" size={28} style={{ color:'#10b981', display:'block', margin:'0 auto 8px' }}/>
                  <div style={{ fontSize:14, fontWeight:700, color:'#059669' }}>Venda Finalizada!</div>
                  {method==='cash'&&change>0&&<div style={{ fontSize:13, color:'var(--sa-text2)', marginTop:4 }}>Troco: {SA_FMT.currency(change)}</div>}
                  <Btn variant="secondary" size="sm" fullWidth onClick={clearCart} style={{ marginTop:12 }}>Nova Venda</Btn>
                </div>
              : <Btn size="lg" fullWidth disabled={cart.length===0} onClick={finalize} icon={<Icon name="check" size={16}/>}>
                  Finalizar Venda · {SA_FMT.currency(total)}
                </Btn>
            }
          </div>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { POSScreen });
