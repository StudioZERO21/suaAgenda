# 📋 CONVENÇÕES DE CÓDIGO - suaAgenda.pro

**Objetivo:** Padronizar estilo de código em todo projeto  
**Enforcer:** Pest + Pint + .cursorrules  
**Versão:** 1.0

---

## 🔧 FERRAMENTAS

| Ferramenta | Uso | Comando |
|---|---|---|
| **Pint** | Formatação PHP | `./vendor/bin/pint` |
| **Pest** | Testes | `./vendor/bin/pest` |
| **Node/npm** | Build frontend | `npm run build` |
| **ESLint** | Lint JS (futuro) | `npm run lint` |

---

## 📝 PHP

### Estrutura de Arquivo

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;
    
    // Código aqui
}
```

**Regras:**
- ✅ declare(strict_types=1) OBRIGATÓRIO primeira linha
- ✅ namespace após declaração
- ✅ Imports em ordem: Illuminate → App → Custom
- ✅ Traits após namespace/imports
- ✅ Comentários docblock acima de classes

### Nomenclatura

#### Classes
```php
class UserController { }        // ✅ PascalCase
class CreateUserRequest { }     // ✅ Request/FormRequest
class UserPolicy { }            // ✅ Policy
class UserService { }           // ✅ Service
class UserRepository { }        // ✅ Repository
```

#### Métodos
```php
public function getUserById($id) { }       // ✅ camelCase
public function isActive(): bool { }       // ✅ com type hint
private function validateEmail(): void { } // ✅ void explícito
```

#### Variáveis
```php
$userId = 1;                      // ✅ camelCase
$companyId = $request->company_id; // ✅ snake_case em DB, camelCase em PHP
$active = true;                   // ✅ bool sem "is_" prefix em variável
```

#### Constantes
```php
const DEFAULT_ROLE = 'user';           // ✅ UPPER_SNAKE_CASE
public const STATUSES = ['active'];    // ✅ public/private explícito
```

### Imports

```php
// ✅ BOM - organizado
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\UserService;

// ❌ RUIM - desordenado
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
```

### Type Hints

```php
// ✅ BOM - completo
public function store(StoreUserRequest $request): JsonResponse
{
    $user = User::create($request->validated());
    return response()->json($user, 201);
}

// ❌ RUIM - sem types
public function store($request)
{
    $user = User::create($request->all());
    return response()->json($user);
}
```

### Docblocks

```php
/**
 * Criar novo usuário
 *
 * @param  StoreUserRequest  $request
 * @return JsonResponse
 *
 * @throws ValidationException
 */
public function store(StoreUserRequest $request): JsonResponse
{
    // ...
}

/**
 * @var Collection<int, User>
 */
private Collection $users;
```

### Controle de Fluxo

```php
// ✅ BOM - claro e simples
if ($user->isActive()) {
    return $user;
}

return null;

// ❌ RUIM - ternário complexo
return $user->isActive() ? $user : null;

// ✅ OK - ternário simples
$status = $user->isActive() ? 'active' : 'inactive';

// ❌ RUIM - muitos níveis
if ($user) {
    if ($user->isActive()) {
        if ($user->hasRole('admin')) {
            // fazer algo
        }
    }
}

// ✅ BOM - early return
if (!$user || !$user->isActive() || !$user->hasRole('admin')) {
    return;
}
// fazer algo
```

### Tamanho de Métodos

```php
// ✅ BOM - < 30 linhas
public function store(StoreUserRequest $request): User
{
    $user = User::create($request->validated());
    event(new UserCreated($user));
    return $user;
}

// ❌ RUIM - > 50 linhas (extrair em Service)
public function store(StoreUserRequest $request): User
{
    $data = $request->validated();
    // 50 linhas de lógica...
}

// ✅ REFACTOR - usar Service
public function store(StoreUserRequest $request, UserService $service): User
{
    return $service->create($request->validated());
}
```

### Collections

```php
// ✅ BOM
$users = User::with('company')->get();
foreach ($users as $user) {
    echo $user->name;
}

// ❌ RUIM
$users = User::get();
foreach ($users as $user) {
    echo $user->company->name;  // N+1!
}

// ✅ BOM - use map
$names = $users->map(fn($u) => $u->name);

// ✅ BOM - use filter
$active = $users->filter(fn($u) => $u->isActive());
```

---

## 🎨 BLADE TEMPLATES

### Estrutura Básica

```blade
@extends('layouts.app')

@section('title', 'Título da Página')

@section('content')
    <div class="container">
        <h1>{{ $title }}</h1>

        @if ($isEmpty)
            <p>Sem dados</p>
        @else
            <table>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>Vazio</td>
                    </tr>
                @endforelse
            </table>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        // JS aqui
    </script>
@endpush
```

### Variáveis

```blade
<!-- ✅ BOM -->
{{ $user->name }}
{{ $user?->name }}  <!-- Safe navigation -->
{{ $items->count() }}

<!-- ❌ RUIM -->
{{ $user['name'] }}  <!-- Use object, não array -->
<?php echo $user->name; ?>  <!-- Use {{ }} -->
```

### Condicionais

```blade
<!-- ✅ BOM -->
@if ($user)
    <p>{{ $user->name }}</p>
@else
    <p>Sem usuário</p>
@endif

<!-- ✅ MELHOR -->
@forelse ($users as $user)
    <p>{{ $user->name }}</p>
@empty
    <p>Sem usuários</p>
@endforelse

<!-- ❌ RUIM -->
@if (count($users) > 0)
    @foreach ($users as $user)
        ...
    @endforeach
@endif
```

### Componentes

```blade
<!-- Usar components -->
<x-button-primary label="Salvar" />
<x-form-input name="email" label="Email" type="email" required />
<x-alert type="success" message="Sucesso!" />

<!-- Define em: resources/views/components/ -->
<!-- Nomeação: button-primary.blade.php (kebab-case) -->
```

### Alpine.js

```blade
<!-- ✅ BOM -->
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Conteúdo</div>
</div>

<!-- ✅ COM ESTADO -->
<div x-data="{ count: 0 }">
    <button @click="count++">{{ count }}</button>
</div>

<!-- ❌ RUIM -->
<button onclick="toggleDiv()">Toggle</button>
<script>function toggleDiv() { ... }</script>
```

### Tailwind CSS

```blade
<!-- ✅ BOM - Utility first -->
<div class="bg-white p-4 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Título</h2>
    <p class="text-gray-600">Descrição</p>
</div>

<!-- ✅ RESPONSIVO -->
<div class="text-sm md:text-base lg:text-lg">
    Texto adapta conforme tela
</div>

<!-- ✅ DARK MODE -->
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    Dark mode ready
</div>

<!-- ❌ RUIM - custom CSS -->
<div class="custom-container">
    <!-- Evitar custom CSS desnecessário -->
</div>
```

---

## 🧪 TESTES (Pest)

### Estrutura de Teste

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    // ✅ BOM - nome descritivo
    test('user can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(302);
        $this->assertAuthenticated();
    });

    // ❌ RUIM - nome genérico
    test('login', function () {
        // ...
    });

    // ✅ BOM - com múltiplos casos
    describe('validation', function () {
        test('email is required', function () {
            // ...
        });

        test('password is required', function () {
            // ...
        });
    });
}
```

### Asserções

```php
// ✅ BOM
expect($response->status())->toBe(200);
expect($user->isActive())->toBeTrue();
expect($collection->count())->toBe(5);
expect($string)->toContain('test');

// ❌ RUIM (Pest é mais claro)
$this->assertEquals(200, $response->status());
```

### Factories

```php
// ✅ BOM - usar factories
$user = User::factory()->create();
$users = User::factory(10)->create();

// ❌ RUIM - dados hardcoded
$user = new User([
    'name' => 'Test',
    'email' => 'test@test.com',
]);
```

---

## 📦 ESTRUTURA DE PASTAS

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   └── RegisterController.php
│   │   ├── AgendamentoController.php
│   │   └── DashboardController.php
│   ├── Requests/
│   │   ├── StoreAgendamentoRequest.php
│   │   └── UpdateAgendamentoRequest.php
│   ├── Resources/
│   │   ├── AgendamentoResource.php
│   │   └── UserResource.php
│   └── Middleware/
│       ├── SetTenantMiddleware.php
│       └── VerifyCompanyAccess.php
├── Models/
│   ├── User.php
│   ├── Company.php
│   ├── Agendamento.php
│   └── Cliente.php
├── Policies/
│   ├── AgendamentoPolicy.php
│   └── UserPolicy.php
├── Traits/
│   ├── HasCompany.php
│   └── IsAuditable.php
├── Scopes/
│   └── CompanyScope.php
├── Services/
│   ├── AgendamentoService.php
│   └── WhatsAppService.php
├── Jobs/
│   ├── SendWhatsAppMessage.php
│   └── ProcessAgendamento.php
└── Providers/
    ├── AuthServiceProvider.php
    └── AppServiceProvider.php

resources/
├── views/
│   ├── components/
│   │   ├── button-primary.blade.php
│   │   ├── form-input.blade.php
│   │   └── alert.blade.php
│   ├── layouts/
│   │   ├── app.blade.php
│   │   └── auth.blade.php
│   ├── auth/
│   │   ├── login.blade.php
│   │   └── register.blade.php
│   └── dashboard/
│       ├── index.blade.php
│       └── calendario.blade.php
├── css/
│   └── app.css
└── js/
    └── app.js

tests/
├── Feature/
│   ├── AuthTest.php
│   ├── AgendamentoTest.php
│   └── MultiTenancyTest.php
└── Unit/
    ├── Models/
    │   ├── UserTest.php
    │   └── AgendamentoTest.php
    └── Services/
        └── AgendamentoServiceTest.php
```

---

## 🔄 GIT COMMITS

### Formato

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Exemplos

```bash
# Feature
git commit -m "feat(agendamento): adicionar lock Redis

- Implementado WhatsAppLimitService
- Adicionado testes em Feature/AgendamentoTest.php
- Documentação em DOCS/API-SPECIFICATION.md"

# Bugfix
git commit -m "fix(auth): corrigir timeout login OAuth

Problema: Query N+1 em User::with('companies')
Solução: Eager load relationships
Testes: Feature/AuthTest.php (passou)"

# Formatação
git commit -m "style: executar ./vendor/bin/pint"

# Documentação
git commit -m "docs(README): adicionar instrução setup"
```

---

## 🎯 CHECKLIST PRÉ-COMMIT

Antes de fazer `git commit`:

- [ ] ./vendor/bin/pint (formatação OK)
- [ ] ./vendor/bin/pest (testes verdes)
- [ ] Sem dd(), console.log(), debugger
- [ ] Sem TODOs ou FIXMEs
- [ ] Sem imports desnecessários
- [ ] Mensagem de commit descritiva
- [ ] .env não commitado
- [ ] Secrets não expostos

---

## 📞 REFERÊNCIA RÁPIDA

```bash
# Formatar código
./vendor/bin/pint

# Rodar testes
./vendor/bin/pest

# Verificar cobertura
./vendor/bin/pest --coverage

# Lint com reportagem
./vendor/bin/pint --test

# Build frontend
npm run build

# Dev frontend
npm run dev
```

---

**Próximo passo:** Começar desenvolvimento seguindo a Etapa 1.1 em [ETAPAS.md](./ETAPAS.md)
