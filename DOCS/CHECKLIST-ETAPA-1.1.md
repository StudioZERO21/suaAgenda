# ✅ CHECKLIST ETAPA 1.1 - suaAgenda.pro

**Etapa:** 1.1  
**Sprint:** 1-2 (2 semanas)  
**Tema:** Setup + Autenticação + Agendamento (Lock Temporal)  
**Data Início:** [Editar]  
**Data Fim Esperada:** [Editar]  
**Status:** 🔵 EM ANDAMENTO  
**Desenvolvedor:** [Seu Nome]

---

## 🎯 OBJETIVOS DA ETAPA

- ✅ Projeto Laravel 13 criado
- ✅ Multi-tenancy configurado
- ✅ Autenticação (email + senha, OAuth Google)
- ✅ CRUD Agendamento com lock Redis
- ✅ Primeiros testes (80%+)
- ✅ Documentação básica

---

## 🏗️ MODELS & BANCO DE DADOS

### Models a Criar

- [ ] **User** (modificar existente)
  - [x] Migration existe
  - [ ] Adicionar: company_id, ativo, deleted_at
  - [ ] Relationships: belongsTo Company
  - [ ] Scope: ativo()

- [ ] **Company** (nova)
  - [ ] Model criado
  - [ ] Migration criada
  - [ ] Campos: id, name, slug, plan, whatsapp, lgpd_consent
  - [ ] SoftDeletes implementado
  - [ ] Relationships: hasMany Users, hasMany Agendamentos

- [ ] **Profissional** (nova)
  - [ ] Model criado
  - [ ] Migration criada
  - [ ] Campos: id, company_id, name, especialidade, ativo
  - [ ] SoftDeletes implementado

- [ ] **Cliente** (nova)
  - [ ] Model criado
  - [ ] Migration criada
  - [ ] Campos: id, company_id, name, phone, email, data_nasc
  - [ ] SoftDeletes implementado

- [ ] **Agendamento** (nova)
  - [ ] Model criado
  - [ ] Migration criada
  - [ ] Campos: id, company_id, profissional_id, cliente_id, data_hora, duracao, status
  - [ ] Status enum: pendente, confirmado, finalizado, cancelado
  - [ ] SoftDeletes implementado
  - [ ] Relationships: belongsTo Company, Profissional, Cliente
  - [ ] Scopes: ativo(), por_data(), por_profissional()

### Validações

- [ ] **User:**
  - [ ] email único
  - [ ] name obrigatório
  - [ ] company_id obrigatório (após login)

- [ ] **Agendamento:**
  - [ ] data_hora obrigatória
  - [ ] duracao > 0
  - [ ] profissional_id obrigatório
  - [ ] cliente_id obrigatório

---

## 🔐 AUTENTICAÇÃO & AUTORIZAÇÃO

### Controllers

- [ ] **Auth/LoginController**
  - [ ] Login por email/senha
  - [ ] Logout
  - [ ] Rate limiting (5 tentativas/5min)
  - [ ] Tests passando

- [ ] **Auth/RegisterController**
  - [ ] Registro de novo usuário
  - [ ] Criação automática de Company
  - [ ] Validações completas
  - [ ] Tests passando

- [ ] **AgendamentoController**
  - [ ] store() - criar agendamento com lock Redis
  - [ ] update() - editar
  - [ ] destroy() - cancelar (soft delete)
  - [ ] index() - listar com paginação
  - [ ] show() - ver detalhes

### Policies

- [ ] **AgendamentoPolicy**
  - [ ] viewAny()
  - [ ] view()
  - [ ] create()
  - [ ] update()
  - [ ] delete()
  - [ ] Tests passando

### Middleware

- [ ] **SetTenantMiddleware**
  - [ ] Extrai company_id
  - [ ] Valida acesso
  - [ ] Injeta no request

### Form Requests

- [ ] **StoreAgendamentoRequest**
  - [ ] Validações completas
  - [ ] Mensagens em português
  - [ ] authorize()

- [ ] **UpdateAgendamentoRequest**
  - [ ] Validações completas
  - [ ] authorize()

---

## 🎨 VIEWS & FRONTEND

### Components Blade

- [ ] **components/button-primary.blade.php**
- [ ] **components/form-input.blade.php**
- [ ] **components/alert.blade.php**
- [ ] **components/calendar-widget.blade.php**

### Páginas

- [ ] **auth/login.blade.php** - completo
- [ ] **auth/register.blade.php** - completo
- [ ] **dashboard/index.blade.php** - básico
- [ ] **dashboard/calendario.blade.php** - calendário visual

### Layouts

- [ ] **layouts/app.blade.php** - responsivo
- [ ] **layouts/auth.blade.php** - simples

---

## 🧪 TESTES (Pest)

### Feature Tests

- [ ] **tests/Feature/AuthTest.php**
  - [ ] test('login com email e senha')
  - [ ] test('login sem credenciais')
  - [ ] test('logout')
  - [ ] test('rate limit login')

- [ ] **tests/Feature/AgendamentoTest.php**
  - [ ] test('criar agendamento')
  - [ ] test('evitar double booking com lock Redis')
  - [ ] test('cancelar agendamento')
  - [ ] test('usuário não vê agendamento de outra empresa')

- [ ] **tests/Feature/MultiTenancyTest.php**
  - [ ] test('dados isolados por company_id')
  - [ ] test('middleware SetTenant funciona')

### Unit Tests

- [ ] **tests/Unit/Models/AgendamentoTest.php**
  - [ ] test('agendamento pode ser criado')
  - [ ] test('soft delete funciona')

---

## 🔒 SEGURANÇA & LGPD

### Autenticação

- [ ] Passwords hash Bcrypt
- [ ] HTTPS em produção
- [ ] CSRF protection
- [ ] Rate limiting login

### Autorização

- [ ] Gate::before super_admin
- [ ] Policies implementadas
- [ ] Multi-tenancy validado

### LGPD

- [ ] Checkbox consentimento em register
- [ ] Soft deletes funcionando
- [ ] Privacy policy link

---

## 📚 DOCUMENTAÇÃO

- [ ] **ARCHITECTURE.md** - Diagrama básico
- [ ] **DATABASE-SCHEMA.md** - ER diagram
- [ ] **Comentários no código** - Docblocks
- [ ] **README atualizado** - Features descritas

---

## 🔧 CÓDIGO & QUALIDADE

### Padrões

- [ ] strict_types=1 em todo PHP
- [ ] PSR-12 formatação (./vendor/bin/pint)
- [ ] Sem imports desnecessários
- [ ] Sem dd(), console.log()

### Lint & Tests

- [ ] ./vendor/bin/pint --test ✅
- [ ] ./vendor/bin/pest ✅ (100% pass)
- [ ] ./vendor/bin/pest --coverage ✅ (80%+)

### Funcionalidade

- [ ] Sem N+1 queries
- [ ] with() aplicado
- [ ] Índices no banco
- [ ] Error handling correto

---

## 💾 BACKUP & GIT

- [ ] Backup banco: `BACKUPS/backup-etapa-1.1.sql`
- [ ] Backup código: `BACKUPS/backup-etapa-1.1.zip`
- [ ] Git: commits descritivos
- [ ] Git: pushed para origin
- [ ] Git tag: v1.1.0-beta (opcional)

---

## ✅ CHECKLIST PRÉ-MERGE

Antes de fazer merge para develop:

- [ ] Todos testes passando (./vendor/bin/pest)
- [ ] Cobertura ≥ 80%
- [ ] Lint OK (./vendor/bin/pint --test)
- [ ] Sem dd(), console.log(), TODOs
- [ ] Backup realizado
- [ ] Git pushado
- [ ] Documentação atualizada
- [ ] README.md atualizado

---

## 📊 PROGRESSO DIÁRIO

| Data | Tarefa | Status | Notas |
|------|--------|--------|-------|
| - | - | - | - |

---

## 🎯 TESTE DE ACEITAÇÃO FINAL

Ao completar etapa, executar:

```bash
# 1. Testes
./vendor/bin/pest tests/Feature/MigrationTest.php    # ✅ PASS
./vendor/bin/pest tests/Feature/AuthTest.php          # ✅ PASS
./vendor/bin/pest tests/Feature/AgendamentoTest.php   # ✅ PASS

# 2. Lint
./vendor/bin/pint --test                              # ✅ OK

# 3. Cobertura
./vendor/bin/pest --coverage                          # ✅ >= 80%

# 4. Banco
php artisan tinker
>>> User::count()          # 2
>>> Company::count()        # 1
>>> Agendamento::count()    # 0 (ok)

# 5. Acesso
http://127.0.0.1:8000/login       # OK
http://127.0.0.1:8000/dashboard   # Redireciona

# ✅ ETAPA 1.1 COMPLETA!
```

---

## 📝 NOTAS & OBSERVAÇÕES

### O que está funcionando bem:
- (Adicionar conforme progride)

### Desafios encontrados:
- (Adicionar conforme progride)

### Pendências / Dívida técnica:
- (Adicionar conforme progride)

---

**Status:** ☐ Em Progresso / ☐ Aguardando Review / ☐ Concluído

**Próximo:** Etapa 1.2 - WhatsApp + API Limits
