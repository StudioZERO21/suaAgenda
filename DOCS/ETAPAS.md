# 🗺️ ETAPAS DE DESENVOLVIMENTO - suaAgenda.pro (Phase 1)

**Objetivo:** Detalhar as 6 etapas de 2 semanas cada (12 semanas totais) para MVP  
**Versão:** 1.0  
**Status:** 📋 Planejado

---

## 📊 VISÃO GERAL

```
┌─────────────────────────────────────────────────────────────┐
│                    PHASE 1: MVP (12 SEMANAS)                │
├─────────────────────────────────────────────────────────────┤
│  Etapa 1.1   │  Etapa 1.2   │  Etapa 1.3   │  Etapa 1.4    │
│  (Sem 1-2)   │  (Sem 3-4)   │  (Sem 5-6)   │  (Sem 7-8)    │
│              │              │              │               │
│  Setup +     │  WhatsApp +  │  Link +      │  Admin        │
│  Auth +      │  API Limit + │  Mobile MVP  │  Dashboard    │
│  Agendamento │  Dashboard   │  Notificações│  Cobrança     │
│  (Lock)      │  Uso         │              │               │
│              │              │              │               │
│ ✅ Auth OK   │ ✅ WhatsApp  │ ✅ Link OK   │ ✅ Admin OK   │
│ ✅ Models OK │ ✅ Limits OK │ ✅ Mobile OK │ ✅ Billing OK │
│ ✅ Tests OK  │ ✅ Tests OK  │ ✅ Tests OK  │ ✅ Tests OK   │
└─────────────────────────────────────────────────────────────┘
           ↓                      ↓                      ↓
┌─────────────────────────────────────────────────────────────┐
│  Etapa 1.5   │  Etapa 1.6   │  PHASE 1 COMPLETA             │
│  (Sem 9-10)  │  (Sem 11-12) │                               │
│              │              │                               │
│  Relatórios  │  QA + Beta + │  ✅ MVP Pronto                │
│  (6 tipos)   │  Docs        │  ✅ 10 clientes beta          │
│  Cache Redis │              │  ✅ Margem 50%+               │
│  Filtros     │  Testes 80%+ │  ✅ Pronto escala             │
│              │  Manual QA   │                               │
│ ✅ Reports   │ ✅ Beta OK   │  → PHASE 2: Growth            │
│ ✅ Tests OK  │ ✅ Release   │                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 ETAPA 1.1 (Semanas 1-2) - Setup + Auth + Agendamento

**Tema:** Fundação do projeto - Autenticação, Models, Agendamento com Lock Temporal

### Objetivos

- ✅ Projeto Laravel 13 criado e pronto
- ✅ Multi-tenancy configurado
- ✅ Autenticação (email + OAuth Google)
- ✅ CRUD Agendamento com lock Redis
- ✅ Primeiros testes (80%+)

### Deliverables

```
✅ app/Models/User, Company, Profissional, Cliente, Agendamento
✅ app/Http/Controllers/Auth/LoginController, RegisterController
✅ app/Http/Controllers/AgendamentoController (CRUD)
✅ app/Http/Requests/StoreAgendamentoRequest, UpdateAgendamentoRequest
✅ app/Policies/AgendamentoPolicy
✅ database/migrations/ (6 tabelas)
✅ database/seeders/UserSeeder, RoleSeeder
✅ tests/Feature/AuthTest.php, AgendamentoTest.php
✅ resources/views/auth/ (login, register)
✅ resources/views/dashboard/ (calendário básico)
✅ .cursorrules (regras Cursor)
✅ DOCS/ARCHITECTURE.md
✅ DOCS/DATABASE-SCHEMA.md
✅ Backup: backup-etapa-1.1.sql + .zip
```

### Daily Tasks (cada dia)

**Dia 1-2 (Seg-Ter):**
- [ ] Setup inicial (PHP, Node, MySQL)
- [ ] Criar projeto Laravel 13
- [ ] Git configurado + branch etapa-1.1
- [ ] .env configurado (DB, Timezone, Locale)

**Dia 3-4 (Qua-Qui):**
- [ ] Models criados (User, Company, etc)
- [ ] Migrations + Seeders
- [ ] AuthController (login, register, logout)
- [ ] OAuth Google configurado (opcional para MVP)

**Dia 5-6 (Sex-Sab):**
- [ ] AgendamentoController (store, update, destroy)
- [ ] Lock Redis implementado
- [ ] Tests: AuthTest.php, AgendamentoTest.php
- [ ] Views: login, register, calendário básico

**Dia 7-10 (seg-sex próxima semana):**
- [ ] Policies (autorização)
- [ ] Soft deletes
- [ ] LGPD compliance (checkbox consentimento)
- [ ] Documentação: ARCHITECTURE.md, DATABASE-SCHEMA.md

**Dia 11-14 (seg-sex última semana):**
- [ ] QA: ./vendor/bin/pest (80%+ cobertura)
- [ ] Lint: ./vendor/bin/pint
- [ ] Cleanup: remover TODOs, dd()
- [ ] **BACKUP OBRIGATÓRIO**
- [ ] Commit final + push
- [ ] Documentação finalizada

### Teste de Aceitação

```bash
# Ao fim da etapa, executar:
./vendor/bin/pest tests/Feature/MigrationTest.php     # ✅ PASS
./vendor/bin/pest tests/Feature/AuthTest.php           # ✅ PASS
./vendor/bin/pest tests/Feature/AgendamentoTest.php    # ✅ PASS
./vendor/bin/pint --test                               # ✅ OK
./vendor/bin/pest --coverage                           # ✅ >= 80%

# Verificar banco:
php artisan tinker
>>> User::count()                        # 2
>>> Company::count()                     # 1
>>> Agendamento::count()                 # 0 (ok, vazio)

# Verificar acesso:
http://127.0.0.1:8000/login              # OK
http://127.0.0.1:8000/dashboard          # Redireciona para login
```

### Checklist Final

- ☑️ Código formatado (./vendor/bin/pint)
- ☑️ Testes passando (80%+)
- ☑️ Documentação atualizada
- ☑️ Backup realizado (SQL + ZIP)
- ☑️ Git: commit + push
- ☑️ Pronto para merge

---

## 🎯 ETAPA 1.2 (Semanas 3-4) - WhatsApp + API Limits

**Tema:** Integração WhatsApp com controle de quota, Dashboard de uso

### Objetivos

- ✅ Integração Twilio WhatsApp
- ✅ Limite de mensagens por plano
- ✅ Dashboard cliente mostrando uso
- ✅ SMS como fallback
- ✅ Logging de mensagens

### Deliverables

```
✅ app/Services/WhatsAppLimitService.php
✅ app/Services/TwilioService.php
✅ app/Models/WhatsAppLog.php
✅ database/migrations/create_whatsapp_logs_table.php
✅ resources/views/dashboard/mensagens.blade.php (Dashboard)
✅ tests/Feature/WhatsAppLimitTest.php
✅ app/Http/Controllers/Dashboard/MensagensController.php
✅ .env: TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_PHONE
✅ Documentação: DOCS/WHATSAPP-INTEGRATION.md
✅ Backup: backup-etapa-1.2.sql + .zip
```

### Daily Tasks

**Dia 1-2:** Twilio setup, models WhatsAppLog
**Dia 3-4:** WhatsAppLimitService (quota check)
**Dia 5-6:** Dashboard mensagens (visualizar uso)
**Dia 7-10:** Tests, SMS fallback
**Dia 11-14:** QA, Documentação, Backup

### Teste de Aceitação

```bash
./vendor/bin/pest tests/Feature/WhatsAppLimitTest.php  # ✅ PASS

# Verificar:
php artisan tinker
>>> $company = Company::first();
>>> app('WhatsAppLimitService')->checkQuota($company->id)
# { limit: 50, used: 5, remaining: 45 }
```

---

## 🎯 ETAPA 1.3 (Semanas 5-6) - Link Personalizado + Mobile MVP

**Tema:** URL customizada, QR Code, App mobile básico em React Native

### Objetivos

- ✅ Link: suaagenda.pro/empresa-name
- ✅ QR Code gerado automaticamente
- ✅ Analytics: cliques, conversões
- ✅ React Native app básico
- ✅ Notificações push

### Deliverables

```
✅ app/Models/CompanyLink.php
✅ app/Http/Controllers/PublicLinkController.php
✅ database/migrations/create_company_links_table.php
✅ resources/views/public/agendamento.blade.php
✅ app/Services/QrCodeService.php
✅ mobile/app.json (React Native + Expo)
✅ mobile/src/screens/AgendamentoScreen.tsx
✅ tests/Feature/PublicLinkTest.php
✅ Backup: backup-etapa-1.3.sql + .zip
```

### Daily Tasks

**Dia 1-3:** Link model + QR Code generator
**Dia 4-6:** Public agendamento page (landing)
**Dia 7-10:** React Native setup + screens básicas
**Dia 11-14:** Tests, Push notifications, Backup

---

## 🎯 ETAPA 1.4 (Semanas 7-8) - Admin Dashboard

**Tema:** Dashboard super_admin, gestão de empresas, cobrança, trial management

### Objetivos

- ✅ Super admin dashboard
- ✅ Gestão de empresas (CRUD)
- ✅ Integração Stripe/ASAAS (cobrança)
- ✅ Trial 7 dias (automático)
- ✅ Upgrade/downgrade de plano
- ✅ Métricas: MRR, churn, LTV

### Deliverables

```
✅ app/Http/Controllers/Admin/CompanyController.php
✅ app/Http/Controllers/Admin/TrialController.php
✅ app/Http/Controllers/Admin/BillingController.php
✅ app/Services/TrialService.php
✅ app/Services/BillingService.php
✅ database/migrations/create_subscriptions_table.php
✅ resources/views/admin/ (dashboard, companies, billing)
✅ tests/Feature/AdminTest.php
✅ DOCS/BILLING-INTEGRATION.md
✅ Backup: backup-etapa-1.4.sql + .zip
```

### Daily Tasks

**Dia 1-3:** Subscription model + migrations
**Dia 4-6:** Stripe/ASAAS integration
**Dia 7-10:** Admin dashboard + company CRUD
**Dia 11-14:** Trial logic, Metrics, Backup

---

## 🎯 ETAPA 1.5 (Semanas 9-10) - Relatórios

**Tema:** 6 relatórios com filtros, cache Redis, gráficos

### Objetivos

- ✅ Receita por período
- ✅ Clientes (quantidade, repeat)
- ✅ Profissionais (ranking, desempenho)
- ✅ Agendamentos (horário pico, no-show)
- ✅ Marketing (campanhas, conversão)
- ✅ Financeiro (lucro, custos)
- ✅ Cache com Redis
- ✅ Gráficos (Chart.js / Recharts)

### Deliverables

```
✅ app/Services/ReportService.php
✅ app/Http/Controllers/ReportController.php
✅ app/Domain/Reports/*.php (6 report classes)
✅ resources/views/reports/ (6 templates)
✅ app/Jobs/GenerateReportJob.php
✅ tests/Feature/ReportTest.php
✅ app/Console/Commands/GenerateReports.php
✅ Backup: backup-etapa-1.5.sql + .zip
```

### Daily Tasks

**Dia 1-2:** ReportService + base structure
**Dia 3-4:** Receita + Clientes reports
**Dia 5-6:** Profissionais + Agendamentos
**Dia 7-8:** Marketing + Financeiro
**Dia 9-10:** Cache, Gráficos, Jobs
**Dia 11-14:** Tests, Documentação, Backup

---

## 🎯 ETAPA 1.6 (Semanas 11-12) - QA + Docs + Beta Launch

**Tema:** Testes finais, documentação completa, lançamento beta com 10 clientes

### Objetivos

- ✅ Cobertura testes 80%+
- ✅ Documentação completa
- ✅ Página landing finalizada
- ✅ 10 clientes beta selecionados
- ✅ Feedback loop estruturado
- ✅ Deploy staging
- ✅ Monitoramento configurado

### Deliverables

```
✅ 100% checklist de testes
✅ ./vendor/bin/pest --coverage >= 80%
✅ DOCS/ (completamente documentado)
✅ resources/views/landing.blade.php
✅ Landing page: pricing, features, FAQ
✅ E-mail automático (confirmação trial)
✅ Support page: documentação cliente
✅ Feedback form (typeform/similar)
✅ Deploy script (staging)
✅ Monitoring (Sentry configurado)
✅ Backup final: backup-etapa-1.6.sql + .zip
```

### Daily Tasks

**Dia 1-3:** Completar cobertura de testes
**Dia 4-5:** Testes manuais (QA checklist)
**Dia 6-7:** Landing page finalizada
**Dia 8-9:** Email triggers, Support docs
**Dia 10-11:** Deploy staging + monitoring
**Dia 12-14:** Beta launch, Feedback collection, Backup

### Teste de Aceitação Final

```bash
./vendor/bin/pest                           # ✅ 100% PASS
./vendor/bin/pest --coverage                # ✅ >= 80%
./vendor/bin/pint --test                    # ✅ OK
ls DOCS/                                    # ✅ 20+ arquivos

# Verificar staging:
https://staging.suaagenda.pro/login         # ✅ OK
https://staging.suaagenda.pro/               # ✅ Landing OK

# Feedback:
Contatos beta: 10 clientes
NPS esperado: > 40
Churn esperado: 12-15%
```

---

## 📅 CRONOGRAMA VISUAL

```
SEMANA 1-2  | SEMANA 3-4  | SEMANA 5-6   | SEMANA 7-8
ETAPA 1.1   | ETAPA 1.2   | ETAPA 1.3    | ETAPA 1.4
Setup+Auth  | WhatsApp    | Link+Mobile  | Admin+Billing
✅ Pronto   | ✅ Pronto   | ✅ Pronto    | ✅ Pronto
            |             |              |
SEMANA 9-10 | SEMANA 11-12|
ETAPA 1.5   | ETAPA 1.6   |
Relatórios  | QA+Beta     |
✅ Pronto   | ✅ LANÇADO! |
```

---

## 🎯 KPIs POR ETAPA

| Etapa | Testes | Cobertura | Docs | Status |
|---|---|---|---|---|
| 1.1 | ./vendor/bin/pest | 80%+ | ARCHITECTURE.md | ✅ |
| 1.2 | + WhatsAppTest | 80%+ | WHATSAPP.md | ✅ |
| 1.3 | + PublicLinkTest | 80%+ | LINK.md | ✅ |
| 1.4 | + AdminTest | 80%+ | ADMIN.md | ✅ |
| 1.5 | + ReportTest | 80%+ | REPORTS.md | ✅ |
| 1.6 | + ManualQA | 80%+ | COMPLETE | ✅ |

---

## 💾 BACKUP OBRIGATÓRIO

**Após cada etapa:** Executar backup duplo

```bash
# Etapa 1.1
mysqldump -u root -p suaAgenda > BACKUPS/backup-etapa-1.1.sql
zip -r BACKUPS/backup-etapa-1.1.zip . --exclude="vendor/*" --exclude="node_modules/*"
git add BACKUPS/ && git commit -m "backup(etapa-1.1): OK"

# Etapa 1.2, 1.3, etc...
```

---

## 🔄 WORKFLOW DE MERGE

Após cada etapa:

```bash
# 1. Final checklist
./vendor/bin/pest              # ✅ PASS
./vendor/bin/pint --test       # ✅ OK
# Backup realizado

# 2. Push
git add .
git commit -m "backup(etapa-1.X): final"
git push origin etapa-1.X

# 3. Merge (manual após review)
git checkout develop
git merge --no-ff etapa-1.X -m "merge(etapa-1.X): descrição"
git push origin develop

# 4. Próxima etapa
git checkout -b etapa-1.Y
# Continua...
```

---

## 📞 REFERÊNCIA RÁPIDA

**Precisa saber a data esperada da Etapa X?**

```
Etapa 1.1: Semanas 1-2   (06 Jan - 20 Jan)
Etapa 1.2: Semanas 3-4   (20 Jan - 03 Feb)
Etapa 1.3: Semanas 5-6   (03 Feb - 17 Feb)
Etapa 1.4: Semanas 7-8   (17 Feb - 03 Mar)
Etapa 1.5: Semanas 9-10  (03 Mar - 17 Mar)
Etapa 1.6: Semanas 11-12 (17 Mar - 31 Mar)

LANÇAMENTO PHASE 1: 31 Março 2026 ✅
```

---

**Próximo passo:** Iniciar Etapa 1.1 - Consulte [SETUP.md](./SETUP.md)
