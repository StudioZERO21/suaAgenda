# CLAUDE.md

## Commands
```bash
composer dev                # Dev completo (serve + queue + pail + vite)
php artisan migrate --seed  # Migrations + seed
composer test               # Testes Pest
./vendor/bin/pint           # Formatar codigo
npm run build               # Build frontend
```

## Stack
- PHP 8.4 + Laravel 13 | MySQL: suaAgenda | Arquitetura: Multi-Empresa
- Tailwind CSS 4 + Alpine.js 3 (CDN) | SweetAlert2 | Lucide Icons (CDN)
- ACL: spatie/laravel-permission 7.x | Roles: super_admin, admin_empresa, gestor, analista
## Conventions
- SweetAlert2 only — sem native alert() ou @error Blade
- Validacoes em FormRequest, nunca inline em controllers
- Sem jQuery, sem Bootstrap
- SoftDeletes em todo Model que guarda dados de usuario
- Gate::before garante que super_admin ignora todas as policies
- Chaves primarias UUID (HasUuids), strict_types=1 obrigatorio

## Users
- adrianoelite@msn.com — super_admin
- adrianoelite1980@gmail.com — admin_empresa
- Senha padrao: StudioZERO21! (ALTERAR antes de producao!)