# 🔀 GIT WORKFLOW - suaAgenda.pro

**Objetivo:** Manter histórico limpo, rastreável e profissional  
**Versão:** 1.0  
**Status:** 📋 Obrigatório

---

## 📋 RESUMO RÁPIDO

```bash
# Criar nova branch para etapa
git checkout -b etapa-1.1

# Fazer modificações...
# (editar arquivos)

# Verificar mudanças
git status
git diff

# Preparar para commit
git add .
git add caminho/especifico.php         # Ou adicionar específicos

# Fazer commit
git commit -m "feat(etapa-1.1): descrição clara do que foi feito"

# Enviar para remoto
git push origin etapa-1.1

# Backup OBRIGATÓRIO (ver BACKUP-RESTORE.md)
# Depois merge (manual, após review)
```

---

## 🌳 ESTRUTURA DE BRANCHES

### Branches Permanentes

```
main (ou master)
  └─ Produção final, tags de release
  └─ Protegido: requer review para merge
  └─ Última versão estável

develop (ou staging)
  └─ Desenvolvimento contínuo
  └─ Base para novas features
  └─ Testado, mas não em produção
```

### Branches de Trabalho (Etapas)

```
etapa-1.1   ← Sprint 1-2: Autenticação + Agendamento
etapa-1.2   ← Sprint 3-4: WhatsApp + API Limit
etapa-1.3   ← Sprint 5-6: Link + Mobile MVP
etapa-1.4   ← Sprint 7-8: Admin Dashboard
etapa-1.5   ← Sprint 9-10: Relatórios
etapa-1.6   ← Sprint 11-12: QA + Docs + Beta

feature/xxx ← Feature isolada de curta duração
bugfix/xxx  ← Correção de bug isolada
hotfix/xxx  ← Correção urgente em produção
```

### Nomenclatura de Branches

```
PADRÃO:
etapa-{FASE}.{SPRINT}
feature/{feature-name}
bugfix/{bug-description}
hotfix/{issue-critical}

✅ BOM:
etapa-1.1
etapa-1.2
feature/agendamento-inteligente
bugfix/correcao-timezone
hotfix/problema-critico-producao

❌ RUIM:
branch-1
novo-branch
meu-trabalho
teste123
alex-trabalha-aqui
```

---

## 💬 PADRÃO DE COMMIT MESSAGES

### Formato Obrigatório

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Tipos de Commit

| Tipo | Descrição | Exemplo |
|------|-----------|---------|
| **feat** | Nova feature | `feat(agendamento): adicionar confirmação WhatsApp` |
| **fix** | Correção de bug | `fix(auth): corrigir erro login OAuth` |
| **chore** | Manutenção, build, deps | `chore: atualizar Laravel para 13.1` |
| **docs** | Documentação | `docs(README): adicionar instrução setup` |
| **test** | Testes | `test(agendamento): adicionar testes Pest` |
| **refactor** | Refatoração (sem feature) | `refactor(models): extrair lógica para Service` |
| **perf** | Otimização | `perf(queries): adicionar índices MySQL` |
| **style** | Formatação, lint | `style: executar ./vendor/bin/pint` |
| **ci** | CI/CD | `ci(actions): adicionar workflow tests` |

### Exemplos Completos

```bash
# Feature simples (uma linha)
git commit -m "feat(agendamento): criar lock Redis para evitar double booking"

# Feature complexa (com body)
git commit -m "feat(whatsapp): implementar limite API por plano

- Starter: 50 msg/mês
- Crescimento: 200 msg/mês
- Profissional: 500 msg/mês
- Enterprise: ilimitado

Implementado: WhatsAppLimitService.php
Tests: tests/Feature/WhatsAppLimitTest.php

Closes #42"

# Bugfix
git commit -m "fix(auth): corrigir erro timeout em login

Problema: Usuário toma timeout ao fazer login
Causa: N+1 query no relacionamento companies
Solução: Adicionar with('companies') em User::query

Testes: Feature/AuthTest.php (passou)"

# Commit de backup
git commit -m "backup(etapa-1.1): snapshot antes de refactor

Backup em BACKUPS/backup-etapa-1.1.sql
Backup em BACKUPS/backup-etapa-1.1.zip

Status: Pronto para merge"
```

---

## 🔄 WORKFLOW POR ETAPA

### Fase 1: Inicializar Etapa

```bash
# 1. Atualizar develop
git checkout develop
git pull origin develop

# 2. Criar branch de etapa
git checkout -b etapa-1.1

# 3. Verificar branch atual
git branch -v
# * etapa-1.1 abc1234 Initial commit

# Mensagem:
echo "Iniciando etapa 1.1: Autenticação + Agendamento"
```

### Fase 2: Desenvolvimento (Ao longo da semana)

```bash
# A cada conjunto coerente de mudanças:

# 1. Ver status
git status

# 2. Adicionar arquivos
git add .
# Ou específico:
git add app/Models/Agendamento.php
git add database/migrations/2024_01_01_create_agendamentos.php

# 3. Commit
git commit -m "feat(etapa-1.1): criar modelo Agendamento

- Modelo: app/Models/Agendamento.php
- Tabela: agendamentos (migration)
- Relacionamentos: empresa, profissional, cliente
- Scopes: ativo(), por_data()
- Validações: datas, horários, duração"

# 4. Verificar log
git log --oneline -5
```

### Fase 3: Fim da Etapa (Semana X)

```bash
# 1. Checklist final (ver CHECKLIST-ETAPA.md)
- ☑️ Todas features implementadas
- ☑️ Testes em verde (./vendor/bin/pest)
- ☑️ Lint OK (./vendor/bin/pint)
- ☑️ Sem dd(), console.log, TODOs no código
- ☑️ Documentação atualizada
- ☑️ Backup realizado

# 2. Cleanup - remover código temporário
rm -f clip_chk.php
rm -f .env.testing

# 3. Commit final
git add .
git commit -m "backup(etapa-1.1): snapshot final concluído

- Verificação OK: ./vendor/bin/pest Feature/MigrationTest.php
- Lint OK: ./vendor/bin/pint
- Backup: BACKUPS/backup-etapa-1.1.sql
- Status: Pronto para merge
- Próxima: etapa-1.2"

# 4. Push
git push origin etapa-1.1

# 5. Tag (opcional, mais importante após Fase 1)
git tag -a v1.1.0-beta -m "Etapa 1.1 MVP completo: Auth + Agendamento"
git push origin v1.1.0-beta
```

### Fase 4: Merge (Depois de review)

```bash
# 1. Checkout develop
git checkout develop

# 2. Merge com mensagem
git merge --no-ff etapa-1.1 -m "merge(etapa-1.1): auth + agendamento completos"

# 3. Push
git push origin develop

# 4. Criar nova etapa
git checkout -b etapa-1.2

# 5. Limpar branch antiga (local)
git branch -d etapa-1.1
# Ou remote:
git push origin --delete etapa-1.1
```

---

## 📊 VISUALIZAÇÃO DO GIT LOG

Recomendado usar aliases para melhor visualização:

```bash
# Adicionar ao ~/.gitconfig (ou .git/config)
[alias]
    log-oneline = log --oneline --graph --all --decorate
    log-detailed = log --format=fuller --graph --all
    log-short = log --oneline -10
    status-short = status -s

# Usar:
git log-oneline
git log-detailed
git log-short
```

---

## 🚨 REGRAS IMPORTANTES

### ✅ FAÇA

```bash
# Commits frequentes (1-2x por dia)
git commit -m "feat: parte 1 de 3 do agendamento"
git commit -m "feat: parte 2 de 3 do agendamento"
git commit -m "feat: parte 3 de 3 do agendamento - completo"

# Mensagens descritivas
git commit -m "feat(auth): implementar login OAuth Google

- Criado OAuth controller
- Adicionado redirect automático
- Testes em Feature/OAuthTest.php"

# Pull antes de push
git pull origin etapa-1.1
git push origin etapa-1.1

# Rebase se houver conflitos
git pull --rebase origin etapa-1.1
```

### ❌ NUNCA

```bash
# Commits genéricos
git commit -m "atualização"
git commit -m "fix"
git commit -m "xxx"
git commit -m "trabalho"

# Commits gigantes
git commit -am "": (50+ arquivos de uma vez)

# Código com debug
git add .
git commit -m "feat: novo agendamento"
# (contém dd(), console.log, debugger)

# Segredos commitados
git commit -m "feat: integração Twilio"
# (contém TWILIO_KEY hardcoded)

# Merge sem --no-ff
git merge etapa-1.1  # Cria merge commit automático
# Prefira:
git merge --no-ff etapa-1.1  # Commit de merge explícito

# Rebase em branch compartilhada
git rebase develop  # NUNCA em branch compartilhada!
# Use merge em branches compartilhadas
```

---

## 🔄 RESOLVER CONFLITOS

### Se houver conflito ao fazer pull

```bash
# 1. Ver conflitos
git status
# conflict (content): arquivo1.php, arquivo2.php

# 2. Abrir arquivos e resolver
# Procurar por: <<<<<<, ======, >>>>>>

# 3. Após resolver:
git add arquivo1.php arquivo2.php

# 4. Commit merge
git commit -m "merge: resolver conflitos em agendamento"

# 5. Push
git push origin etapa-1.1
```

### Se fez push e percebeu erro

```bash
# Se ainda não foi mergeado (em branch de etapa):

# Opção 1: Novo commit corrigindo
git add arquivo.php
git commit -m "fix: corrigir erro no commit anterior"
git push origin etapa-1.1

# Opção 2: Amend (se ainda não fez push)
git add arquivo.php
git commit --amend --no-edit
git push origin etapa-1.1 -f  # Force push APENAS em branches privadas!
```

---

## 🔐 CONFIGURAÇÃO GIT INICIAL

```bash
# Uma vez na máquina:

# 1. Configurar identidade
git config --global user.name "Seu Nome"
git config --global user.email "seu@email.com"

# 2. Configurar merge strategy padrão
git config --global pull.rebase false  # Usa merge padrão

# 3. Configurar credenciais (opcional)
git config --global credential.helper store
# Depois faz um push normal e pedirá senha uma vez

# 4. Alias úteis (opcional)
git config --global alias.log-oneline "log --oneline --graph --all --decorate"
git config --global alias.status-short "status -s"
```

---

## 📈 FLUXO COMPLETO VISUAL

```
main (produção)
  ↑
develop (staging)
  ↓
┌─────────────────────────────────┐
│ etapa-1.1                       │
│ (2 semanas desenvolvimento)      │
│ feat: auth + agendamento        │
│                                  │
│ Commit 1: Models               │
│ Commit 2: Controllers          │
│ Commit 3: Views                │
│ Commit 4: Tests                │
│ Commit 5: Docs                 │
│ Commit: backup(etapa-1.1)      │
└─────────────────────────────────┘
  ↓
develop (merge após review)
  ↓
┌─────────────────────────────────┐
│ etapa-1.2                       │
│ (2 semanas próxima etapa)       │
└─────────────────────────────────┘
```

---

## 🎯 CHECKLIST PRÉ-PUSH

Antes de fazer `git push`, verificar:

- ☑️ Branch correta: `git branch`
- ☑️ Commits descritivos: `git log --oneline -5`
- ☑️ Sem arquivo sensível: `git diff --cached | grep password`
- ☑️ Testes passam: `./vendor/bin/pest`
- ☑️ Lint OK: `./vendor/bin/pint --test`
- ☑️ Sem debug: `grep -r "dd\|console.log\|debugger" app/`
- ☑️ .env não commitado: `git status | grep .env`
- ☑️ Backup feito (se fim de etapa): BACKUPS/backup-etapa-*.sql

---

## 📞 REFERÊNCIA RÁPIDA

```bash
# Criar branch
git checkout -b etapa-1.1

# Ver branches
git branch -v
git branch -a

# Ver diferenças
git diff
git diff arquivo.php
git diff etapa-1.1 develop

# Ver log
git log --oneline
git log -5
git log --graph --all --decorate

# Adicionar arquivos
git add .
git add app/Models/*.php
git add -p  # Interativo

# Commit
git commit -m "mensagem"
git commit -m "msg" --no-verify  # Pula hooks (não recomendado)

# Enviar
git push origin etapa-1.1
git push origin :etapa-1.1  # Deleta remote

# Atualizar
git pull origin etapa-1.1
git pull --rebase origin etapa-1.1

# Merge
git merge --no-ff etapa-1.1

# Tag
git tag -a v1.0.0 -m "Release 1.0.0"
git push origin v1.0.0
```

---

**Próximo passo:** Ler [BACKUP-RESTORE.md](./BACKUP-RESTORE.md) para aprender procedimento de backup após cada etapa.
