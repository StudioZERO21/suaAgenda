# CLAUDE.md

## Commands
```bash
composer dev                # Dev completo (serve + queue + pail + vite)
php artisan migrate --seed  # Migrations + seed
composer test               # Testes Pest
./vendor/bin/pint           # Formatar codigo
npm run build               # Build frontend
```

## Git & GitHub — REGRA OBRIGATÓRIA
- Repositório: https://github.com/StudioZERO21/suaAgenda.git
- Branch principal: `master` (base estável)
- Branches de trabalho: `etapa-X.Y` (uma por etapa)
- **Todo commit DEVE ser pushado para o GitHub** — é o backup oficial do projeto
- Fluxo por etapa:
  1. Trabalhar na branch `etapa-X.Y`
  2. `git push origin etapa-X.Y` ao fim de cada sessão
  3. Ao concluir a etapa: abrir PR `etapa-X.Y → master`, fazer merge, push do master
  4. Criar nova branch `etapa-X.(Y+1)` a partir do master atualizado
- Nunca force-push em `master`
- Executar `./vendor/bin/pint` e `composer test` antes de cada commit

## Stack
- PHP 8.4 + Laravel 13 | MySQL: suaAgenda | Arquitetura: Multi-Empresa
- Tailwind CSS 4 + Alpine.js 3 (CDN) | SweetAlert2 | Lucide Icons (CDN)
- ACL: spatie/laravel-permission 8.x com UUID | Roles: super_admin, admin_empresa, gestor, analista

## Conventions
- SweetAlert2 only — sem native alert() ou @error Blade
- Validacoes em FormRequest, nunca inline em controllers
- Sem jQuery, sem Bootstrap
- SoftDeletes em todo Model que guarda dados de usuario
- Gate::before garante que super_admin ignora todas as policies
- Chaves primarias UUID (HasUuids), strict_types=1 obrigatorio
- Profissional model: $table = 'profissionais' obrigatorio (pluralizacao errada sem isso)
- DevLoginController e rota /dev/login: SOMENTE quando app()->isLocal()

## Design System
- CSS vars --sa-*: primary #1a1a1a | secondary/accent #d4a574 | sidebar #111111
- Fontes: Inter (body) + Poppins (headings) via Google Fonts CDN
- Layouts: layouts/auth.blade.php (hero esquerdo) | layouts/app.blade.php (sidebar escura)
- Novas telas: seguir prototipos em DOCS/Layout/suaagenda/project/

## Users (Barbearia Teste)
- adrianoelite@msn.com — super_admin (sem empresa)
- adrianoelite1980@gmail.com — admin_empresa (Barbearia Teste)
- carlos@barbearia.test — gestor (Barbearia Teste)
- joao@barbearia.test — analista (Barbearia Teste)
- maria@cliente.test — sem role (Barbearia Teste)
- Senha padrao: StudioZERO21! (ALTERAR antes de producao!)
