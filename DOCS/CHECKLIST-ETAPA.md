# ✅ CHECKLIST POR ETAPA - suaAgenda.pro

**Objetivo:** Acompanhar progresso de desenvolvimento e qualidade  
**Frequência:** Atualizar diariamente durante a etapa  
**Versão:** 1.0

---

## 📋 COMO USAR

1. **Copiar este arquivo** para a etapa específica:
   ```bash
   cp DOCS/CHECKLIST-ETAPA.md DOCS/CHECKLIST-ETAPA-1.1.md
   cp DOCS/CHECKLIST-ETAPA.md DOCS/CHECKLIST-ETAPA-1.2.md
   # etc
   ```

2. **Atualizar nome e datas** na seção "Informações da Etapa"

3. **Marcar progresso** conforme desenvolve:
   - ☐ Não iniciado
   - 🟡 Em progresso
   - ✅ Completo

4. **Manter arquivo versionado** no Git:
   ```bash
   git add DOCS/CHECKLIST-ETAPA-1.1.md
   git commit -m "docs(etapa-1.1): atualizar checklist"
   git push origin etapa-1.1
   ```

---

## 📌 INFORMAÇÕES DA ETAPA

```
Etapa: 1.1
Sprint: Sprint 1-2 (2 semanas)
Tema: Autenticação + Agendamento (Lock Temporal)
Data Início: 2026-01-06
Data Fim Esperada: 2026-01-20
Status Geral: 🔵 EM ANDAMENTO
Desenvolvedor: [Seu Nome]
```

---

## 🎯 OBJETIVOS DA ETAPA

**Descrição geral:**
Implementar sistema de autenticação multi-tenant com suporte OAuth Google, calendário visual e agendamento com lock temporal em Redis para evitar double booking.

**Funcionalidades esperadas:**
- [ ] Autenticação email + senha
- [ ] OAuth Google 2.0
- [ ] Multi-tenancy (isolamento por company_id)
- [ ] Calendário visual (dia, semana, mês)
- [ ] Agendamento simples
- [ ] Lock temporal (Redis, 5 min)
- [ ] PDV básico (registrar pagamento)
- [ ] LGPD compliance (consentimento)

**Tamanho estimado:** 150-200 linhas de código novo

---

## 🏗️ MODELS & BANCO DE DADOS

### Models a Criar/Modificar

- [ ] **User** - Adicionar company_id, ativo
  - [ ] Migrations criadas
  - [ ] Campos: id, name, email, password, company_id, ativo, deleted_at
  - [ ] Relationships: belongsTo Company
  - [ ] Scopes: ativo()

- [ ] **Company** (Empresa)
  - [ ] Migration criada
  - [ ] Campos: id, name, slug, plan, whatsapp, lgpd_consent
  - [ ] Relationships: hasMany Users, hasMany Agendamentos
  - [ ] Soft Delete: ✅

- [ ] **Profissional**
  - [ ] Migration criada
  - [ ] Campos: id, company_id, name, especialidade, ativo
  - [ ] Relationships: belongsTo Company, hasMany Agendamentos
  - [ ] Soft Delete: ✅

- [ ] **Cliente**
  - [ ] Migration criada
  - [ ] Campos: id, company_id, name, phone, email, data_nasc
  - [ ] Relationships: belongsTo Company, hasMany Agendamentos
  - [ ] Soft Delete: ✅

- [ ] **Agendamento**
  - [ ] Migration criada
  - [ ] Campos: id, company_id, profissional_id, cliente_id, data_hora, duracao, status, motivo_cancelamento
  - [ ] Relationships: belongsTo Company, belongsTo Profissional, belongsTo Cliente
  - [ ] Soft Delete: ✅
  - [ ] Status enum: pendente, confirmado, finalizado, cancelado

- [ ] **Pagamento** (PDV Básico)
  - [ ] Migration criada
  - [ ] Campos: id, agendamento_id, valor, metodo (dinheiro, débito, crédito, pix)
  - [ ] Soft Delete: ✅

### Validação & Scopes

- [ ] **User model:**
  - [ ] Validação: email único, nome obrigatório
  - [ ] Scope: ativo()
  - [ ] Método: hasRoles(), can()

- [ ] **Agendamento model:**
  - [ ] Validação: data/hora obrigatória, duracao > 0
  - [ ] Scope: por_data($date), por_profissional($id)
  - [ ] Método: estaDisponivel()

---

## 🔐 AUTENTICAÇÃO & AUTORIZAÇÃO

### Controllers

- [ ] **Auth/LoginController**
  - [ ] Login por email/senha
  - [ ] Login OAuth Google
  - [ ] Logout
  - [ ] Verificação de 2FA (se aplicável)
  - [ ] Rate limiting (5 tentativas/5min)

- [ ] **Auth/RegisterController**
  - [ ] Criação de conta
  - [ ] Envio email confirmação
  - [ ] Confirmação email
  - [ ] Criar company automaticamente

- [ ] **AgendamentoController**
  - [ ] Store: criar agendamento com lock Redis
  - [ ] Update: editar agendamento
  - [ ] Destroy: cancelar (soft delete)
  - [ ] Index: listar agendamentos (com paginação)

### Policies

- [ ] **AgendamentoPolicy**
  - [ ] viewAny(): admin da empresa
  - [ ] view(): super_admin ou dono
  - [ ] create(): admin empresa
  - [ ] update(): admin empresa
  - [ ] delete(): admin empresa

### Middleware

- [ ] **SetTenantMiddleware**
  - [ ] Extrai X-Company-Id do header
  - [ ] Valida se user pertence à company
  - [ ] Injeta no request

- [ ] **VerifyCompanyAccess**
  - [ ] Verifica acesso à company específica

### Form Requests

- [ ] **StoreAgendamentoRequest**
  - [ ] Validações: data_hora required, profissional_id válido
  - [ ] Mensagens em português
  - [ ] authorize(): verificar acesso

- [ ] **UpdateAgendamentoRequest**
  - [ ] Idem

---

## 🎨 VIEWS & FRONTEND

### Components Blade

- [ ] **components/button-primary.blade.php**
  - [ ] Props: label, disabled, loading
  - [ ] Classes Tailwind corretas
  - [ ] SweetAlert2 integrado

- [ ] **components/form-input.blade.php**
  - [ ] Props: name, label, type, placeholder, required
  - [ ] Validação visual
  - [ ] Error messages (SweetAlert2)

- [ ] **components/calendar-widget.blade.php**
  - [ ] Vista dia/semana/mês
  - [ ] Alpine.js para interatividade
  - [ ] Slots disponíveis destacados
  - [ ] Responsivo mobile

### Páginas (Views)

- [ ] **auth/login.blade.php**
  - [ ] Form email/senha
  - [ ] Botão OAuth Google
  - [ ] Design responsivo
  - [ ] Link register + forgot password

- [ ] **auth/register.blade.php**
  - [ ] Form name, email, senha, confirmação
  - [ ] LGPD checkbox (consentimento)
  - [ ] Criação automática de company
  - [ ] Validação client-side

- [ ] **dashboard/calendário.blade.php**
  - [ ] Calendário visual
  - [ ] Seleção data/hora
  - [ ] Profissional selection
  - [ ] Confirmação

- [ ] **dashboard/agendamentos-lista.blade.php**
  - [ ] Tabela agendamentos
  - [ ] Filtros (data, profissional, status)
  - [ ] Ações (editar, cancelar)
  - [ ] Paginação

### Layout Principal

- [ ] **layouts/app.blade.php**
  - [ ] Header com logo
  - [ ] Sidebar navegação
  - [ ] Footer
  - [ ] Dark mode toggle
  - [ ] Respsonivo

---

## 🧪 TESTES (Pest)

### Testes de Feature

- [ ] **tests/Feature/AuthTest.php**
  - [ ] test('login com email e senha')
  - [ ] test('login OAuth Google')
  - [ ] test('logout')
  - [ ] test('login sem credenciais')
  - [ ] test('rate limit login')

- [ ] **tests/Feature/AgendamentoTest.php**
  - [ ] test('criar agendamento')
  - [ ] test('evitar double booking com lock Redis')
  - [ ] test('cancelar agendamento')
  - [ ] test('usuário não pode ver agendamento de outra empresa')
  - [ ] test('pagination funciona')

- [ ] **tests/Feature/MultiTenancyTest.php**
  - [ ] test('dados isolados por company_id')
  - [ ] test('middleware SetTenant funciona')
  - [ ] test('acesso negado fora da company')

### Testes Unitários

- [ ] **tests/Unit/Models/UserTest.php**
  - [ ] test('user.hasCompany()')
  - [ ] test('user.isActive()')

- [ ] **tests/Unit/Models/AgendamentoTest.php**
  - [ ] test('agendamento.estaDisponivel()')
  - [ ] test('agendamento status transitions')

---

## 🔒 SEGURANÇA & LGPD

### Autenticação

- [ ] Passwords hash Bcrypt
- [ ] HTTPS em produção
- [ ] CSRF protection (Blade automático)
- [ ] Rate limiting login (5/5min)
- [ ] Session segura (httpOnly)

### Autorização

- [ ] Gate::before super_admin
- [ ] Policies implementadas
- [ ] Multi-tenancy validado (company_id check)
- [ ] Soft deletes funcionando

### LGPD

- [ ] [ ] Checkbox consentimento em registro
- [ ] Logs de quem acessou dados (audit log básico)
- [ ] Direito ao esquecimento (soft delete)
- [ ] Privacy policy link (rodapé)
- [ ] LGPD compliance document (DOCS/SECURITY.md)

---

## 📚 DOCUMENTAÇÃO

- [ ] **README.md atualizado**
  - [ ] Setup instrições
  - [ ] Stack listado
  - [ ] Features descritas

- [ ] **ARCHITECTURE.md**
  - [ ] Diagrama da arquitetura
  - [ ] Relacionamentos entre models
  - [ ] Multi-tenancy explicado

- [ ] **DATABASE-SCHEMA.md**
  - [ ] ER diagram (models relacionados)
  - [ ] Descrição de cada tabela
  - [ ] Índices definidos

- [ ] **API-SPECIFICATION.md**
  - [ ] Endpoints REST documentados
  - [ ] Parâmetros e respostas
  - [ ] Exemplos cURL

- [ ] **TESTING.md**
  - [ ] Como rodar testes
  - [ ] Cobertura esperada
  - [ ] Mocking guia

---

## 🔧 CÓDIGO & QUALIDADE

### Padrões

- [ ] strict_types=1 em todo PHP
- [ ] PSR-12 formatação (./vendor/bin/pint)
- [ ] Variáveis camelCase
- [ ] Constantes UPPER_SNAKE_CASE
- [ ] Métodos privados (sem _ prefixo, exceto frameworks)

### Linting

- [ ] ./vendor/bin/pint --test ✅
- [ ] Sem imports desnecessários
- [ ] Sem variáveis não usadas
- [ ] Sem dd(), console.log() em código
- [ ] Sem comentários óbvios

### Funcionalidade

- [ ] [ ] Sem N+1 queries
- [ ] [ ] with() aplicado em relacionamentos
- [ ] [ ] Índices no banco adicionados
- [ ] [ ] Transações em operações críticas
- [ ] [ ] Error handling correto

---

## 🚀 DEPLOYMENT & OPERAÇÃO

### Preparação

- [ ] Migrations criadas (version control)
- [ ] Seeders funcionando
- [ ] .env.example atualizado (sem secrets)
- [ ] Variáveis de ambiente documentadas

### Performance

- [ ] Cache implementado (Redis)
- [ ] Queries otimizadas
- [ ] Índices no banco criados
- [ ] Assets minificados (npm run build)

### Monitoramento

- [ ] Logging de erros (Sentry configurado)
- [ ] Health checks implementados
- [ ] Alerts configurados (opcional para MVP)

---

## 📊 COBERTURA DE TESTES

**Meta:** 80% de cobertura

| Componente | Meta | Atual | Status |
|---|---|---|---|
| Models | 90% | - | ☐ |
| Controllers | 80% | - | ☐ |
| Policies | 85% | - | ☐ |
| Services | 80% | - | ☐ |
| **TOTAL** | **80%** | **%** | ☐ |

Verificar:
```bash
./vendor/bin/pest --coverage
# Espera: 80%+ overall
```

---

## 🎯 MÉTRICAS DE SUCESSO

Ao fim da etapa:

- [ ] Todos checkboxes verdes ✅
- [ ] Testes em verde: `./vendor/bin/pest`
- [ ] Lint OK: `./vendor/bin/pint --test`
- [ ] Cobertura ≥ 80%: `./vendor/bin/pest --coverage`
- [ ] Zero dd() ou console.log()
- [ ] Sem TODOs em código
- [ ] Documentação atualizada
- [ ] Backup realizado
- [ ] Git commitado e pushado
- [ ] Pronto para merge

---

## 🔄 WORKFLOW DIÁRIO

### Cada Dia

- [ ] Atualizar este checklist (10 min)
- [ ] Rodar testes: `./vendor/bin/pest` ✅
- [ ] Formatar código: `./vendor/bin/pint` ✅
- [ ] Commitar progresso: `git add . && git commit -m "..."`
- [ ] Push: `git push origin etapa-1.1`

### Fim do Dia

- [ ] Todos testes em verde
- [ ] Código formatado
- [ ] Progresso documentado
- [ ] Backup (se fim de etapa)

### Fim da Etapa (Semana 2)

- [ ] Checklist 100% completo
- [ ] Testes: `./vendor/bin/pest Feature/` ✅
- [ ] Lint: `./vendor/bin/pint --test` ✅
- [ ] Backup OBRIGATÓRIO (ver BACKUP-RESTORE.md)
- [ ] Git: commit + push
- [ ] Pronto para merge → develop

---

## 📝 NOTAS & OBSERVAÇÕES

### O que está funcionando bem:
- (Adicionar conforme progride)

### Desafios encontrados:
- (Adicionar conforme progride)

### Pendências / Dívida técnica:
- (Adicionar conforme progride)

### Próximas etapas:
- [ ] Etapa 1.2: WhatsApp + API Limit
- [ ] Etapa 1.3: Link + Mobile MVP

---

## 🎓 REFERÊNCIAS

- **Laravel:** https://laravel.com/docs/13
- **Pest:** https://pestphp.com
- **Spatie Permission:** https://spatie.be/docs/laravel-permission
- **.cursorrules:** Veja arquivo na raiz
- **DOCS/:** Consulte documentação da pasta DOCS

---

## ✍️ HISTÓRICO DE ATUALIZAÇÕES

| Data | Atualizado Por | Progresso | Notas |
|------|---|---|---|
| 2026-01-06 | [Nome] | 0% | Iniciado |
| 2026-01-10 | [Nome] | 40% | Auth + Models OK |
| 2026-01-15 | [Nome] | 85% | Testes faltam |
| 2026-01-20 | [Nome] | 100% | ✅ COMPLETO |

---

**Status Final:** ☐ Em Progresso / 🟡 Aguardando Review / ✅ Concluído

**Próximo passo:** Atualizar este checklist diariamente e ao concluir, ler [BACKUP-RESTORE.md](./BACKUP-RESTORE.md)
