# 🤖 CLAUDE CODE + CURSOR - Guia de Integração Automática

**Objetivo:** Usar Claude Code dentro do Cursor IDE para desenvolvimento automático seguindo as regras  
**Status:** ✅ Pronto  
**Versão:** 1.0

---

## 🎯 O QUE É CLAUDE CODE NO CURSOR?

**Claude Code** é a integração nativa do Claude (IA) dentro do **Cursor IDE**. Permite:

✅ Gerar código automaticamente  
✅ Refatorar código  
✅ Sugerir correções  
✅ Ler regras automaticamente  
✅ Executar comandos no terminal  
✅ Criar arquivos  
✅ Fazer commits Git automaticamente

---

## ⚡ COMO USAR - PASSO A PASSO

### **1. Abrir Cursor IDE com o Projeto**

```bash
# Na raiz do suaAgenda:
cursor .

# Ou dentro do Cursor:
File → Open Folder → suaAgenda/
```

### **2. Claude Code Carrega Automaticamente**

Quando você abre Cursor:

```
✅ .cursorrules é LIDO AUTOMATICAMENTE
✅ Claude Code segue todas as regras
✅ Pode usar @rules para referência
✅ IA segue padrões rigorosamente
```

### **3. Usar Claude Code para Gerar Código**

#### **Opção A: Chat do Cursor (Cmd+Shift+J)**

```
Você escreve:
"Crie o model Agendamento com as regras"

Claude Code:
✅ Lê .cursorrules
✅ Cria app/Models/Agendamento.php
✅ Segue strict_types, nomenclatura, soft deletes
✅ Adiciona docblocks
✅ Faz git add
```

#### **Opção B: Inline (Cmd+K no arquivo)**

```
No arquivo:
1. Abra app/Models/Agendamento.php (vazio)
2. Pressione Cmd+K
3. Digite: "Complete o model Agendamento"

Claude Code:
✅ Completa automaticamente
✅ Segue as regras
✅ Roda ./vendor/bin/pint automaticamente
```

#### **Opção C: Terminal (Ctrl+J)**

```
Terminal do Cursor:

> @code criar migration para agendamentos

Claude Code:
✅ Cria database/migrations/2026_01_XX_create_agendamentos_table.php
✅ Com os campos corretos
✅ Roda: php artisan migrate
✅ Verifica se funcionou
```

---

## 📋 EXEMPLOS PRÁTICOS

### **Exemplo 1: Criar Model + Migration + Tests**

```
Escrever no chat:
"@rules

Seguindo as regras, crie:
1. Model: app/Models/Agendamento.php
   - SoftDeletes
   - Relationships (belongsTo Company, Profissional, Cliente)
   - Scopes: ativo(), por_data(), por_profissional()
   - Validações no model

2. Migration: database/migrations/...
   - Tabela agendamentos
   - Soft deletes
   - Índices em company_id

3. Test: tests/Feature/AgendamentoTest.php
   - Teste criar agendamento
   - Teste soft delete
   - Teste acesso só da company_id correta

Depois:
- ./vendor/bin/pint (formatar)
- ./vendor/bin/pest (rodar tests)
- git add . && git commit -m 'feat(etapa-1.1): Model Agendamento completo'"

Claude Code:
✅ Lê as regras
✅ Cria TUDO
✅ Roda formatação + testes
✅ Faz commit automaticamente
```

### **Exemplo 2: Criar Controller com Policy**

```
"@rules

Crie AgendamentoController + AgendamentoPolicy:

Controller:
- store() com lock Redis
- update() com autorização
- destroy() com soft delete
- index() com paginação
- Tests passando

Policy:
- viewAny(), view(), create(), update(), delete()

Depois:
- Testes passando
- Código formatado
- Commit automático"

Claude Code:
✅ Cria Controller
✅ Cria Policy
✅ Cria Tests
✅ Roda tudo
✅ Faz commit
```

### **Exemplo 3: Refatorar Código**

```
Abra arquivo problemático em Cursor + Cmd+K:

"Refatore este código seguindo .cursorrules:
- Extraia lógica complexa para Service
- Adicione validações em FormRequest
- Implemente Policy de autorização
- Adicione docblocks
- Rode testes"

Claude Code:
✅ Refatora
✅ Testa
✅ Formata
✅ Commit
```

---

## 🔧 COMANDOS ÚTEIS NO CURSOR

### **Chat (Cmd+L no Cursor)**

```
@rules               - Referencia as regras
@codebase           - Entende a estrutura do projeto
@file               - Analisa arquivo específico
@folder             - Entende pasta específica

Exemplo:
"@rules @codebase Qual é a estrutura de Models?"
```

### **Terminal (Ctrl+J)**

```
Executar comandos automaticamente:

> composer dev
> ./vendor/bin/pest
> ./vendor/bin/pint
> git status
> backup.sh 1.1
```

### **Arquivo (Cmd+K)**

```
Criar/editar código:

Cmd+K: "Implemente método..."
```

---

## 🚀 WORKFLOW COMPLETO DO DIA

### **Morning: Começar Desenvolvimento**

```
1. Abrir Cursor
2. Chat: "@rules qual é o task do dia?"
3. Claude Code lê CHECKLIST-ETAPA-1.1.md
4. Mostra próximo task
```

### **Durante: Gerar Código**

```
1. Chat: "Crie [X] seguindo as regras"
2. Claude Code gera automaticamente
3. Roda testes + formatação
4. Faz git add + commit
```

### **Evening: Finalizar Etapa**

```
1. Chat: "Verifique se etapa 1.1 está 100%"
2. Claude Code:
   - Roda ./vendor/bin/pest
   - Verifica cobertura (80%?)
   - Formata código
   - Faz backup: bash backup.sh 1.1
   - Git commit + push
```

---

## 📊 CHECKLIST: CLAUDE CODE RODANDO BEM

Você saberá que Claude Code está funcionando quando:

- ✅ `.cursorrules` é respeitado em TODOS os arquivos criados
- ✅ Código gerado tem `strict_types=1`
- ✅ Nomenclatura segue padrões (camelCase, PascalCase)
- ✅ SoftDeletes em models apropriados
- ✅ Tests são criados automaticamente
- ✅ ./vendor/bin/pint roda sem erros
- ✅ ./vendor/bin/pest passa com 80%+
- ✅ Git commits são feitos automaticamente
- ✅ Código está formatado e documentado

---

## 🚨 SE ALGO DER ERRADO

### **Claude Code não segue as regras?**

```
1. Verificar: .cursorrules existe e está lido
   - Cursor mostra "Rules: .cursorrules" no chat

2. Referenciar explicitamente:
   "@rules Crie [X] seguindo RIGOROSAMENTE as regras"

3. Especificar detalhes:
   "strict_types=1, SoftDeletes, with() em queries, etc"
```

### **Testes falhando?**

```
Chat: "@codebase Por que os testes falharam?
       Veja CONVENTIONS.md para padrão correto"

Claude Code:
✅ Analisa
✅ Corrige
✅ Roda tests
✅ Explica
```

### **Código não está sendo commitado?**

```
Chat: "Por favor, faça git add . && git commit -m '...'"

Claude Code:
✅ Faz git add
✅ Faz git commit
✅ Faz git push
```

---

## 🎯 DICAS IMPORTANTES

### **1. Seja Específico**

❌ "Crie um controller"  
✅ "Crie AgendamentoController com store(), update(), destroy(), index(), show(), todas com autorização"

### **2. Referencie as Regras**

❌ "Crie um teste"  
✅ "@rules Crie test/Feature/AgendamentoTest.php seguindo Pest conforme CONVENTIONS.md"

### **3. Peça Verificação**

❌ "Pronto"  
✅ "Verifique com ./vendor/bin/pest e ./vendor/bin/pint"

### **4. Automatize Tudo**

❌ Fazer git add/commit manualmente  
✅ Pedir ao Claude Code fazer "git add . && git commit -m '...' && git push"

---

## 📚 FLUXO IDEAL COM CLAUDE CODE

```
DIA 1:
├─ Morning: Chat "@rules qual é meu task?"
├─ Claude Code mostra próximo task
├─ Você: "Crie [X] seguindo as regras"
├─ Claude Code:
│  ├─ Cria arquivo
│  ├─ Adiciona código
│  ├─ Roda ./vendor/bin/pint
│  ├─ Roda ./vendor/bin/pest
│  ├─ git add .
│  └─ git commit
├─ Lunch: Relaxar
└─ Evening: Backup: bash backup.sh 1.1

DIA 2:
├─ Morning: Nova tarefa
├─ Repete...
└─ Evening: Backup

DIA 14 (Fim da Etapa):
├─ ./vendor/bin/pest (100% pass)
├─ ./vendor/bin/pint --test (OK)
├─ bash backup.sh 1.1
├─ Verificar README.md + DOCS/
└─ git checkout -b etapa-1.2 (próxima)
```

---

## 💡 PRÓXIMOS PASSOS

1. **Abrir Cursor com o projeto**
   ```bash
   cursor /path/to/suaAgenda
   ```

2. **Verificar se .cursorrules foi carregado**
   - Chat mostra: "I'll follow the .cursorrules file..."

3. **Começar Etapa 1.1**
   - Chat: "@rules Qual é o primeiro passo da Etapa 1.1?"
   - Claude Code responde baseado em ETAPAS.md
   - Você aprova
   - Claude Code começa

4. **Desenvolver conforme as regras**
   - Tudo automatizado
   - Tudo testado
   - Tudo commitado

---

**Status:** ✅ Pronto para usar  
**Próximo:** Abra Cursor e use Claude Code!

---

*Lembre-se: Claude Code é poderoso quando você é claro sobre o que quer. Quanto mais detalhado, melhor o resultado!*
