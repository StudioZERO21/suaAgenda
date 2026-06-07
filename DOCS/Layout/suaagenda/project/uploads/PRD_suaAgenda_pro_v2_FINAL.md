# PRD - suaAgenda.pro
## Sistema SaaS de Agendamento Multi-Empresa

**Versão:** 2.0 (Revisado)  
**Data:** 2026  
**Stakeholders:** Desenvolvedor, Vendedor, Cliente  
**Produto:** suaAgenda.pro  
**Status:** PRONTO PARA DESENVOLVIMENTO

---

## 1. VISÃO DO PRODUTO

### 1.1 Declaração de Visão

Ser a **plataforma de agendamento mais segura, inteligente e acessível do Brasil** para pequenas e médias empresas de serviços, oferecendo automação completa, conformidade LGPD garantida e inteligência artificial incluída desde o primeiro dia.

### 1.2 Missão

Permitir que empresas de serviços (salões, clínicas, barbearias, etc.) e profissionais autônomos gerenciem seus agendamentos de forma automática, segura e lucrativa, reduzindo custos operacionais em 40-60% e aumentando a taxa de conversão de clientes.

### 1.3 Proposta de Valor

```
Para o Dono da Empresa:
├─ Economia de 50% em custo de notificações (vs Gendo)
├─ LGPD compliance certificado (sem risco legal)
├─ IA analisando padrões de clientes 24/7
├─ Relatórios inteligentes com insights automáticos
└─ Customização completa da marca

Para o Profissional:
├─ Controle total da agenda em tempo real
├─ Alertas inteligentes de clientes em risco
├─ Comissões calculadas automaticamente
└─ App mobile próprio (React Native)

Para o Cliente (Consumidor):
├─ Agendamento fácil via WhatsApp ou site
├─ Confirmação e lembretes automáticos
├─ Histórico completo de serviços
└─ Avaliação e recomendações personalizadas
```

---

## 2. ESCOPO DO PRODUTO

### 2.1 Nichos-Alvo (MVP)

```
Primários (Foco Inicial):
✅ Salões de Beleza
✅ Barbearias
✅ Clínicas Estéticas
✅ Profissionais Autônomos (Personal, Maquiadora, Tatuador)

Secundários (Fase 2):
✅ Clínicas Odontológicas
✅ Clínicas de Saúde
✅ Pet Shops
✅ Manicure/Nail Designers
✅ Estúdios de Tatuagem
```

### 2.2 Personas

#### Persona 1: Carolina (Dona de Salão - PME)
- Idade: 38 anos
- Negócio: Salão de beleza com 5 profissionais
- Faturamento: R$ 25-40k/mês
- Problema: Agenda no papel, lembretes manuais, multa por não comparecimento
- Objetivo: Automatizar sem gastar muito
- Tech: Básica (WhatsApp, Instagram)

#### Persona 2: João (Profissional Autônomo)
- Idade: 28 anos
- Negócio: Personal trainer, 10 clientes
- Faturamento: R$ 4-8k/mês
- Problema: Sem sistema, cliente com dúvida não sabe ligar
- Objetivo: Parecer profissional, crescer com tecnologia
- Tech: Intermediária (Instagram, Google)

#### Persona 3: Marina (Gerente de Clínica)
- Idade: 42 anos
- Negócio: Clínica de estética, 8 profissionais
- Faturamento: R$ 60-80k/mês
- Problema: Conformidade LGPD, controle financeiro, comissões
- Objetivo: Compliance + Relatórios + Controle
- Tech: Avançada (Excel, sistemas)

---

## 3. FUNCIONALIDADES PRINCIPAIS

### 3.1 Autenticação e Multi-Tenancy

```
Autenticação (MVP):
├─ Email + Senha
├─ OAuth 2.0 (Google)
├─ Validação email (confirmação)
└─ Recuperação de senha

Segurança:
├─ Hashing Bcrypt (passwords)
├─ HTTPS/SSL (todos endpoints)
├─ Rate limiting (API)
├─ CORS configurado por tenant
└─ Tokens JWT (expiração 24h)

Multi-Tenancy:
├─ Isolamento de dados por company_id
├─ Middleware: TenantMiddleware
├─ Cache isolado por tenant
├─ Logs auditáveis por empresa
```

### 3.2 Agendamento (MVP)

#### Core Features:
```
✅ Calendário visual (dia, semana, mês)
✅ Agendamento por serviço (com duração configurável)
✅ Slots disponíveis conforme profissional
✅ Lock temporal (5min) para evitar double booking
✅ Status de agendamento: PENDENTE → CONFIRMADO → FINALIZADO
✅ Cancelamento com motivo registrado
✅ Histórico completo de agendamentos
✅ Busca e filtro por cliente, profissional, data, status
```

#### Reserva Inteligente:
```
FLUXO:
1. Cliente seleciona data/hora → Sistema bloqueia no Redis (5min)
2. Valida disponibilidade em tempo real
3. Se expirar: Libera slot automaticamente
4. Evita double booking mesmo com múltiplas requisições

IMPLEMENTAÇÃO:
- Redis: key = "slot:{date}:{time}:{professional_id}:lock"
- TTL: 5 minutos
- Check: Antes de confirmar, valida Redis + Database
```

### 3.3 WhatsApp - Modelo Híbrido (CLIENT-INITIATED)

#### Fluxo Completo:

```
TIPO 1: AGENDAMENTO INICIAL
┌─────────────────────────────────────────┐
│ 1. Cliente acessa site da empresa       │
│ 2. Clica "Agendar via WhatsApp"        │
│ 3. Abre WhatsApp da empresa com:       │
│    "Olá! Gostaria de agendar..."       │
│ 4. Cliente ENVIA PRIMEIRA MENSAGEM     │
│ 5. Sistema registra: AGENDAMENTO PENDENTE│
│ 6. Empresa responde manualmente         │
│ 7. Após resposta: Sistema envia:       │
│    "Agendamento confirmado para..."    │
│ 8. Status: CONFIRMADO                   │
└─────────────────────────────────────────┘

TIPO 2: LEMBRETE AUTOMÁTICO
┌─────────────────────────────────────────┐
│ 24h antes do agendamento                │
│ Sistema envia automaticamente:           │
│ "Não esqueça seu agendamento amanhã..." │
│ CUSTO: R$ 0,25                         │
└─────────────────────────────────────────┘

TIPO 3: CANCELAMENTO
┌─────────────────────────────────────────┐
│ Se cliente cancelar via site:           │
│ Sistema notifica empresa:                │
│ "Cliente X cancelou agendamento para..." │
│ CUSTO: R$ 0,25                         │
└─────────────────────────────────────────┘
```

#### Controle de Limite de API (CRÍTICO)

```
OBJETIVO: Evitar gastos descontrolados com Twilio

ESTRUTURA POR PLANO:

STARTER (R$ 49,90/mês):
├─ Limite: 50 mensagens WhatsApp/mês
├─ SMS incluído: 50 mensagens/mês
├─ Overage: R$ 0,15/msg WhatsApp (limite máx: 300)
├─ Overage: R$ 0,08/msg SMS
└─ Implementação: Bloqueia envio após limite

CRESCIMENTO (R$ 99,90/mês):
├─ Limite: 200 mensagens WhatsApp/mês
├─ SMS incluído: 200 mensagens/mês
├─ Overage: R$ 0,12/msg WhatsApp (limite máx: 800)
├─ Overage: R$ 0,07/msg SMS
└─ Implementação: Alerta antes de bloquear

PROFISSIONAL (R$ 199,90/mês):
├─ Limite: 500 mensagens WhatsApp/mês
├─ SMS incluído: 500 mensagens/mês
├─ Overage: R$ 0,10/msg WhatsApp (limite máx: 2.000)
├─ Overage: R$ 0,05/msg SMS
└─ Implementação: Permissivo, raramente bloqueia

ENTERPRISE:
├─ Ilimitado WhatsApp
├─ Ilimitado SMS
└─ Sem bloqueios

CÓDIGO IMPLEMENTAÇÃO:

// services/WhatsAppLimitService.php
class WhatsAppLimitService {
    public function checkQuota($companyId, $month = null) {
        $month = $month ?? now()->format('Y-m');
        $plan = Company::find($companyId)->plan;
        
        $limits = [
            'starter' => 50,
            'crescimento' => 200,
            'profissional' => 500,
            'enterprise' => PHP_INT_MAX,
        ];
        
        $used = WhatsAppLog::where('company_id', $companyId)
            ->whereMonth('created_at', substr($month, -2))
            ->whereYear('created_at', substr($month, 0, 4))
            ->count();
        
        $limit = $limits[$plan] ?? 50;
        
        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'exceeded' => $used > $limit,
        ];
    }
    
    public function sendMessage($companyId, $phone, $message) {
        $quota = $this->checkQuota($companyId);
        
        if ($quota['exceeded']) {
            throw new QuotaExceededException(
                "Limite de mensagens atingido. " .
                "Upgrade para continuar ou use SMS como alternativa."
            );
        }
        
        // Envia via Twilio
        $result = TwilioService::send($phone, $message);
        
        // Log para contador
        WhatsAppLog::create([
            'company_id' => $companyId,
            'phone' => $phone,
            'message' => $message,
            'status' => 'sent',
        ]);
        
        return $result;
    }
}
```

#### Dashboard de Uso (Cliente Vê)

```
┌──────────────────────────────────────────────────────┐
│ 📊 MENSAGENS - MARÇO 2026                            │
├──────────────────────────────────────────────────────┤
│                                                       │
│ WhatsApp:                                             │
│ ████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│ 75 / 200 mensagens (37%)                             │
│                                                       │
│ SMS:                                                  │
│ ██░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│ 10 / 200 mensagens (5%)                              │
│                                                       │
│ Email:                                                │
│ ██████████████████████████░░░░░░░░░░░░░░░░░░░░░░░░ │
│ 145 / Ilimitado                                       │
│                                                       │
│ ⚠️ Aviso:                                             │
│ "Você atingirá o limite de WhatsApp em ~4 dias"     │
│                                                       │
│ 💡 Sugestão:                                          │
│ "Considere usar SMS para notificações adicionais"   │
│                                                       │
│ [UPGRADE PARA PRO] [USAR SMS]                        │
│                                                       │
└──────────────────────────────────────────────────────┘
```

### 3.4 Link de Agendamento Personalizado

```
Funcionalidade:
├─ URL customizada: suaagenda.pro/carolina (domínio compartilhado)
├─ White-label: Domínio próprio opcional (Enterprise)
├─ Logo + cores: Customização básica (Crescimento+)
├─ Social preview: OG tags customizadas
└─ Analytics: Cliques, conversões, origem

Implementação:
├─ Slug gerado automaticamente (slug único por empresa)
├─ Estatísticas: Rastreadas em link_visits table
├─ QR Code gerado automaticamente (para impressos)
└─ Shortlink automático para WhatsApp
```

### 3.5 PDV (Ponto de Venda) Básico

```
Funcionalidade (MVP):
├─ Registrar pagamento de agendamento
├─ Métodos: Dinheiro, Débito, Crédito, Pix, Outros
├─ Integração ASAAS/MercadoPago (opcional)
├─ Histórico de pagamentos
├─ Relatório por período

Futuro (Fase 2):
├─ Vendas adicionais (produtos)
├─ Descontos por profissional
├─ Comissões automáticas
└─ Fechamento de caixa
```

### 3.6 Relatórios (Por Plano)

```
STARTER (1 relatório):
├─ Receita por período

CRESCIMENTO (3 relatórios):
├─ Receita por período
├─ Clientes (quantidade, repeat)
└─ Profissionais (ranking, desempenho)

PROFISSIONAL (Todos 6):
├─ Receita por período
├─ Clientes (segmentação, churn)
├─ Profissionais (ranking, comissões)
├─ Agendamentos (horário de pico, no-show)
├─ Marketing (campanhas, conversão)
└─ Financeiro (lucro, custos)

ENTERPRISE:
├─ Tudo do Profissional +
├─ IA Insights (automático)
├─ Dashboards customizados
└─ Exportação (PDF, Excel)

Implementação:
├─ Cache com Redis (reutilização)
├─ Jobs para geração async
├─ Filtros avançados (data, profissional, etc)
└─ Gráficos com Chart.js / Recharts
```

### 3.7 App Mobile (React Native)

```
STARTER:
├─ Visualizar agenda (leitura)
├─ Notificações de agendamentos
└─ Histórico de clientes

CRESCIMENTO/PROFISSIONAL:
├─ Editar agenda (adicionar/remover)
├─ Dashboard em tempo real
├─ Relatório rápido
├─ Notificações customizadas
└─ Offline mode (básico)

ENTERPRISE:
├─ Tudo acima +
├─ Gestão de comissões
├─ IA insights (mobile)
└─ Integrações
```

### 3.8 IA e Automações (Fase 2)

```
Análise de Padrões:
├─ Clientes em risco (não agendaram em 60 dias)
├─ Cross-sell (sugestões baseadas em histórico)
├─ Horários de pico (mapa de calor)
└─ Previsão de no-show

Marketing Automático (Fase 2):
├─ Aniversariantes (SMS/WhatsApp)
├─ Clientes em risco (email de resgate)
├─ Campanhas sazonais (Black Friday, etc)
└─ Funil de venda (chatbot)

Disponibilidade:
├─ Incluído ilimitado: Plano Profissional+
├─ Não disponível: Starter/Crescimento
└─ Upgrade path claro
```

### 3.9 Dashboard SuperAdmin

#### Gestão de Empresas:
```
✅ Cadastro completo de empresa
✅ Dados bancários (para repasse)
✅ Status (ativa, suspensa, cancelada)
✅ Histórico de pagamentos
✅ Limite de uso (profissionais, agendamentos)
✅ Upgrade/Downgrade de plano
✅ Trial status (ativo, convertido, expirado)
✅ Métricas: MRR, churn, LTV
```

#### Relatórios de Negócio:
```
✅ Receita MRR (Monthly Recurring Revenue)
✅ Churn rate (empresas canceladas)
✅ LTV (Lifetime Value)
✅ CAC (Customer Acquisition Cost)
✅ Segmentação por nicho
✅ Taxa de crescimento por plano
✅ Heatmap: qual hora mais contrata
✅ Distribuição de planos
```

#### APIs e Integrações:
```
✅ Gerenciar chaves de API
✅ Logs de integrações
✅ Webhooks por evento
✅ Rate limit por empresa
✅ Estatísticas de consumo
```

---

## 4. MONETIZAÇÃO

### 4.1 Modelo de Negócio

```
Subscription SaaS:
├─ Recorrente mensal
├─ Cobrança via Stripe/ASAAS
├─ Trial 7 dias (sem cartão no início, obrigatório ao final)
├─ Cancelamento sem multa
└─ Overage automático em limite de mensagens
```

### 4.2 Tabela de Preços (FINAL)

```
┌──────────────────────────────────────────────────────┐
│ 🆕 TRIAL 7 DIAS (Gratuito)                           │
├──────────────────────────────────────────────────────┤
│ Acesso: Plano STARTER completo                       │
│ Duração: 7 dias corridos                             │
│ Cartão: Obrigatório no final (não cobra antecipado)  │
│ Conversão esperada: 15-25%                           │
│ Cancelar a qualquer momento                          │
│                                                       │
│ Email reminders: Dia 4, 6, 7                         │
│ Bloqueio automático no dia 8 (sem assinatura)        │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ 💼 PLANO STARTER - R$ 49,90/mês                      │
├──────────────────────────────────────────────────────┤
│ Profissionais: 1                                      │
│ Agendamentos: Ilimitado                              │
│ WhatsApp incluído: 50 mensagens/mês                 │
│ SMS incluído: 50 mensagens/mês                       │
│ Email: Ilimitado                                     │
│ Limite máximo: 300 mensagens/mês (bloqueia)         │
│                                                       │
│ Features:                                             │
│ ✅ Calendário (dia, semana, mês)                    │
│ ✅ Agendamento simples                               │
│ ✅ Link personalizado (suaagenda.pro/nome)           │
│ ✅ PDV básico                                         │
│ ✅ 1 relatório (receita)                             │
│ ✅ Notificações automáticas (WhatsApp/SMS)           │
│ ✅ App mobile (leitura)                              │
│ ✅ LGPD compliance                                   │
│ ❌ Profissionais extras                              │
│ ❌ Marketing automático                              │
│ ❌ IA insights                                       │
│ ❌ Customização de tema                              │
│                                                       │
│ Margem: 65% (viável, upgrade esperado em 3-6 meses)│
│ Churn esperado: 10-15%/mês (para Crescimento)       │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ 🚀 PLANO CRESCIMENTO - R$ 99,90/mês (NOVO)          │
├──────────────────────────────────────────────────────┤
│ Profissionais: 2 a 4                                 │
│ Agendamentos: Ilimitado                              │
│ WhatsApp incluído: 200 mensagens/mês                │
│ SMS incluído: 200 mensagens/mês                      │
│ Email: Ilimitado                                     │
│ Limite máximo: 800 mensagens/mês (alerta antes)      │
│                                                       │
│ Features (Tudo do Starter +):                        │
│ ✅ 2-4 profissionais                                 │
│ ✅ Relatórios: Receita, Clientes, Profissionais    │
│ ✅ Marketing automático: Aniversariantes, resgate    │
│ ✅ App mobile: Completo (edição)                     │
│ ✅ Google Calendar: Sync 2-way                       │
│ ✅ Customização: Cores + Logo                        │
│ ✅ PDV avançado                                      │
│ ❌ IA análise completa                               │
│ ❌ Domínio customizado                               │
│ ❌ Multi-unidade                                     │
│                                                       │
│ Margem: 62% ✅ (ALVO MÍNIMO)                         │
│ Churn esperado: 8%/mês                              │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ 👑 PLANO PROFISSIONAL - R$ 199,90/mês               │
├──────────────────────────────────────────────────────┤
│ Profissionais: 5 a 15                                │
│ Agendamentos: Ilimitado                              │
│ WhatsApp incluído: 500 mensagens/mês                │
│ SMS incluído: 500 mensagens/mês                      │
│ Email: Ilimitado                                     │
│ Limite máximo: 2.000 mensagens/mês (permissivo)      │
│                                                       │
│ Features (Tudo do Crescimento +):                    │
│ ✅ Todos 6 relatórios (filtros avançados)           │
│ ✅ IA: Análise de padrões + Recomendações           │
│ ✅ Clientes em risco (automático)                    │
│ ✅ Gestão de estoque                                 │
│ ✅ Comissões automáticas                             │
│ ✅ MercadoPago integrado                             │
│ ✅ Campanhas sazonais                                │
│ ❌ Domínio customizado                               │
│ ❌ Multi-unidade (franquias)                         │
│ ❌ Support prioritário                               │
│                                                       │
│ Margem: 76% ✅✅ (EXCELENTE)                        │
│ Churn esperado: 4%/mês                              │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ 🏢 PLANO ENTERPRISE - Negociado                      │
├──────────────────────────────────────────────────────┤
│ Base: R$ 399,90/mês                                 │
│ Profissionais: Ilimitados                            │
│ Mensagens: Ilimitadas (WhatsApp + SMS)              │
│                                                       │
│ Features (Tudo acima +):                             │
│ ✅ Multi-unidade: +R$ 149/unidade/mês               │
│ ✅ Domínio customizado (incluído)                   │
│ ✅ IA Chatbot (atender clientes)                    │
│ ✅ API REST (integrações custom)                    │
│ ✅ Support 24/7 (WhatsApp direto)                   │
│ ✅ Consultoria de otimização (25h/ano)              │
│ ✅ Dashboard customizado                             │
│ ✅ Integrações avançadas (Zapier, Make)             │
│                                                       │
│ Valores Adicionais (a la carte):                     │
│ ├─ Integrações custom: R$ 500-2.000 (one-time)     │
│ ├─ Consultoria extra: R$ 200/hora                   │
│ └─ Contrato anual: 15-20% desconto                  │
│                                                       │
│ Margem: 56%+ (cliente premium, menos suporte)       │
│ Churn esperado: 2%/mês                              │
└──────────────────────────────────────────────────────┘
```

### 4.3 Estrutura de Custos (Viabilidade)

```
CUSTOS FIXOS MENSAIS (Inicial):
├─ Hospedagem: R$ 500 (você já tem - adicional se crescer)
├─ Email (SendGrid): R$ 100
├─ Observability (Sentry): R$ 100
└─ TOTAL: R$ 700/mês

CUSTOS VARIÁVEIS POR CLIENTE:
├─ Twilio WhatsApp: R$ 0,25/msg (conforme uso)
├─ SMS ASAAS: R$ 0,10-0,30/SMS (fallback)
├─ Email: ~R$ 0,01/email (negligible)
└─ Payment processing: 2,5% da receita

EXEMPLO: 100 CLIENTES (Distribuição Realista)
Receita mensal: R$ 10.491
├─ Starter (40): R$ 1.996
├─ Crescimento (35): R$ 3.496
├─ Profissional (15): R$ 2.999
└─ Enterprise (5): R$ 1.999

Custos mensais: R$ 1.915
├─ Fixo: R$ 700
├─ WhatsApp: R$ 1.215
└─ Payment: R$ 262

LUCRO: R$ 8.576/mês = 82% margem
MARGEM POR CLIENTE: 78-86% (conforme plano)

✅ CONCLUSÃO: Margem 50%+ garantida desde o início!
```

---

## 5. ROADMAP (FASES)

### 5.1 Fase 1 (MVP - Semanas 1-12)

```
Sprint 1-2 (2 semanas): Autenticação + Agendamento
✅ Autenticação multi-tenant (email, senha, OAuth Google)
✅ Calendário visual (dia, semana, mês)
✅ Lock temporal (Redis) - evitar double booking
✅ PDV básico (registrar pagamento)
✅ LGPD: Consentimento + Logs

Sprint 3-4 (2 semanas): WhatsApp + Limite de API
✅ Integração Twilio (confirmação + lembrete)
✅ Limite de API por plano (CRÍTICO)
✅ Bloqueio automático quando excede
✅ Dashboard de uso (cliente vê consumo)
✅ Fallback SMS automático

Sprint 5-6 (2 semanas): Link + Link + App Mobile MVP
✅ Link personalizado (suaagenda.pro/nome)
✅ QR Code automático
✅ App mobile básico (React Native)
✅ Notificações push

Sprint 7-8 (2 semanas): Dashboard Admin
✅ Gestão de empresas (cadastro, status, plano)
✅ Cobrança ASAAS/Stripe integration
✅ Métricas básicas (MRR, churn, LTV)
✅ Trial management (7 dias automático)

Sprint 9-10 (2 semanas): Relatórios Básicos
✅ Relatório 1: Receita por período
✅ Relatório 2: Agendamentos
✅ Relatório 3: Clientes
✅ Cache com Redis

Sprint 11-12 (2 semanas): QA + Docs + Beta
✅ Testes automatizados (80% cobertura)
✅ Documentação API (OpenAPI)
✅ Beta com 10 clientes
✅ Ajustes baseado em feedback

KPI: 50 empresas ativas com 1.000+ agendamentos
MRR: R$ 2.000-3.000
Churn: 12-15% (esperado em beta)
```

### 5.2 Fase 2 (Semanas 13-24)

```
✅ Customização de temas (cores, logo)
✅ Marketing ativo: Aniversariantes, Resgate
✅ Google Calendar sync (2-way)
✅ Controle de comissões
✅ Gestão de estoque
✅ Relatórios avançados com filtros (TODOS 6 tipos)
✅ IA: Análise de padrões (recomendações)
✅ App mobile: Versão completa
✅ Dashboard profissional: Agenda real-time
✅ MercadoPago/ASAAS: Integração completa

KPI: 500 empresas, R$ 50k MRR
Churn: 8%/mês
LTV: R$ 1.200+
```

### 5.3 Fase 3 (Semanas 25-36)

```
✅ Plano ENTERPRISE (público, transparente)
✅ Domínio customizado
✅ Multi-unidade (franquias)
✅ API REST (documentação completa)
✅ Funil de venda por IA (chatbot)
✅ Chat para tirar dúvidas (IA)
✅ Mapa de calor (horários de pico)
✅ App iOS/Android na App Store/Google Play
✅ Integrações avançadas (Zapier, Make)
✅ Relatório com IA: Insights automáticos

KPI: 2.000 empresas, R$ 200k MRR
ARR: R$ 2.4M
Churn: 4%/mês
```

---

## 6. STACK TÉCNICO

### 6.1 Backend

```
Framework: Laravel 11
├─ Rotas: API REST (routes/api.php)
├─ Autenticação: Sanctum (tokens)
├─ Autorização: Policies + Gates
├─ Validação: Form Request
├─ Middleware: Multi-tenancy scope
└─ Queue: Redis (notificações, sync Google)

Banco de Dados: MySQL 8.0
├─ Transações ACID (agendamentos)
├─ Migrations versionadas
├─ Seeding para testes
└─ Backup automático 2x/dia

Cache/Sessions: Redis
├─ Slots reserve (TTL 5min)
├─ Sessions de usuário
├─ Cache de relatórios
├─ Jobs queue
└─ Rate limiting

Autenticação: OAuth 2.0
├─ Google (Calendar)
├─ MercadoPago
├─ ASAAS
└─ Tokens em Redis (não DB)

Integrações Principais:
├─ Twilio (WhatsApp)
├─ ASAAS (SMS, Pagamento)
├─ SendGrid (Email)
├─ Stripe/MercadoPago (Cobrança)
└─ Google APIs (Calendar, Business)
```

### 6.2 Frontend

```
Web:
├─ Tailwind CSS (estilo)
├─ Alpine.js ou Vue.js (interatividade)
├─ Blade (templates)
├─ Axios (requisições)
└─ PWA (offline capability)

Mobile:
├─ React Native
├─ Expo (build/deploy)
├─ Redux ou Context (state management)
├─ React Query (data fetching)
└─ UI components: React Native Paper

Customização:
├─ CSS variables (temas dinâmicos)
├─ Logo upload (AWS S3)
├─ Font selection (Google Fonts)
└─ Color picker (hex input)
```

### 6.3 DevOps

```
Containerização: Docker
├─ Dockerfile (Laravel)
├─ Docker Compose (local dev)
└─ Multi-stage build

CI/CD: GitHub Actions
├─ Testes automáticos
├─ Lint e análise estática
├─ Build e push imagem
└─ Deploy automático

Deploy: Railway / Render
├─ Zero-downtime deployment
├─ Auto-scaling
├─ SSL automático
└─ Backup automático

Monitoramento:
├─ Sentry (error tracking)
├─ DataDog (performance)
├─ UptimeRobot (health checks)
└─ Custom dashboards
```

---

## 7. ANÁLISE COMPETITIVA

### 7.1 Vs Gendo

| Aspecto | Gendo | suaAgenda.pro | Vencedor |
|---------|-------|---------------|----------|
| **Agendamento** | ✅ | ✅ | Empate |
| **WhatsApp (modelo)** | ❌ API pura | ✅ Cliente-iniciado | suaAgenda |
| **LGPD Compliance** | ✓ Mencionado | ✅ Certificado | suaAgenda |
| **IA Incluída** | ❌ Créditos | ✅ Ilimitado (Pro+) | suaAgenda |
| **Preço Inicial** | R$ 39,90 | R$ 49,90 | Gendo (10% menos) |
| **Limite API** | ❌ Sem limites | ✅ Controlado | suaAgenda |
| **Relatórios** | ✅ Básicos | ✅ Avançados (6 tipos) | suaAgenda |
| **Customização** | ❌ Limitada | ✅ Temas + Domínio | suaAgenda |
| **Trial** | 7 dias | 7 dias | Empate |
| **Margem Financeira** | Oculta | 50%+ (transparent) | suaAgenda |

### 7.2 Diferencial Claro

```
Você: "A plataforma de agendamento que controla gastos,
       segue a lei (LGPD) e funciona melhor (Client-Initiated)"

Posicionamento: PME + Autônomo + Controle de Custos
Preço: Competitivo (R$ 49,90 é justo)
Features: Mais inteligentes e seguras
Transparência: Margem 50% garantida desde o início
```

---

## 8. KPIs E MÉTRICAS

### 8.1 Produto

```
├─ Uptime: 99.9%
├─ Latência: < 200ms (p95)
├─ Taxa de erro: < 0.1%
├─ Load time: < 3s (mobile)
├─ Net Promoter Score (NPS): > 50
└─ API Quota Accuracy: 99.99%
```

### 8.2 Negócio (Ano 1)

```
├─ Trials iniciados: 300+
├─ Conversão trial: 15-25%
├─ Clientes ao final: 75+
├─ MRR ao final: R$ 7.900+
├─ Churn mensal: 6-8%
├─ LTV: > R$ 1.200
├─ CAC: < R$ 50 (via trial)
├─ Margem blended: 78%+
└─ Payback: 2-3 meses
```

### 8.3 Financeiro (Ano 1)

```
├─ Receita trimestral: R$ 2k → R$ 23k
├─ ARR estimado: R$ 94.800
├─ Lucro estimado: R$ 70.000+
├─ Clientes ativos: 75
├─ Receita média por cliente: R$ 105
└─ Viabilidade: ✅ COMPROVADA
```

---

## 9. RISCOS E MITIGAÇÃO

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|--------|-----------|
| **Limite API muito restritivo** | Média | Alto | Comunicar bem, SMS fallback |
| **Cliente bate limite cedo** | Alta | Médio | Upgrade automático, aviso 7 dias |
| **Concorrência oferece sem limite** | Baixa | Crítico | Maior valor agregado (UX, IA) |
| **Integração WhatsApp falha** | Baixa | Crítico | Testes rigorosos + fallback SMS |
| **LGPD: Não cumprir** | Muito baixa | Crítico | Auditoria desde dia 1 |
| **Perda de dados** | Muito baixa | Crítico | Backup 2x/dia + replicação |
| **Falta de tração inicial** | Média | Alto | Marketing + Parcerias + Referência |
| **Custo API sobe** | Baixa | Médio | Contrato anual Twilio (discount) |

---

## 10. GO-TO-MARKET

### 10.1 Fase 1: Validação (Mês 1-2)

```
├─ Beta com 10 clientes presenciais
├─ Feedback loops semanais
├─ Ajuste de pricing/features conforme feedback
├─ Teste: Limite de 50 msg é realista?
├─ Teste: Conversão trial 15%+ é alcançável?
└─ Decisão GO para escala
```

### 10.2 Fase 2: Early Adopters (Mês 3-4)

```
├─ Lançamento público (landing page)
├─ Content: 3-5 artigos SEO (guias práticos)
├─ Parcerias: Fornecedores de beleza
├─ Programa de referência: "Indique = créditos"
├─ Email marketing (lista beta)
└─ Alvo: 30-50 clientes pagos
```

### 10.3 Fase 3: Crescimento (Mês 5-12)

```
├─ SEM (Google Ads) - keywords altas intenção
├─ Parcerias: Software de PDV (integração)
├─ Webinars mensais (dicas, trends)
├─ Comunidade online (Slack/Discord)
├─ Afiliados (bônus por referência)
├─ Press release (publicações tech)
└─ Alvo: 75-100 clientes pagos
```

---

## 11. PLANO DE IMPLEMENTAÇÃO

### Timeline

```
SEMANA 1-2: Planejamento + Setup
├─ [ ] Validar pricing com 5 clientes (entrevistas)
├─ [ ] Setup banco de dados (MySQL migrations)
├─ [ ] Setup hospedagem (você já tem)
├─ [ ] Setup repositório Git + CI/CD
└─ [ ] Setup Twilio + ASAAS (contas)

SEMANA 3-4: MVP Backend
├─ [ ] Autenticação (email, OAuth Google)
├─ [ ] Multi-tenancy (middleware isolamento)
├─ [ ] Calendário (agendamento básico)
├─ [ ] Lock temporal (Redis)
└─ [ ] WhatsApp limit service (CRÍTICO)

SEMANA 5-6: WhatsApp + Trial
├─ [ ] Twilio integration (envio SMS/WhatsApp)
├─ [ ] Dashboard de uso (cliente vê quota)
├─ [ ] Trial logic (7 dias, bloqueio automático)
├─ [ ] Email reminders (dia 4, 6, 7)
└─ [ ] Admin dashboard (visualizar clientes)

SEMANA 7-8: Frontend + Mobile
├─ [ ] Landing page (sales + trial signup)
├─ [ ] Dashboard cliente (calendário + settings)
├─ [ ] App mobile basic (React Native)
├─ [ ] QR Code geração
└─ [ ] Notificações push

SEMANA 9-10: Relatórios + PDV
├─ [ ] Relatório 1: Receita
├─ [ ] Relatório 2: Agendamentos
├─ [ ] Relatório 3: Clientes
├─ [ ] PDV: Registrar pagamento
└─ [ ] Cache com Redis

SEMANA 11-12: QA + Docs + Beta
├─ [ ] Testes automatizados (80%+)
├─ [ ] Documentação API (OpenAPI)
├─ [ ] Manual do usuário
├─ [ ] Beta launch (10 clientes)
└─ [ ] Ajustes baseado em feedback

RESULTADO: MVP pronto para escala
```

---

## 12. ORÇAMENTO & RECURSOS

### Infraestrutura (Você já tem)
- ✅ Domínio
- ✅ Hospedagem (Railway/Render ~R$500/mês)

### Terceiros (Necessário)
```
Twilio (WhatsApp): ~R$ 100-200/mês (variável)
ASAAS (SMS + Pagamento): ~R$ 100-150/mês
SendGrid (Email): ~R$ 100/mês
Sentry (Observability): ~R$ 100/mês
Google APIs: Gratuito
────────────────────────────
TOTAL: ~R$ 500-650/mês (custos fixos)

Obs: Estes custos já estão inclusos no cálculo 
de margem 50% (viável desde o início)
```

### Recursos Humanos
```
Dev Full-Stack: 1 pessoa (você)
├─ 12 semanas para MVP
├─ Após MVP: Manutenção 20-30h/semana
└─ Suporte: 5-10h/semana (aumenta com clientes)

Designer/UX (Fase 2): Parceiro ou freelancer
├─ Landing page: ~R$ 2.000-3.000
├─ Design system: ~R$ 1.500-2.000
└─ Opcional: Fazer você (templates existentes)

Copywriter (Fase 2): Freelancer
├─ Conteúdo (5 artigos): ~R$ 1.000-1.500
├─ Emails: ~R$ 500-800
└─ Opcional: Fazer você (IA pode ajudar)
```

---

## 13. SUCESSO & MILESTONES

### MVP (Semana 12)
- ✅ 10 clientes beta ativos
- ✅ 100+ agendamentos/mês
- ✅ Limit API funcionando
- ✅ Margem 50%+ comprovada
- ✅ Churn 12-15% (esperado)

### Mês 3
- ✅ 30-50 clientes pagos
- ✅ R$ 2-3k MRR
- ✅ Feedback positivo (NPS > 40)
- ✅ 0 dados perdidos (backup OK)

### Mês 6
- ✅ 75+ clientes
- ✅ R$ 7-8k MRR
- ✅ Churn caindo (para 8%)
- ✅ Fase 2 iniciada (marketing ativo)

### Mês 12
- ✅ 100+ clientes
- ✅ R$ 10-12k MRR
- ✅ ARR R$ 120-150k
- ✅ Lucro R$ 80-100k+
- ✅ Produto sustentável

---

## 14. CONCLUSÃO

### Viabilidade: ✅ SIM

```
Razões:

1. MARGEM FINANCEIRA
   └─ 50%+ garantida desde cliente #1
   └─ Limite API = controle de custos
   └─ Escala sem aumentar custo variável

2. MERCADO VALIDADO
   └─ 15.000+ potenciais clientes no Brasil
   └─ Disposição a pagar: R$ 50-200/mês
   └─ Pain point real: Custos de agendamento

3. DIFERENCIAL CLARO
   └─ Cliente-initiated WhatsApp (não API)
   └─ LGPD compliance
   └─ Limite transparente (vs. surpresas)
   └─ IA incluída (Profissional+)

4. ROADMAP REALISTA
   └─ 12 semanas para MVP
   └─ 24 semanas para Fase 2 (Marketing ativo)
   └─ Crescimento exponencial esperado

5. RISCO BAIXO
   └─ Tech simples (Laravel + React)
   └─ Stack conhecido
   └─ Terceiros confiáveis (Twilio, ASAAS)
   └─ Backup de dados 2x/dia
```

### Recomendação Final

**VALIDAR + IMPLEMENTAR IMEDIATAMENTE**

Próximos passos:
1. Perguntar 5 clientes sobre pricing (2 dias)
2. Iniciar desenvolvimento MVP (12 semanas)
3. Beta com 10 clientes (2 semanas)
4. Ajustar + Escalar (4 semanas)
5. Lançamento público (Semana 20)

---

## DOCUMENTOS ANEXOS

- [ ] Diagrama de Arquitetura (ER, API) - A criar
- [ ] Wireframes/Mockups (Figma) - A criar
- [ ] Documento de Segurança & LGPD - A criar
- [ ] Especificação de API (OpenAPI 3.0) - A criar
- [ ] Plano de Testes - A criar
- [ ] Documentação Técnica - A criar

---

**Preparado por:** Análise Estratégica  
**Última atualização:** 2026  
**Status:** ✅ PRONTO PARA IMPLEMENTAÇÃO  
**Viabilidade Financeira:** ✅ COMPROVADA (Margem 50%+)  
**Risco:** BAIXO

---

## HISTÓRICO DE VERSÕES

| Versão | Data | Alterações |
|--------|------|-----------|
| 1.0 | 2026 | Versão original |
| 2.0 | 2026 | Trial 7 dias (não free), Limite API WhatsApp, Margem 50%, Preço R$49,90 |

