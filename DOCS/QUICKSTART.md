# ⚡ QUICKSTART - suaAgenda.pro v2.0

**Objetivo:** Primeiras horas após setup - Entender estrutura e fazer primeiro commit  
**Tempo:** 30 min a 1 hora  
**Pré-requisito:** [SETUP.md](./SETUP.md) já completo

---

## 🎯 Neste Quickstart

- ✅ Entender a estrutura do projeto
- ✅ Explorar arquivos importantes
- ✅ Fazer primeiro commit
- ✅ Estar pronto para começar Etapa 1.1

---

## 1️⃣ Verificar Setup

Antes de tudo, confirme que setup foi bem-sucedido:

```bash
# Entrar no diretório
cd suaAgenda

# Ver versão Laravel
composer show laravel/framework

# Ver PHP
php -v
# Espera: PHP 8.4+

# Ver Node
node -v
# Espera: v18+

# Ver Git
git status
# Espera: On branch etapa-1.1 (ou develop)

# Verificar banco
php artisan tinker
>>> User::count()
# Espera: 2 (super_admin + admin_empresa)

>>> Company::count()
# Espera: 1 (empresa padrão)

>>> exit
```

✅ **Setup OK!** Prosseguir.

---

## 2️⃣ Explorar Estrutura

### Arquivos Importantes

```bash
# 📋 Raiz do projeto
.cursorrules           # ← LEIA: Regras para Cursor IDE
CLAUDE.md             # ← LEIA: Instruções para IA
README.md             # ← Está criado
.env                  # ← Suas configurações
composer.json         # ← Dependências PHP
package.json          # ← Dependências Node
```

### Pastas Principais

```bash
# 📂 Backend
tree app/             # Models, Controllers, Services
tree routes/          # Rotas (web.php, api.php)
tree database/        # Migrations, Factories, Seeders
tree tests/           # Testes Pest

# 🎨 Frontend
tree resources/       # Views, CSS, JS
tree public/          # Assets compilados

# 📚 Documentação
tree DOCS/            # Toda documentação aqui
```

### Arquivos a Estudar Hoje

Leia nesta ordem (15 min cada):

```bash
# 1. Regras de código
cat .cursorrules

# 2. Stack
cat CLAUDE.md

# 3. Documentação atual
cat DOCS/README.md
cat DOCS/ARCHITECTURE.md

# 4. Convenções
cat DOCS/CONVENTIONS.md
```

---

## 3️⃣ Entender Estrutura multi-tenancy

**Multi-tenancy** = Múltiplas empresas, dados isolados

### Como funciona

```
User (adrianoelite@msn.com)
  └─ Company (Salão da Carolina)
      ├─ Profissionais (5)
      ├─ Clientes (50)
      └─ Agendamentos (200)

User (outro@email.com)
  └─ Company (Barbearia do João)
      ├─ Profissionais (2)
      ├─ Clientes (30)
      └─ Agendamentos (100)

# Dados NÃO se misturam!
# Salão da Carolina NÃO vê dados da Barbearia do João
```

### Implementação

```bash
# Ver no banco
mysql -u root -p suaAgenda

# Ver usuários
SELECT id, name, email, company_id FROM users;

# Ver empresas
SELECT id, name FROM companies;

# Fechar MySQL
exit;
```

### Global Scope (automático)

Qualquer query faz automáticamente:

```php
// Você escreve:
User::all()

// Automaticamente fica:
User::where('company_id', Auth::user()->company_id)->all()

// Por isso, dados ficam isolados! 🔒
```

---

## 4️⃣ Iniciar Servidor

Abrir **3 terminais** (ou 1 terminal dividido):

**Terminal 1: Laravel Server**
```bash
php artisan serve
# Deve mostrar: Server started on [http://127.0.0.1:8000]
```

**Terminal 2: Queue Listener (background jobs)**
```bash
php artisan queue:listen
# Deve ficar aguardando jobs
```

**Terminal 3: Seu terminal de desenvolvimento**
```bash
# Aqui você faz commits, cria arquivos, etc
git status
```

**Ou tudo junto (mais fácil):**
```bash
composer dev
# Abre: Laravel + Queue + Pail + Vite em paralelo
```

---

## 5️⃣ Acessar Dashboard

Abrir navegador:

```
http://127.0.0.1:8000/login
```

**Credenciais:**
```
Email: adrianoelite@msn.com
Senha: StudioZERO21!
```

⚠️ **ALTERAR ESTA SENHA ANTES DE PRODUÇÃO!**

Se login funciona = Backend OK ✅

---

## 6️⃣ Fazer Primeiro Commit

Agora vamos documentar que exploramos o projeto:

```bash
# Ver o que mudou
git status

# Adicionar documentação lida
git add DOCS/

# Commit explicativo
git commit -m "docs(quickstart): exploração inicial do projeto

- Confirmado setup: PHP 8.4, Node 18+, MySQL 8.0
- Verificado banco de dados: 2 users, 1 company
- Explorado estrutura de pastas
- Lido .cursorrules e CLAUDE.md
- Entendido multi-tenancy
- Servidor rodando OK
- Pronto para Etapa 1.1"

# Push para remoto
git push origin etapa-1.1

# Confirmar
git log --oneline -3
```

---

## 7️⃣ Estrutura Branch

**Onde estamos:**
```
main (produção) ← não mexemos
└─ develop (staging) ← branch principal
   └─ etapa-1.1 ← VOCÊ ESTÁ AQUI
      └─ Commits seu trabalho
      
Ao fim: etapa-1.1 faz merge → develop
        e cria nova: etapa-1.2
```

**Comandos úteis:**
```bash
git branch -v              # Ver branches
git status                 # Ver mudanças
git log --oneline -5       # Ver commits recentes
git diff                   # Ver diferenças em arquivos
```

---

## 8️⃣ Estrutura de Testes

Verificar que testes estão rodando:

```bash
# Rodar testes
./vendor/bin/pest

# Espera:
# ✓ tests/Feature/ExampleTest.php (2 tests)
# ✓ tests/Feature/MigrationTest.php (12 tests)
# ────────────────────────────────
# 14 tests | 100% passed

# Cobertura
./vendor/bin/pest --coverage
# Espera: ~30-50% no início
```

---

## 9️⃣ Entender Arquitetura Atual

**Models (app/Models/):**
```
User ✅ (pronto)
  └─ Belongs to Company
     └─ Has many...

Company ✅ (pronto)
  └─ Has many Users
     └─ Has many...

(Outros models ainda a criar em Etapa 1.1)
```

**Controllers (app/Http/Controllers/):**
```
Auth/ ✅
  └─ LoginController (pronto)
  └─ RegisterController (pronto)

Outros ✅ (ainda a criar)
```

**Views (resources/views/):**
```
auth/ ✅ (básicas)
  └─ login.blade.php
  └─ register.blade.php

layouts/ ✅ (básico)
  └─ app.blade.php

dashboard/ ✅ (vazio, a criar)
```

---

## 🔟 Checklist Pós-Quickstart

- ☑️ Setup verificado
- ☑️ Lidos: .cursorrules, CLAUDE.md, DOCS/README.md
- ☑️ Entendido multi-tenancy
- ☑️ Servidor rodando
- ☑️ Dashboard acessível
- ☑️ Primeiro commit feito
- ☑️ Testes verdes
- ☑️ Git branch correto
- ☑️ Pronto para Etapa 1.1

---

## 📚 Próximo Passo

**Agora você está pronto para começar Etapa 1.1!**

1. **Leia:** [ETAPAS.md](./ETAPAS.md) - Seção "ETAPA 1.1"
2. **Copie:** `CHECKLIST-ETAPA.md` → `CHECKLIST-ETAPA-1.1.md`
3. **Comece:** Models + Migrations para Agendamento
4. **Acompanhe:** Checklist diariamente

---

## 💡 Dicas Importantes

### Desenvolvimento

```bash
# Enquanto desenvolve, rode sempre:
./vendor/bin/pest             # Testes
./vendor/bin/pint             # Formatação

# Se der erro, veja stack trace
php artisan tinker            # Debug interativo
```

### Commits

```bash
# Commit frequente (1-2x por dia)
git add .
git commit -m "feat(etapa-1.1): descrever o que fez"
git push origin etapa-1.1
```

### Documentação

```bash
# Atualizar conforme implementa
DOCS/ARCHITECTURE.md          # Adicione diagrama
DOCS/DATABASE-SCHEMA.md       # Adicione relacionamentos
DOCS/CONVENTIONS.md           # Consulte padrões
```

---

## 🚀 Se Tiver Dúvidas

**Responder sozinho:**

1. Está no .cursorrules? → Leia lá primeiro
2. Está na documentação? → Procure em DOCS/
3. É sobre padrão? → Veja DOCS/CONVENTIONS.md
4. É sobre banco? → Veja DOCS/DATABASE-SCHEMA.md
5. É sobre git? → Veja DOCS/GIT-WORKFLOW.md

**Se ainda tiver dúvida:**

- Procure na [documentação oficial](https://laravel.com/docs/13)
- Teste em `php artisan tinker`
- Pergunte ao Cursor (Claude) com contexto do projeto

---

**Próximo Documento:** [ETAPAS.md](./ETAPAS.md) - Etapa 1.1 em detalhes
