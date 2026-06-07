// ============================================================
// suaAgenda.pro — Site Settings Screen (Configurações do Site Público)
// ============================================================
const { useState, useEffect } = React;

function SiteSettingsScreen() {
  const [tab, setTab] = useState('banner');
  const [saving, setSaving] = useState(false);

  const [banner, setBanner] = useState({
    headline: 'Arte em cada detalhe.',
    subheadline: 'Barbearia premium com os melhores profissionais. Experiência única desde 2018.',
    ctaText: 'Agendar Horário',
    ctaSecondaryText: '(11) 99999-0000',
    showStats: true,
    statsItems: [
      { n:'8+',    l:'Anos de experiência' },
      { n:'2.400', l:'Clientes atendidos'  },
      { n:'4.9★',  l:'Avaliação média'     },
      { n:'98%',   l:'Satisfação'          },
    ],
  });

  const [sections, setSections] = useState({
    showServices: true,
    showPortfolio: true,
    showTeam: true,
    showTestimonials: true,
    showStore: true,
    showBookingCta: true,
    showMap: true,
  });

  const [messages, setMessages] = useState({
    confirmationMsg: 'Agendamento confirmado! Você receberá uma confirmação no WhatsApp em breve.',
    reminderMsg: 'Lembrete: você tem um agendamento amanhã às {hora}. Nos vemos em breve!',
    cancellationMsg: 'Seu agendamento foi cancelado. Sentimos muito, esperamos vê-lo em breve!',
    lgpdMsg: 'Ao agendar, você concorda com nossa Política de Privacidade e autoriza o contato via WhatsApp.',
    welcomePopup: '',
    footerText: 'Powered by suaAgenda.pro',
  });

  const [seo, setSeo] = useState({
    metaTitle: 'Barbearia Style — Agendamento Online',
    metaDesc: 'Agende seu horário na Barbearia Style. Corte, barba e muito mais. Atendimento premium em São Paulo.',
    keywords: 'barbearia, corte, barba, São Paulo, agendamento online',
    ogImage: '',
    googleAnalytics: '',
  });

  const save = () => {
    setSaving(true);
    setTimeout(() => { setSaving(false); window.SA_TOAST('Configurações do site salvas!','success'); }, 700);
  };

  const TABS = [
    { id:'banner',   label:'Banner & Hero',    icon:'eye'     },
    { id:'sections', label:'Seções',           icon:'filter'  },
    { id:'messages', label:'Mensagens',        icon:'phone'   },
    { id:'seo',      label:'SEO & Analytics',  icon:'globe'   },
  ];

  const Toggle = ({ value, onChange }) => (
    <button onClick={()=>onChange(!value)} style={{ width:42, height:24, borderRadius:12, border:'none', cursor:'pointer', background:value?'var(--sa-primary)':'var(--sa-border)', position:'relative', padding:0, transition:'background 200ms', flexShrink:0 }}>
      <div style={{ position:'absolute', top:3, left:value?20:3, width:18, height:18, borderRadius:'50%', background:'#fff', transition:'left 200ms', boxShadow:'0 1px 3px rgba(0,0,0,.2)' }}/>
    </button>
  );

  const SettingRow = ({ label, sub, children }) => (
    <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'12px 0', borderBottom:'1px solid var(--sa-border)' }}>
      <div style={{ flex:1, paddingRight:20 }}>
        <div style={{ fontSize:13, fontWeight:600, color:'var(--sa-text1)' }}>{label}</div>
        {sub&&<div style={{ fontSize:12, color:'var(--sa-text3)', marginTop:2 }}>{sub}</div>}
      </div>
      {children}
    </div>
  );

  return (
    <div style={{ flex:1, padding:'0 0 40px' }}>
      <AppHeader title="Configurações do Site" subtitle="Personalize sua página pública de agendamento"
        actions={<Btn loading={saving} onClick={save} icon={<Icon name="check" size={15}/>}>Salvar Alterações</Btn>}/>

      <div style={{ padding:'20px 32px 0', display:'flex', gap:24 }}>
        {/* Vertical tabs */}
        <div style={{ width:190, flexShrink:0 }}>
          {TABS.map(t=>(
            <button key={t.id} onClick={()=>setTab(t.id)} style={{ display:'flex', alignItems:'center', gap:9, padding:'10px 12px', borderRadius:9, border:'none', cursor:'pointer', width:'100%', textAlign:'left', background:tab===t.id?'color-mix(in srgb,var(--sa-primary) 8%,transparent)':'transparent', color:tab===t.id?'var(--sa-primary)':'var(--sa-text2)', fontWeight:tab===t.id?600:500, fontSize:13, fontFamily:'var(--sa-font-body)', borderLeft:tab===t.id?'2px solid var(--sa-primary)':'2px solid transparent', transition:'all 150ms', marginBottom:2 }}>
              <Icon name={t.icon} size={15}/>{t.label}
            </button>
          ))}
          <div style={{ marginTop:16, padding:'12px', background:'color-mix(in srgb,var(--sa-secondary) 10%,transparent)', borderRadius:10, border:'1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent)' }}>
            <div style={{ fontSize:12, fontWeight:700, color:'var(--sa-secondary)', marginBottom:6 }}>Pré-visualização</div>
            <div style={{ fontSize:11, color:'var(--sa-text3)', lineHeight:1.6 }}>Acesse a página pública pelo botão no rodapé do painel</div>
          </div>
        </div>

        <div style={{ flex:1, minWidth:0 }}>

          {/* ── BANNER ─────────────────────────────────── */}
          {tab==='banner'&&(
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Textos do Hero</h3>
                <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
                  <Inp label="Título principal (H1)" value={banner.headline} onChange={e=>setBanner(b=>({...b,headline:e.target.value}))} placeholder="Ex: Arte em cada detalhe."/>
                  <Txta label="Subtítulo" value={banner.subheadline} onChange={e=>setBanner(b=>({...b,subheadline:e.target.value}))} rows={2} placeholder="Descrição breve do negócio"/>
                  <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
                    <Inp label="Texto do botão principal" value={banner.ctaText} onChange={e=>setBanner(b=>({...b,ctaText:e.target.value}))} placeholder="Ex: Agendar Horário"/>
                    <Inp label="Botão secundário (telefone)" value={banner.ctaSecondaryText} onChange={e=>setBanner(b=>({...b,ctaSecondaryText:e.target.value}))} placeholder="(11) 99999-0000"/>
                  </div>
                </div>
              </Card>

              <Card style={{ padding:22 }}>
                <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:14 }}>
                  <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:0 }}>Barra de Estatísticas</h3>
                  <Toggle value={banner.showStats} onChange={v=>setBanner(b=>({...b,showStats:v}))}/>
                </div>
                {banner.showStats&&(
                  <div style={{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:10 }}>
                    {banner.statsItems.map((s,i)=>(
                      <div key={i} style={{ background:'var(--sa-surface2)', borderRadius:9, padding:'10px 12px', border:'1px solid var(--sa-border)' }}>
                        <input value={s.n} onChange={e=>setBanner(b=>({...b,statsItems:b.statsItems.map((x,j)=>j===i?{...x,n:e.target.value}:x)}))}
                          style={{ width:'100%', fontSize:18, fontWeight:800, color:'var(--sa-secondary)', border:'none', background:'transparent', fontFamily:"var(--sa-font-heading,'Poppins',sans-serif)", outline:'none', marginBottom:4 }}/>
                        <input value={s.l} onChange={e=>setBanner(b=>({...b,statsItems:b.statsItems.map((x,j)=>j===i?{...x,l:e.target.value}:x)}))}
                          style={{ width:'100%', fontSize:11, color:'var(--sa-text3)', border:'none', background:'transparent', fontFamily:'var(--sa-font-body)', outline:'none' }}/>
                      </div>
                    ))}
                  </div>
                )}
              </Card>

              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 14px' }}>Banner Principal</h3>
                <div style={{ border:'2px dashed var(--sa-border)', borderRadius:12, padding:'32px', textAlign:'center', cursor:'pointer', transition:'all 200ms' }}
                  onClick={()=>window.SA_TOAST('Upload de banner em breve!','info')}
                  onMouseEnter={e=>{e.currentTarget.style.borderColor='var(--sa-primary)';e.currentTarget.style.background='color-mix(in srgb,var(--sa-primary) 3%,transparent)';}}
                  onMouseLeave={e=>{e.currentTarget.style.borderColor='var(--sa-border)';e.currentTarget.style.background='transparent';}}>
                  <Icon name="arrowUp" size={28} style={{ color:'var(--sa-text3)', display:'block', margin:'0 auto 10px', opacity:.4 }}/>
                  <div style={{ fontSize:14, fontWeight:600, color:'var(--sa-text2)', marginBottom:4 }}>Fazer upload do banner</div>
                  <div style={{ fontSize:12, color:'var(--sa-text3)' }}>JPG, PNG ou WebP · Recomendado: 1920×800px · Máx. 5MB</div>
                </div>
              </Card>
            </div>
          )}

          {/* ── SECTIONS ───────────────────────────────── */}
          {tab==='sections'&&(
            <Card style={{ padding:22 }}>
              <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Seções Visíveis</h3>
              <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 16px' }}>Controle quais seções aparecem na sua página pública</p>
              {[
                { key:'showServices',     label:'Serviços',          sub:'Grid com todos os serviços e preços' },
                { key:'showPortfolio',    label:'Portfólio',         sub:'Galeria de fotos dos trabalhos' },
                { key:'showTeam',         label:'Equipe',            sub:'Cards dos profissionais com botão de agendamento' },
                { key:'showTestimonials', label:'Depoimentos',       sub:'Carrossel de avaliações de clientes' },
                { key:'showStore',        label:'Loja de Produtos',  sub:'Exibe produtos para compra (requer produtos cadastrados)' },
                { key:'showBookingCta',   label:'Seção de CTA',      sub:'Chamada final para agendamento antes do rodapé' },
                { key:'showMap',          label:'Mapa & Contato',    sub:'Localização e informações de contato no rodapé' },
              ].map(opt=>(
                <SettingRow key={opt.key} label={opt.label} sub={opt.sub}>
                  <Toggle value={sections[opt.key]} onChange={v=>setSections(s=>({...s,[opt.key]:v}))}/>
                </SettingRow>
              ))}
            </Card>
          )}

          {/* ── MESSAGES ───────────────────────────────── */}
          {tab==='messages'&&(
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 4px' }}>Mensagens Automáticas</h3>
                <p style={{ fontSize:13, color:'var(--sa-text3)', margin:'0 0 16px' }}>Use <code style={{ background:'var(--sa-surface2)', padding:'1px 5px', borderRadius:4, fontSize:12 }}>{`{hora}`}</code>, <code style={{ background:'var(--sa-surface2)', padding:'1px 5px', borderRadius:4, fontSize:12 }}>{`{nome}`}</code>, <code style={{ background:'var(--sa-surface2)', padding:'1px 5px', borderRadius:4, fontSize:12 }}>{`{servico}`}</code> como variáveis</p>
                <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
                  <Txta label="Confirmação de agendamento" value={messages.confirmationMsg} onChange={e=>setMessages(m=>({...m,confirmationMsg:e.target.value}))} rows={2}/>
                  <Txta label="Lembrete (WhatsApp/SMS)" value={messages.reminderMsg} onChange={e=>setMessages(m=>({...m,reminderMsg:e.target.value}))} rows={2}/>
                  <Txta label="Cancelamento" value={messages.cancellationMsg} onChange={e=>setMessages(m=>({...m,cancellationMsg:e.target.value}))} rows={2}/>
                </div>
              </Card>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Textos da Página</h3>
                <div style={{ display:'flex', flexDirection:'column', gap:14 }}>
                  <Txta label="Aviso LGPD (formulário de agendamento)" value={messages.lgpdMsg} onChange={e=>setMessages(m=>({...m,lgpdMsg:e.target.value}))} rows={2}/>
                  <Inp label="Texto do rodapé" value={messages.footerText} onChange={e=>setMessages(m=>({...m,footerText:e.target.value}))} placeholder="Powered by suaAgenda.pro"/>
                  <Txta label="Popup de boas-vindas (deixe em branco para desativar)" value={messages.welcomePopup} onChange={e=>setMessages(m=>({...m,welcomePopup:e.target.value}))} rows={3} placeholder="Ex: Bem-vindo! Aproveite 10% de desconto no primeiro agendamento."/>
                </div>
              </Card>
            </div>
          )}

          {/* ── SEO ────────────────────────────────────── */}
          {tab==='seo'&&(
            <div style={{ display:'flex', flexDirection:'column', gap:20 }}>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>SEO & Metadados</h3>
                <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
                  <Inp label="Título da página (meta title)" value={seo.metaTitle} onChange={e=>setSeo(s=>({...s,metaTitle:e.target.value}))} helper={`${seo.metaTitle.length}/60 caracteres — ideal entre 50–60`}/>
                  <Txta label="Descrição (meta description)" value={seo.metaDesc} onChange={e=>setSeo(s=>({...s,metaDesc:e.target.value}))} rows={2} helper={`${seo.metaDesc.length}/160 caracteres — ideal entre 140–160`}/>
                  <Inp label="Palavras-chave (separadas por vírgula)" value={seo.keywords} onChange={e=>setSeo(s=>({...s,keywords:e.target.value}))}/>
                </div>
              </Card>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Imagem de Compartilhamento (OG Image)</h3>
                <div style={{ border:'2px dashed var(--sa-border)', borderRadius:10, padding:'24px', textAlign:'center', cursor:'pointer' }} onClick={()=>window.SA_TOAST('Upload em breve!','info')}>
                  <Icon name="arrowUp" size={22} style={{ color:'var(--sa-text3)', display:'block', margin:'0 auto 8px', opacity:.4 }}/>
                  <div style={{ fontSize:13, color:'var(--sa-text3)' }}>Recomendado: 1200×630px</div>
                </div>
              </Card>
              <Card style={{ padding:22 }}>
                <h3 style={{ fontFamily:"var(--sa-font-heading)", fontSize:15, fontWeight:600, color:'var(--sa-text1)', margin:'0 0 16px' }}>Analytics & Rastreamento</h3>
                <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
                  <Inp label="Google Analytics (ID de medição)" value={seo.googleAnalytics} onChange={e=>setSeo(s=>({...s,googleAnalytics:e.target.value}))} placeholder="G-XXXXXXXXXX"/>
                  <div style={{ padding:'10px 14px', background:'var(--sa-surface2)', borderRadius:9, border:'1px solid var(--sa-border)' }}>
                    <div style={{ fontSize:12, color:'var(--sa-text3)', lineHeight:1.6 }}>⚡ Analytics leve integrado (sem cookies, sem PII) — ativo por padrão em todos os planos</div>
                  </div>
                </div>
              </Card>
            </div>
          )}

        </div>
      </div>
    </div>
  );
}

Object.assign(window, { SiteSettingsScreen });
