# 🚀 SETUP INICIAL - suaAgenda.pro v2.0

**Tempo estimado:** 30-45 minutos  
**Pré-requisitos:** PHP 8.4+, Node.js, Composer, MySQL 8.0+, Git  
**Gerado por:** Clip de Papel v3.3

---

## ✅ PRÉ-REQUISITOS VERIFICADOS

Antes de iniciar, verifique que tem tudo instalado:

```bash
php -v          # PHP 8.4 ou superior
node -v         # Node.js 18+
composer -V     # Composer 2.x
git --version   # Git 2.x
mysql -V        # MySQL 8.0+
```

Se algum não estiver instalado, consulte a documentação oficial:
- [PHP](https://www.php.net/downloads)
- [Node.js](https://nodejs.org/)
- [Composer](https://getcomposer.org/download/)
- [Git](https://git-scm.com/)
- [MySQL](https://dev.mysql.com/downloads/mysql/)

---

## 🔧 OPÇÃO 1: SETUP AUTOMATIZADO (Recomendado Windows)

Se você está em **Windows com PowerShell**:

```powershell
# 1. Baixar o script Clip de Papel
# (Já está em /DOCS/clip-de-papel.ps1)

# 2. Executar (como administrador):
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
.\clip-de-papel.ps1

# O script fará TUDO automaticamente:
# ✅ Criar projeto Laravel 13
# ✅ Instalar dependências (Composer + npm)
# ✅ Criar banco de dados MySQL
# ✅ Rodar migrations e seeders
# ✅ Inicializar Git + branches
# ✅ Abrir Cursor IDE
# ✅ Iniciar servidor
```

**Se o script completou com sucesso, pule para "✅ Após Setup"**

---

## 🔧 OPÇÃO 2: SETUP MANUAL (macOS/Linux/Windows WSL)

### Etapa 1: Clonar e preparar repositório

```bash
# 1. Clonar repositório
git clone https://github.com/StudioZERO21/suaAgenda.git
cd suaAgenda

# 2. Copiar .env
cp .env.example .env

# 3. Instalar dependências Composer
composer install

# 4. Gerar APP_KEY
php artisan key:generate

# 5. Instalar dependências npm
npm install
npm install sweetalert2
```

### Etapa 2: Configurar banco de dados

```bash
# 1. Criar banco de dados
mysql -u root -p
# Entrar no MySQL e executar:
# CREATE DATABASE suaAgenda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# EXIT;

# 2. Configurar .env
# Edite .env e configure:
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=suaAgenda
DB_USERNAME=root
DB_PASSWORD=sua_senha_aqui
```

### Etapa 3: Rodar migrations e seeders

```bash
# 1. Rodar migrations
php artisan migrate --force

# 2. Rodar seeders
php artisan db:seed

# 3. Criar storage link
php artisan storage:link

# 4. Limpar cache
php artisan optimize:clear
```

### Etapa 4: Configurar Git

```bash
# 1. Inicializar Git
git init

# 2. Configurar remote (se não clonou)
git remote add origin https://github.com/StudioZERO21/suaAgenda.git

# 3. Criar branches de trabalho
git branch etapa-1.1
git checkout etapa-1.1

# 4. Commit inicial
git add .
git commit -m "chore: setup inicial concluído"
git push origin etapa-1.1
```

### Etapa 5: Compilar assets

```bash
# Build frontend
npm run build

# Ou modo desenvolvimento (watch)
npm run dev
```

---

## ✅ APÓS SETUP

### 1. Verificar Instalação

Execute o script de verificação:

```bash
# Teste rápido via tinker
php artisan tinker
>>> User::count()          # Deve retornar 2
>>> Role::pluck('name')    # Deve ter super_admin, admin_empresa
>>> exit
```

Ou rode os testes:

```bash
./vendor/bin/pest tests/Feature/MigrationTest.php
```

### 2. Iniciar Servidor

**Opção A: Desenvolvimento completo (recomendado)**

```bash
composer dev
```

Isso abre em paralelo:
- 🌐 Laravel serve (porta 8000)
- 📋 Queue listener
- 📊 Pail (logs em tempo real)
- 🎨 Vite dev server

**Opção B: Apenas Laravel server**

```bash
php artisan serve
```

**Opção C: Com Docker (opcional)**

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
```

### 3. Acessar Dashboard

```
URL: http://127.0.0.1:8000/login

Credenciais (ALTERAR IMEDIATAMENTE):
  Email: adrianoelite@msn.com
  Senha: StudioZERO21!
```

### 4. Estrutura de Pastas Criadas

```
app/
├── Http/
│   ├── Controllers/Auth/    ← Controllers de autenticação
│   ├── Requests/            ← Form Requests (validação)
│   └── Controllers/         ← Controllers principais
├── Models/                  ← Modelos Eloquent
├── Policies/                ← Policies de autorização
├── Traits/                  ← Traits reutilizáveis
├── Scopes/                  ← Global Scopes
└── Domain/                  ← Lógica de negócio

resources/
├── views/
│   ├── components/          ← Componentes Blade
│   ├── layouts/             ← Layouts principais
│   └── ...
├── css/
└── js/

tests/
├── Feature/                 ← Testes de integração
└── Unit/                    ← Testes unitários

database/
├── migrations/
├── factories/
└── seeders/
```

### 5. Verificar Arquivos de Configuração

```bash
# .cursorrules (regras para Cursor IDE)
cat .cursorrules

# CLAUDE.md (instruções para Claude)
cat CLAUDE.md

# composer.json (verificar script 'dev')
cat composer.json | grep -A 5 '"dev"'
```

---

## 🔐 SEGURANÇA INICIAL

⚠️ **ALTERAR IMEDIATAMENTE:**

### 1. Mudar senhas padrão

```bash
php artisan tinker
>>> $user = User::where('email', 'adrianoelite@msn.com')->first();
>>> $user->password = Hash::make('SenhaNovaForte123!');
>>> $user->save();
>>> exit
```

### 2. Gerar nova APP_KEY

```bash
php artisan key:generate
# Já foi feito no setup, mas verificar no .env
echo $APP_KEY
```

### 3. Configurar Timezone e Locale

Verificar no `.env`:

```env
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
```

---

## 📊 VERIFICAÇÃO FINAL

Execute esta checklist:

```bash
# 1. Banco de dados
php artisan migrate:status      # Deve mostrar "Ran" em todas

# 2. Usuários
php artisan tinker
>>> User::count()               # Deve ser 2
>>> User::pluck('email')        # Deve ter as 2 contas

# 3. Roles e Permissions
>>> Role::pluck('name')         # super_admin, admin_empresa
>>> Permission::count()         # Deve ter muitas

# 4. Testes
exit
./vendor/bin/pest tests/Feature/MigrationTest.php    # Deve passar

# 5. Lint
./vendor/bin/pint --test

# 6. Storage
ls -la public/storage           # Deve ser symlink
```

---

## 🚨 SOLUÇÃO DE PROBLEMAS

### Erro: "SQLSTATE[HY000]: General error: 1030"

```bash
# Banco de dados não existe. Crie:
mysql -u root -p
CREATE DATABASE suaAgenda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Depois rode:
php artisan migrate --force
```

### Erro: "Could not find driver"

```bash
# PHP não tem extensão MySQL. Instale:

# Windows (XAMPP)
php.ini → descomente: extension=mysqli
php.ini → descomente: extension=pdo_mysql

# macOS
brew install php@8.4
brew tap shivammathur/php
brew tap-new local/homebrew-local
brew install-options php@8.4 --with-mysql

# Linux
sudo apt-get install php8.4-mysql
```

### Erro: "Composer update" trava

```bash
# Use opções de memória:
composer install -d $(composer config --global home)
# Ou aumenta memória PHP:
php -d memory_limit=-1 /usr/local/bin/composer install
```

### Erro: npm build falha

```bash
# Limpar cache:
npm cache clean --force
rm -rf node_modules package-lock.json

# Reinstalar:
npm install
npm run build
```

### Erro: "Port 8000 already in use"

```bash
# Use porta diferente:
php artisan serve --port=8001

# Ou mate processo:
# Windows: netstat -ano | findstr :8000
# macOS/Linux: lsof -i :8000 | kill -9 <PID>
```

---

## 📝 PRÓXIMOS PASSOS

Após setup bem-sucedido:

1. **Ler documentação:**
   - Abrir [/DOCS/README.md](./README.md)
   - Ler [/DOCS/ARCHITECTURE.md](./ARCHITECTURE.md)
   - Estudar [/DOCS/GIT-WORKFLOW.md](./GIT-WORKFLOW.md)

2. **Começar Etapa 1.1:**
   - Abrir [/DOCS/ETAPAS.md](./ETAPAS.md)
   - Marcar checklist em [/DOCS/CHECKLIST-ETAPA.md](./CHECKLIST-ETAPA.md)

3. **Familiarizar com stack:**
   - Laravel: https://laravel.com/docs/13
   - Blade: https://laravel.com/docs/13/blade
   - Alpine.js: https://alpinejs.dev
   - Tailwind: https://tailwindcss.com/docs

4. **Entender multi-tenancy:**
   - Cada usuário = uma empresa (tenant)
   - Isolamento de dados por company_id
   - Middleware automático

5. **Familiarizar com testes:**
   - Teste unitário: `tests/Unit/`
   - Teste de feature: `tests/Feature/`
   - Rodar: `./vendor/bin/pest`

---

## 🎯 CHECKLIST PRÉ-DESENVOLVIMENTO

Antes de começar a codificar, verifique:

- ☑️ Servidor rodando (`composer dev`)
- ☑️ Login funcionando (email/senha)
- ☑️ Banco acessível (`php artisan tinker`)
- ☑️ Git configurado (`git status`)
- ☑️ Branch correto (`git branch -v`)
- ☑️ Testes passando (`./vendor/bin/pest`)
- ☑️ IDE aberta (Cursor, VSCode, etc)
- ☑️ Terminal pronto (`composer dev`)
- ☑️ .cursorrules lido
- ☑️ Documentação acessível

---

## 📞 SUPORTE

Se tiver problemas:

1. **Consulte documentação:**
   - [/DOCS/](./README.md) - Índice completo
   - [.cursorrules](../.cursorrules) - Regras de código

2. **Verifique stack versões:**
   ```bash
   php -v
   node -v
   composer -V
   git --version
   ```

3. **Verifique .env:**
   ```bash
   cat .env | grep DB_
   cat .env | grep APP_
   ```

4. **Rode testes:**
   ```bash
   ./vendor/bin/pest tests/Feature/MigrationTest.php -v
   ```

---

**Parabéns! Setup inicial completo. Prossiga para [QUICKSTART.md](./QUICKSTART.md)**
