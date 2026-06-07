// ============================================================
// suaAgenda.pro — Plans & Subscription Screen
// Includes WhatsApp usage dashboard (PRD §3.3)
// ============================================================
const { useState, useEffect } = React;

const PLANS = [
  {
    id: 'starter', name: 'Starter', price: 49.90, color: '#6b7280',
    professionals: '1', whatsapp: 50, sms: 50, maxMsg: 300,
    features: [
      'Calendário completo',
      'Agendamentos ilimitados',
      'Link personalizado',
      'PDV básico',
      '1 relatório (receita)',
      'Notificações automáticas',
      'App mobile (leitura)',
      'LGPD compliance',
    ],
    missing: ['Profissionais extras','Marketing automático','IA insights','Customização de tema'],
  },
  {
    id: 'crescimento', name: 'Crescimento', price: 99.90, color: '#6366f1', popular: true,
    professionals: '2–4', whatsapp: 200, sms: 200, maxMsg: 800,
    features: [
      'Tudo do Starter',
      '2–4 profissionais',
      '3 relatórios completos',
      'Marketing: Aniversariantes',
      'App mobile completo',
      'Google Calendar sync',
      'Customização: Cores + Logo',
      'PDV avançado',
    ],
    missing: ['IA análise completa','Domínio customizado','Multi-unidade'],
  },
  {
    id: 'profissional', name: 'Profissional', price: 199.90, color: 'var(--sa-secondary)',
    professionals: '5–15', whatsapp: 500, sms: 500, maxMsg: 2000,
    features: [
      'Tudo do Crescimento',
      'Todos os 6 relatórios',
      'IA: Análise de padrões',
      'Clientes em risco',
      'Gestão de estoque',
      'Comissões automáticas',
      'MercadoPago integrado',
      'Campanhas sazonais',
    ],
    missing: ['Domínio customizado','Multi-unidade'],
  },
  {
    id: 'enterprise', name: 'Enterprise', price: 399.90, color: '#1a1a1a',
    professionals: 'Ilimitado', whatsapp: -1, sms: -1, maxMsg: -1,
    features: [
      'Tudo do Profissional',
      'Multi-unidade',
      'Domínio customizado',
      'IA Chatbot',
      'API REST',
      'Suporte 24/7',
      'Consultoria (25h/ano)',
      'Dashboard customizado',
    ],
    missing: [],
  },
];

// ── USAGE BAR ─────────────────────────────────────────────────
function UsageBar({ label, used, limit, color, warn }) {
  const [animated, setAnimated] = useState(false);
  useEffect(() => { const t = setTimeout(() => setAnimated(true), 100); return () => clearTimeout(t); }, []);

  const unlimited = limit < 0;
  const pct = unlimited ? 0 : Math.min(used / limit * 100, 100);
  const isWarn = !unlimited && pct > 70;
  const isDanger = !unlimited && pct > 90;
  const barColor = isDanger ? '#ef4444' : isWarn ? '#f59e0b' : color;
  const daysLeft = 8; // mock

  return (
    <div style={{ marginBottom: 24 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: 8 }}>
        <div>
          <span style={{ fontSize: 14, fontWeight: 600, color: 'var(--sa-text1)' }}>{label}</span>
          {isWarn && !isDanger && (
            <span style={{ marginLeft: 8, fontSize: 11, fontWeight: 600, color: '#f59e0b', background: 'rgba(245,158,11,.1)', borderRadius: 20, padding: '2px 8px' }}>⚠ Atenção</span>
          )}
          {isDanger && (
            <span style={{ marginLeft: 8, fontSize: 11, fontWeight: 600, color: '#ef4444', background: 'rgba(239,68,68,.1)', borderRadius: 20, padding: '2px 8px' }}>🔴 Limite próximo</span>
          )}
        </div>
        <span style={{ fontSize: 13, color: 'var(--sa-text3)' }}>
          {unlimited ? <span style={{ color: '#10b981', fontWeight: 600 }}>Ilimitado</span> : `${used} / ${limit} mensagens`}
        </span>
      </div>
      {!unlimited && (
        <>
          <div style={{ height: 10, borderRadius: 5, background: 'var(--sa-surface2)', overflow: 'hidden', border: '1px solid var(--sa-border)' }}>
            <div style={{
              height: '100%', borderRadius: 5, background: barColor,
              width: animated ? `${pct}%` : '0%',
              transition: 'width 900ms cubic-bezier(.4,0,.2,1)',
            }}/>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 5 }}>
            <span style={{ fontSize: 12, color: isDanger ? '#ef4444' : isWarn ? '#f59e0b' : 'var(--sa-text3)' }}>
              {pct.toFixed(0)}% utilizado
            </span>
            <span style={{ fontSize: 12, color: 'var(--sa-text3)' }}>{limit - used} restantes este mês</span>
          </div>
          {isWarn && (
            <div style={{ marginTop: 8, padding: '8px 12px', borderRadius: 8, background: isDanger ? 'rgba(239,68,68,.06)' : 'rgba(245,158,11,.06)', border: `1px solid ${isDanger ? '#ef444430' : '#f59e0b30'}` }}>
              <p style={{ fontSize: 12, color: isDanger ? '#dc2626' : '#d97706', margin: 0, lineHeight: 1.6 }}>
                {isDanger
                  ? `⚠️ Você atingirá o limite em ~${daysLeft} dias. Considere fazer upgrade ou usar SMS.`
                  : `💡 Sugestão: Use SMS (R$ 0,08/msg) para notificações adicionais e preserve seu limite.`}
              </p>
            </div>
          )}
        </>
      )}
    </div>
  );
}

// ── PLAN CARD ─────────────────────────────────────────────────
function PlanCard({ plan, current }) {
  const isCurrentPlan = current === plan.id;
  return (
    <div style={{
      background: isCurrentPlan ? 'color-mix(in srgb, var(--sa-primary) 6%, transparent)' : 'var(--sa-surface)',
      border: `2px solid ${isCurrentPlan ? 'var(--sa-primary)' : plan.popular ? plan.color : 'var(--sa-border)'}`,
      borderRadius: 16, padding: 24, position: 'relative', overflow: 'hidden',
    }}>
      {plan.popular && !isCurrentPlan && (
        <div style={{ position: 'absolute', top: 12, right: -22, background: plan.color, color: '#fff', fontSize: 10, fontWeight: 700, padding: '3px 28px', transform: 'rotate(45deg)', letterSpacing: '1px', textTransform: 'uppercase' }}>POPULAR</div>
      )}
      {isCurrentPlan && (
        <div style={{ position: 'absolute', top: 14, right: 14, fontSize: 10, fontWeight: 700, color: 'var(--sa-primary)', background: 'color-mix(in srgb, var(--sa-primary) 10%, transparent)', borderRadius: 20, padding: '3px 10px', border: '1px solid color-mix(in srgb, var(--sa-primary) 20%, transparent)' }}>ATUAL</div>
      )}

      <div style={{ width: 36, height: 36, borderRadius: 9, background: `${plan.color}18`, display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: 14 }}>
        <Icon name="sparkle" size={18} style={{ color: plan.color }} />
      </div>
      <div style={{ fontFamily: "'Poppins',sans-serif", fontSize: 16, fontWeight: 700, color: 'var(--sa-text1)', marginBottom: 4 }}>{plan.name}</div>
      <div style={{ marginBottom: 16 }}>
        <span style={{ fontFamily: "'Poppins',sans-serif", fontSize: 28, fontWeight: 800, color: plan.color }}>R$ {plan.price.toFixed(2).replace('.', ',')}</span>
        <span style={{ fontSize: 12, color: 'var(--sa-text3)' }}>/mês</span>
      </div>

      <div style={{ fontSize: 12, color: 'var(--sa-text3)', marginBottom: 14 }}>
        <div>👤 {plan.professionals} profissional{plan.professionals !== '1' ? 'is' : ''}</div>
        <div>💬 {plan.whatsapp < 0 ? 'WhatsApp ilimitado' : `${plan.whatsapp} msgs WhatsApp/mês`}</div>
      </div>

      <div style={{ display: 'flex', flexDirection: 'column', gap: 7, marginBottom: 20 }}>
        {plan.features.slice(0, 5).map(f => (
          <div key={f} style={{ display: 'flex', alignItems: 'flex-start', gap: 7, fontSize: 12, color: 'var(--sa-text2)' }}>
            <Icon name="check" size={13} style={{ color: '#10b981', flexShrink: 0, marginTop: 1 }} />{f}
          </div>
        ))}
        {plan.features.length > 5 && (
          <div style={{ fontSize: 11, color: 'var(--sa-text3)', paddingLeft: 20 }}>+{plan.features.length - 5} mais...</div>
        )}
      </div>

      {isCurrentPlan
        ? <Btn variant="secondary" size="sm" fullWidth disabled>Plano Atual</Btn>
        : <Btn size="sm" fullWidth style={{ background: plan.color }} onClick={() => window.SA_TOAST(`Upgrade para ${plan.name} iniciado!`, 'success')}>
            {PLANS.indexOf(plan) > PLANS.findIndex(p => p.id === current) ? 'Fazer Upgrade' : 'Fazer Downgrade'}
          </Btn>
      }
    </div>
  );
}

// ── PLANS SCREEN ──────────────────────────────────────────────
function PlansScreen() {
  const [currentPlan] = useState('crescimento'); // mock

  // Mock usage data for "crescimento" plan
  const USAGE = [
    { label: 'WhatsApp', used: 142, limit: 200, color: '#10b981' },
    { label: 'SMS',      used: 38,  limit: 200, color: '#6366f1' },
    { label: 'E-mail',   used: 312, limit: -1,  color: 'var(--sa-secondary)' },
  ];

  const plan = PLANS.find(p => p.id === currentPlan);
  const billingDate = '05/07/2026';

  return (
    <div style={{ flex: 1, padding: '0 0 40px' }}>
      <AppHeader
        title="Planos & Assinatura"
        subtitle="Gerencie seu plano e uso de mensagens"
      />

      <div style={{ padding: '24px 32px 0' }}>
        {/* Current plan banner */}
        <Card style={{ padding: 24, marginBottom: 24, background: 'color-mix(in srgb, var(--sa-primary) 6%, transparent)', border: '1px solid color-mix(in srgb, var(--sa-primary) 15%, transparent)' }}>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr', gap: 24, alignItems: 'center' }}>
            <div>
              <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--sa-text3)', letterSpacing: '1px', textTransform: 'uppercase', marginBottom: 6 }}>Plano Atual</div>
              <div style={{ fontFamily: "'Poppins',sans-serif", fontSize: 22, fontWeight: 800, color: 'var(--sa-text1)' }}>{plan.name}</div>
              <div style={{ fontSize: 13, color: 'var(--sa-text3)' }}>R$ {plan.price.toFixed(2).replace('.', ',')}/mês</div>
            </div>
            <div>
              <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--sa-text3)', letterSpacing: '1px', textTransform: 'uppercase', marginBottom: 6 }}>Próxima Cobrança</div>
              <div style={{ fontSize: 18, fontWeight: 700, color: 'var(--sa-text1)' }}>{billingDate}</div>
              <div style={{ fontSize: 13, color: 'var(--sa-text3)' }}>Renovação automática</div>
            </div>
            <div>
              <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--sa-text3)', letterSpacing: '1px', textTransform: 'uppercase', marginBottom: 6 }}>Trial</div>
              <div style={{ fontSize: 16, fontWeight: 700, color: '#10b981' }}>✓ Ativo (pago)</div>
              <div style={{ fontSize: 13, color: 'var(--sa-text3)' }}>Cliente desde jan/2026</div>
            </div>
            <div style={{ display: 'flex', gap: 8, flexDirection: 'column' }}>
              <Btn variant="secondary" size="sm" onClick={() => window.SA_TOAST('Gerenciando pagamento...', 'info')} icon={<Icon name="dollar" size={14}/>}>Método de Pagamento</Btn>
              <Btn variant="ghost" size="sm" onClick={() => window.SA_TOAST('Cancelamento requer confirmação', 'warning')}>Cancelar Assinatura</Btn>
            </div>
          </div>
        </Card>

        <div style={{ display: 'grid', gridTemplateColumns: '380px 1fr', gap: 24 }}>
          {/* Usage dashboard */}
          <div>
            <Card style={{ padding: 24, marginBottom: 16 }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
                <h3 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 15, fontWeight: 600, color: 'var(--sa-text1)', margin: 0 }}>Uso de Mensagens — Jun/2026</h3>
                <span style={{ fontSize: 11, color: 'var(--sa-text3)' }}>Renova em 29 dias</span>
              </div>
              {USAGE.map(u => <UsageBar key={u.label} {...u} />)}

              {/* Suggestions */}
              <div style={{ background: 'var(--sa-surface2)', borderRadius: 10, padding: '14px 16px', border: '1px solid var(--sa-border)' }}>
                <div style={{ fontSize: 12, fontWeight: 700, color: 'var(--sa-text1)', marginBottom: 8 }}>💡 Dica para economizar</div>
                <p style={{ fontSize: 12, color: 'var(--sa-text3)', margin: '0 0 10px', lineHeight: 1.6 }}>
                  Use SMS (R$ 0,08/msg) como alternativa ao WhatsApp para lembretes simples. Você tem 162 SMS restantes.
                </p>
                <div style={{ display: 'flex', gap: 8 }}>
                  <Btn size="sm" variant="muted" onClick={() => window.SA_TOAST('Configurando SMS...', 'info')}>Usar SMS</Btn>
                  <Btn size="sm" onClick={() => window.SA_TOAST('Fazendo upgrade...', 'success')}>Upgrade Pro</Btn>
                </div>
              </div>
            </Card>

            {/* Invoice history */}
            <Card style={{ padding: 20 }}>
              <h3 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 14, fontWeight: 600, color: 'var(--sa-text1)', margin: '0 0 14px' }}>Histórico de Faturas</h3>
              {[
                { d: 'Mai/2026', v: 99.90, s: 'paid' },
                { d: 'Abr/2026', v: 99.90, s: 'paid' },
                { d: 'Mar/2026', v: 49.90, s: 'paid' },
              ].map((inv, i) => (
                <div key={i} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 0', borderBottom: i < 2 ? '1px solid var(--sa-border)' : 'none' }}>
                  <div>
                    <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--sa-text1)' }}>{inv.d}</div>
                    <div style={{ fontSize: 11, color: 'var(--sa-text3)' }}>Fatura #{1020 + i}</div>
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <span style={{ fontSize: 13, fontWeight: 700, color: 'var(--sa-text1)' }}>{SA_FMT.currency(inv.v)}</span>
                    <span style={{ fontSize: 11, fontWeight: 600, color: '#059669', background: 'rgba(16,185,129,.1)', borderRadius: 20, padding: '2px 8px' }}>Pago</span>
                    <Icon name="arrowDown" size={13} style={{ color: 'var(--sa-text3)', cursor: 'pointer' }} />
                  </div>
                </div>
              ))}
            </Card>
          </div>

          {/* Plan comparison */}
          <div>
            <h3 style={{ fontFamily: "'Poppins',sans-serif", fontSize: 16, fontWeight: 600, color: 'var(--sa-text1)', margin: '0 0 16px' }}>Comparar Planos</h3>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 14 }}>
              {PLANS.map(p => <PlanCard key={p.id} plan={p} current={currentPlan} />)}
            </div>
            <p style={{ fontSize: 12, color: 'var(--sa-text3)', marginTop: 12, textAlign: 'center' }}>
              Cancelamento sem multa a qualquer momento. Cobrança mensal via cartão de crédito.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { PlansScreen });
