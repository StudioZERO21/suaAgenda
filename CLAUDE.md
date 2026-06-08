# CLAUDE.md

---

## ⚠️ REGRA IMUTÁVEL — CONSULTAR DESIGN ANTES DE QUALQUER VIEW

> **Esta regra NÃO pode ser ignorada, contornada ou esquecida. É a regra mais importante do projeto.**

Antes de criar ou modificar QUALQUER view Blade, você DEVE ler o arquivo JSX correspondente em:
**`DOCS/Layout/suaagenda/project/`**

Os JSX são a **única fonte de verdade** do design. CSS vars, espaçamentos, tipografia, componentes e comportamentos interativos são todos derivados desses protótipos. Se uma view parecer diferente do protótipo, o protótipo vence.

### Mapa de telas → arquivos JSX obrigatórios

| Tela / Feature | Arquivo JSX a ler |
|---|---|
| Dashboard | `DashboardScreen.jsx` |
| Clientes | `ClientsScreen.jsx` |
| Profissionais / Equipe | `StaffScreen.jsx` |
| Serviços | `ServicesScreen.jsx` |
| Agendamentos (lista) | `DashboardScreen.jsx` + `layout.jsx` |
| Calendário | `CalendarScreen.jsx` |
| Relatórios / Financeiro | `ReportsScreen.jsx` + `FinancialScreen.jsx` |
| Configurações / Empresa | `SiteSettingsScreen.jsx` |
| Planos | `PlansScreen.jsx` |
| Perfil do usuário | `layout.jsx` (AppHeader dropdown) |
| Permissões / Roles | `PermissionsScreen.jsx` + `RolesScreen.jsx` |
| Agendamento público | `PublicScreen.jsx` + `BookingModal.jsx` |
| Login / Recuperação | `LoginScreen.jsx` + `RecoverScreen.jsx` |
| Qualquer componente UI | `ui.jsx` (Btn, Inp, Sel, Badge, Card, TintCard, Avt, Modal) |
| Layout geral / Sidebar | `layout.jsx` (AppShell, AppHeader, Sidebar, BottomTabBar) |
| Modais | `Modals.jsx` + `BookingModal.jsx` |

**Protocolo obrigatório:**
1. Identificar qual tela será criada/editada
2. Ler o(s) JSX correspondente(s) **antes** de escrever qualquer linha Blade
3. Extrair: CSS vars usadas, espaçamentos exatos, tipografia, cores, comportamentos hover/focus
4. Implementar pixel-a-pixel conforme o protótipo

---

## Commands
```bash
composer dev                # Dev completo (serve + queue + pail + vite)
php artisan migrate --seed  # Migrations + seed
composer test               # Testes Pest
./vendor/bin/pint           # Formatar codigo
npm run build               # Build frontend
```

## Backup — OBRIGATÓRIO antes de qualquer migration
```bash
bash backup.sh [etapa]      # Exemplo: bash backup.sh 1.5
```
- Dumpa o banco suaAgenda em BACKUPS/db-etapa-*.sql.gz (local, gitignored)
- Executar ANTES de `php artisan migrate` em toda sessão de desenvolvimento
- mysqldump em D:\laragon3\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe

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
- Alpine.js 3 (CDN) | SweetAlert2 | Lucide/inline SVG Icons
- ACL: spatie/laravel-permission 8.x com UUID | Roles: super_admin, admin_empresa, gestor, analista
- **NÃO usar Tailwind classes no conteúdo das views** — apenas CSS vars via `style=""` inline

## Conventions (PHP/Laravel)
- SweetAlert2 only — sem native alert() ou @error Blade
- Validacoes em FormRequest, nunca inline em controllers
- Sem jQuery, sem Bootstrap
- SoftDeletes em todo Model que guarda dados de usuario
- Gate::before garante que super_admin ignora todas as policies
- Chaves primarias UUID (HasUuids), strict_types=1 obrigatorio
- Profissional model: `$table = 'profissionais'` obrigatorio (pluralizacao inglesa errada)
- DevLoginController e rota /dev/login: SOMENTE quando app()->isLocal()
- Rotas com plural não-inglês: usar `->parameters(['resource' => 'singular'])` no Route::resource

---

## Design System — REGRAS OBRIGATÓRIAS

> **Fonte de verdade:** `DOCS/Layout/suaagenda/project/` — os arquivos JSX definem o design canônico.
> Toda view nova DEVE seguir exatamente os padrões abaixo.

### CSS Variables (--sa-*)
```
--sa-primary:      #1a1a1a   → fundo de botão primário, avatar, textos fortes
--sa-primary-l:    #2d2d2d   → hover do primário
--sa-secondary:    #d4a574   → accent/ouro: ícones de destaque, valores em R$, logo dot
--sa-secondary-l:  #e6c299   → hover/tint do secondary (ouro claro)
--sa-bg:           #f5f5f5   → fundo geral do body
--sa-surface:      #ffffff   → fundo de cards, inputs, tabelas
--sa-surface2:     #fafafa   → fundo de table-header, row-hover, surface alternativo
--sa-text1:        #1a1a1a   → texto primário (headings, valores importantes)
--sa-text2:        #5a5a5a   → texto secundário (labels, conteúdo geral)
--sa-text3:        #999999   → texto terciário (placeholders, timestamps, subtítulos)
--sa-border:       #e2e2e2   → bordas de cards, inputs, dividers
--sa-border2:      #d0d0d0   → bordas mais escuras / separadores com mais contraste
--sa-side-bg:      #111111   → fundo da sidebar
--sa-side-text:    #eeeeee   → texto na sidebar
--sa-side-muted:   #888888   → texto muted na sidebar
--sa-side-accent:  #d4a574   → item ativo na sidebar (mesmo que secondary)
```
**NUNCA hardcodar valores de cor** — sempre `var(--sa-*)`.

---

### Estrutura da View (padrão obrigatório)
```blade
@extends('layouts.app')
@section('title', 'Título da Página')
@section('page-title', 'Título da Página')

@section('content')
{{-- AppHeader --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Título</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Subtítulo com contexto</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        {{-- botões de ação do header --}}
    </div>
</div>

{{-- Conteúdo --}}
<div style="max-width:1100px">
    ...
</div>
@endsection
```

---

### Card
```html
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);
            padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
```
- Padding padrão: **24px** (ou 20px em cards menores)
- Para cards de tabela: `padding:0;overflow:hidden` (a tabela preenche)
- Cards clicáveis: adicionar `cursor:pointer;transition:box-shadow 200ms` com hover `0 6px 20px rgba(0,0,0,.1)`

---

### Botão Primário
```html
<button style="display:inline-flex;align-items:center;gap:7px;
               padding:10px 18px;border-radius:8px;border:none;cursor:pointer;
               font-size:14px;font-weight:600;font-family:'Inter',sans-serif;
               background:var(--sa-primary);color:#fff;
               transition:filter 200ms"
        onmouseover="this.style.filter='brightness(1.1)'"
        onmouseout="this.style.filter='none'">
```
**ERRADO**: `onmouseover="this.style.background='var(--sa-secondary)'"` — NÃO mudar cor no hover.
**CORRETO**: usar `filter:brightness(1.1)` no hover do botão primário.

### Botão Secundário (outline)
```html
<a style="display:inline-flex;align-items:center;gap:7px;
          padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);
          background:transparent;color:var(--sa-text2);
          font-size:14px;font-weight:600;text-decoration:none;
          transition:border-color 180ms,color 180ms"
   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
```

### Botão Ícone (ações de tabela — 30×30)
```html
<button style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);
               background:transparent;cursor:pointer;display:flex;align-items:center;
               justify-content:center;color:var(--sa-text3);transition:all 150ms"
        onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
```
Botão destrutivo (delete): hover com `#ef4444` em vez de secondary.

---

### Input / Select / Textarea
```html
<label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
    Nome <span style="color:var(--sa-secondary)">*</span>
</label>
<input type="text" name="campo"
       style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);
              border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;
              color:var(--sa-text1);background:var(--sa-surface);
              outline:none;transition:border-color 180ms,outline 180ms"
       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
       onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
```
**CRÍTICO**: `focus = var(--sa-primary)` (preto) + glow `outline:3px solid rgba(0,0,0,.06)`, **NÃO** `var(--sa-secondary)` (ouro).
- Labels: `font-size:13px`, `color:var(--sa-text1)`, `letter-spacing:.2px` — NÃO text2/12px
- Input com erro: `border-color:#ef4444` inicial + `outline:3px solid rgba(239,68,68,.12)`, mensagem `<p style="font-size:12px;color:#ef4444;margin-top:4px">`
- Select: adicionar `appearance:none; background-image: url('chevron SVG data URI')`

---

### Badge de Status (com ponto colorido)
```html
{{-- Padrão obrigatório: dot + texto --}}
<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;
             border-radius:20px;font-size:12px;font-weight:600;
             background:rgba(16,185,129,.12);color:#059669">
    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
    Confirmado
</span>
```
Cores dos badges por status:
```
confirmado  → bg:rgba(16,185,129,.12)  color:#059669   (verde)
pendente    → bg:rgba(245,158,11,.12)  color:#d97706   (âmbar)
finalizado  → bg:rgba(107,114,128,.12) color:#6b7280   (cinza)
cancelado   → bg:rgba(239,68,68,.1)    color:#dc2626   (vermelho)
ativo       → bg:rgba(16,185,129,.12)  color:#059669   (verde)
inativo     → bg:rgba(107,114,128,.12) color:#6b7280   (cinza)
```

---

### Tabela (em Card padding:0)
```html
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;
                           color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;
                           white-space:nowrap">Coluna</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms"
                onmouseover="this.style.background='var(--sa-surface2)'"
                onmouseout="this.style.background='transparent'">
                <td style="padding:14px 16px;font-size:14px;color:var(--sa-text1)">Dado</td>
            </tr>
        </tbody>
    </table>
</div>
```
**Row hover: `var(--sa-surface2)`** — NÃO `rgba(0,0,0,.02)`.

---

### TintCard (card de estatística — Dashboard)
```html
<div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);
            border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);
            border-radius:16px;padding:22px 22px 0;
            position:relative;overflow:hidden;min-height:148px;
            display:flex;flex-direction:column">
    <div style="font-size:11px;font-weight:700;color:var(--sa-primary);
                letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">
        LABEL
    </div>
    <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;
                color:var(--sa-text1);line-height:1;letter-spacing:-1px">
        VALOR
    </div>
    {{-- Ícone sangrando no fundo --}}
    <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
        <svg width="130" height="130" .../>
    </div>
</div>
```
Para cards de receita (R$): trocar `var(--sa-primary)` por `var(--sa-secondary)` (ouro).

---

### Avatar (iniciais)
```html
<div style="width:34px;height:34px;border-radius:50%;background:var(--sa-primary);
            color:#fff;display:flex;align-items:center;justify-content:center;
            font-size:13px;font-weight:700;font-family:'Inter',sans-serif;flex-shrink:0">
    {{ strtoupper(substr($name, 0, 1)) }}
</div>
```

---

### Formulário (card de form)
```html
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
    <form style="display:flex;flex-direction:column;gap:20px">
        {{-- campos em grid 2 colunas quando aplicável --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            ...
        </div>
        {{-- botões de submit --}}
        <div style="display:flex;gap:10px;padding-top:8px">
            <button type="submit" ...>Salvar</button>
            <a href="..." ...>Cancelar</a>
        </div>
    </form>
</div>
```

---

### SweetAlert2 (confirmação de exclusão)
```js
Swal.fire({
    title: 'Excluir X?',
    text: 'Esta ação não pode ser desfeita.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, excluir',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: 'transparent',
    customClass: { cancelButton: 'swal-cancel-muted' },
}).then(r => { if (r.isConfirmed) form.submit(); });
```

---

### Regras Anti-Pattern (PROIBIDO)
```
❌ onmouseover="this.style.background='var(--sa-secondary)'"  → em botões primários
❌ onfocus="this.style.borderColor='var(--sa-secondary)'"     → em inputs (deve ser primary)
❌ border-radius:9px em cards                                  → usar 12px
❌ border-radius:9px em botões                                 → usar 8px
❌ font-weight:700 em th de tabela                             → usar 600
❌ rgba(0,0,0,.02) no row-hover                               → usar var(--sa-surface2)
❌ Tailwind classes (bg-gray-100, text-sm, etc.) nas views    → usar CSS vars inline
❌ jQuery, Bootstrap, alert() nativo                           → Alpine.js + SweetAlert2
❌ Cores hardcodadas (#ffffff, #1a1a1a) nas views              → sempre var(--sa-*)
❌ Badge sem ponto colorido                                    → incluir dot span
```

---

### Seção de Referência — Protótipos JSX
> Ver tabela completa no início deste arquivo (REGRA IMUTÁVEL). Arquivos disponíveis em `DOCS/Layout/suaagenda/project/`:

**Telas principais:** `DashboardScreen.jsx`, `ClientsScreen.jsx`, `StaffScreen.jsx`, `ServicesScreen.jsx`, `CalendarScreen.jsx`, `ReportsScreen.jsx`, `FinancialScreen.jsx`, `PlansScreen.jsx`, `PermissionsScreen.jsx`, `RolesScreen.jsx`, `SiteSettingsScreen.jsx`, `PortfolioScreen.jsx`, `POSScreen.jsx`, `ProductsScreen.jsx`

**Auth / Public:** `LoginScreen.jsx`, `RecoverScreen.jsx`, `PublicScreen.jsx`

**Infra:** `layout.jsx`, `ui.jsx`, `Modals.jsx`, `BookingModal.jsx`, `app.jsx`, `utils.js`

---

## Users (Barbearia Teste)
- adrianoelite@msn.com — super_admin (sem empresa)
- adrianoelite1980@gmail.com — admin_empresa (Barbearia Teste)
- carlos@barbearia.test — gestor (Barbearia Teste)
- joao@barbearia.test — analista (Barbearia Teste)
- maria@cliente.test — sem role (Barbearia Teste)
- Senha padrao: StudioZERO21! (ALTERAR antes de producao!)
