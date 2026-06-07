# 💾 BACKUP & RESTORE - suaAgenda.pro

**Objetivo:** Proteger dados e código contra perda  
**Frequência:** OBRIGATÓRIO após cada etapa  
**Versão:** 1.0  
**Responsável:** Desenvolvedor

---

## ⚠️ AVISOS CRÍTICOS

1. **Backup é OBRIGATÓRIO após CADA ETAPA**
   - Sem exceções
   - Sem procrastinação
   - Faça imediatamente após conclusão

2. **Perda de dados é irreversível**
   - 1 banco perdido = semanas de retrabalho
   - 1 código perdido = features desaparecidas
   - Proteja-se

3. **Backup duplo é mais seguro**
   - Banco de dados (SQL)
   - Arquivos comprimidos (ZIP)
   - Ambos localmente + cloud é ideal

---

## 🚀 QUICK BACKUP (30 segundos)

Se está com pressa:

```bash
# 1. Backup banco
mysqldump -u root -p suaAgenda > BACKUPS/backup-etapa-1.1-$(date +%Y%m%d-%H%M%S).sql

# 2. Backup arquivos
zip -r BACKUPS/backup-etapa-1.1-$(date +%Y%m%d-%H%M%S).zip . --exclude=".env" --exclude="node_modules/*" --exclude="vendor/*"

# 3. Push Git
git add BACKUPS/
git commit -m "backup(etapa-1.1): snapshot final"
git push origin etapa-1.1

PRONTO! ✅
```

---

## 📋 ESTRATÉGIA COMPLETA DE BACKUP

### Estrutura de Pastas

```
suaAgenda/
├── BACKUPS/                          ← Backups locais
│   ├── backup-etapa-1.1-20260115-143022.sql
│   ├── backup-etapa-1.1-20260115-143022.zip
│   ├── backup-etapa-1.2-20260122-180515.sql
│   ├── backup-etapa-1.2-20260122-180515.zip
│   └── README-BACKUP.md
├── storage/logs/                     ← Logs de backup
│   └── backup.log
└── ... (resto do projeto)
```

### Tipos de Backup

| Tipo | O quê | Frequência | Local | Retenção |
|------|-------|-----------|-------|----------|
| **Database SQL** | Banco MySQL | Após etapa | BACKUPS/ | 60 dias |
| **Full ZIP** | Código + banco | Após etapa | BACKUPS/ | 60 dias |
| **Incremental Git** | Alterações | A cada commit | GitHub | ∞ |
| **Cloud** | Tudo | Semanal | Dropbox/Drive | ∞ |

---

## 🔧 BACKUP DETALHADO

### Etapa 1: Preparar Ambiente

```bash
# 1. Criar pasta BACKUPS se não existir
mkdir -p BACKUPS

# 2. Verificar espaço disponível
df -h
# Precisa de pelo menos 500MB livre

# 3. Verificar status Git
git status
# Não deve haver mudanças não commitadas (ou comitar antes)
```

### Etapa 2: Backup do Banco de Dados

#### Opção A: Apenas estrutura (rápido)

```bash
mysqldump -u root -p --no-data suaAgenda > BACKUPS/backup-etapa-1.1-schema.sql
# Tamanho: ~50KB
```

#### Opção B: Completo (recomendado)

```bash
# Com timestamp automático:
mysqldump -u root -p suaAgenda > BACKUPS/backup-etapa-1.1-$(date +%Y%m%d-%H%M%S).sql

# Sem timestamp (simples):
mysqldump -u root -p suaAgenda > BACKUPS/backup-etapa-1.1.sql

# Com compressão (economiza espaço):
mysqldump -u root -p suaAgenda | gzip > BACKUPS/backup-etapa-1.1.sql.gz

# Verificar arquivo
ls -lh BACKUPS/backup-etapa-1.1.sql
# Deve ter alguns MB
```

#### Opção C: Backup completo com eventos

```bash
mysqldump -u root -p \
  --all-databases \
  --routines \
  --triggers \
  --events \
  > BACKUPS/backup-completo-$(date +%Y%m%d).sql

# Mais seguro, mas arquivo maior
```

### Etapa 3: Backup de Arquivos

#### Opção A: ZIP simples

```bash
# Excluindo pastas grandes
zip -r BACKUPS/backup-etapa-1.1-$(date +%Y%m%d).zip . \
  --exclude="node_modules/*" \
  --exclude="vendor/*" \
  --exclude=".git/*" \
  --exclude="storage/logs/*" \
  --exclude=".env" \
  --exclude=".DS_Store"

# Tamanho: ~50-100MB
```

#### Opção B: TAR comprimido (melhor compressão)

```bash
tar -czf BACKUPS/backup-etapa-1.1-$(date +%Y%m%d).tar.gz . \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=.git \
  --exclude=storage/logs \
  --exclude=.env

# Tamanho: ~20-30MB (comprime mais)
```

#### Opção C: Script completo (recomendado)

```bash
#!/bin/bash
# Salvar como: backup.sh

ETAPA="1.1"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="BACKUPS"

echo "🔄 Iniciando backup etapa $ETAPA..."

# 1. Banco de dados
echo "📊 Backup banco de dados..."
mysqldump -u root -p suaAgenda | gzip > $BACKUP_DIR/backup-etapa-$ETAPA-$TIMESTAMP.sql.gz

# 2. Arquivos
echo "📁 Backup arquivos..."
tar -czf $BACKUP_DIR/backup-etapa-$ETAPA-$TIMESTAMP.tar.gz . \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=.git \
  --exclude=storage/logs \
  --exclude=.env

# 3. Listagem
echo "✅ Backup concluído:"
ls -lh $BACKUP_DIR/backup-etapa-$ETAPA-$TIMESTAMP.*

echo "📝 Registrando..."
echo "$(date '+%Y-%m-%d %H:%M:%S') - Backup etapa $ETAPA: OK" >> storage/logs/backup.log

echo "✅ BACKUP COMPLETO!"
```

Usar:
```bash
chmod +x backup.sh
./backup.sh
```

### Etapa 4: Verificar Integridade

```bash
# 1. Verificar arquivo SQL
gunzip -t BACKUPS/backup-etapa-1.1-*.sql.gz
# Sem output = OK

# 2. Verificar arquivo ZIP
unzip -t BACKUPS/backup-etapa-1.1-*.zip
# Deve terminar com "No errors detected"

# 3. Verificar arquivo TAR
tar -tzf BACKUPS/backup-etapa-1.1-*.tar.gz > /dev/null
# Sem output = OK

# 4. Simular restore (apenas estrutura)
gunzip -c BACKUPS/backup-etapa-1.1.sql.gz | head -20
# Deve mostrar SQL válido
```

### Etapa 5: Commit Git

```bash
# 1. Adicionar backups ao Git
git add BACKUPS/

# 2. Commit descritivo
git commit -m "backup(etapa-1.1): snapshot final concluído

Database: backup-etapa-1.1-*.sql.gz
Arquivos: backup-etapa-1.1-*.tar.gz
Tamanho total: X MB
Status: Pronto para merge"

# 3. Push
git push origin etapa-1.1

# Verificar:
git log --oneline -3
git show --stat  # Ver arquivos inclusos
```

---

## 🔄 RESTORE (RECUPERAR BACKUP)

### Se cometeu erro e quer voltar

```bash
# 1. Limpar banco atual
mysql -u root -p -e "DROP DATABASE suaAgenda;"

# 2. Restaurar banco
mysql -u root -p < BACKUPS/backup-etapa-1.1.sql
# Ou se comprimido:
gunzip -c BACKUPS/backup-etapa-1.1.sql.gz | mysql -u root -p

# 3. Verificar
php artisan tinker
>>> User::count()     # Deve restaurar dados
>>> exit

# 4. Limpar cache
php artisan optimize:clear

# 5. Testar servidor
composer dev
```

### Se precisa restaurar arquivos

```bash
# 1. Backup dos arquivos atuais (segurança)
cp -r app app.backup

# 2. Extrair backup
unzip BACKUPS/backup-etapa-1.1.zip -d restore-dir/
# Ou tar:
tar -xzf BACKUPS/backup-etapa-1.1.tar.gz -C restore-dir/

# 3. Comparar
diff -r app restore-dir/app

# 4. Restaurar seletivamente
cp restore-dir/app/Models/User.php app/Models/User.php

# 5. Limpar
rm -rf restore-dir app.backup
```

### Se perdeu tudo (cenário catastrófico)

```bash
# 1. Clonar do GitHub (se tiver feito push)
git clone https://github.com/StudioZERO21/suaAgenda.git
cd suaAgenda

# 2. Restaurar banco
gunzip -c BACKUPS/backup-etapa-1.1.sql.gz | mysql -u root -p suaAgenda

# 3. Instalar dependências
composer install
npm install

# 4. Testar
php artisan tinker
>>> User::count()

# PRONTO! Voltou ao estado anterior
```

---

## 📅 CRONOGRAMA DE BACKUPS

### Diário (Automático se possível)

```bash
# Adicionar ao crontab (Linux/macOS):
crontab -e

# Adicionar linha:
0 22 * * * cd /caminho/para/suaAgenda && ./backup.sh

# Isso executa backup todo dia às 22:00
```

### Por Etapa (Obrigatório)

| Etapa | Data Esperada | Backup Feito? |
|-------|--------------|---------------|
| 1.1 | Semana 2 | ☐ |
| 1.2 | Semana 4 | ☐ |
| 1.3 | Semana 6 | ☐ |
| 1.4 | Semana 8 | ☐ |
| 1.5 | Semana 10 | ☐ |
| 1.6 | Semana 12 | ☐ |

### Limpeza de Backups Antigos

```bash
# Ver backups antigos (> 60 dias)
find BACKUPS -name "backup-*" -mtime +60

# Deletar antigos
find BACKUPS -name "backup-*" -mtime +60 -delete

# Ou manual:
rm BACKUPS/backup-etapa-1.1-20251001-*
```

---

## 🔐 BACKUP SEGURO (Adicional)

### Fazer upload para Dropbox/Google Drive

```bash
# Windows (usar Dropbox app, automático)
# macOS (usar iCloud/Dropbox, automático)

# Ou manual:
cp BACKUPS/backup-etapa-1.1.sql.gz ~/Dropbox/suaAgenda-Backups/
```

### Fazer upload para Amazon S3 (Premium)

```bash
# Instalar AWS CLI
pip install awscli

# Configurar credenciais
aws configure

# Upload
aws s3 cp BACKUPS/backup-etapa-1.1.sql.gz s3://seu-bucket/suaAgenda/
```

### Fazer upload para GitHub (NUNCA o banco SQL!)

```bash
# Criar arquivo .gitignore
echo "*.sql" >> .gitignore
echo "*.sql.gz" >> .gitignore
echo "*.zip" >> .gitignore

# Então:
git add -A
git commit -m "backup: código e estrutura"
git push origin etapa-1.1
# Banco de dados NUNCA no Git!
```

---

## ❌ ERROS COMUNS

### "mysqldump: command not found"

```bash
# MySQL não está no PATH
# Windows: Adicionar ao PATH do MySQL
# macOS: brew install mysql-client
# Linux: sudo apt-get install mysql-client
```

### "Access denied for user 'root'"

```bash
# Senha incorreta ou root sem senha

# Tentar com senha vazia
mysqldump -u root suaAgenda > backup.sql

# Ou especificar socket
mysqldump -u root --socket=/var/run/mysqld/mysqld.sock suaAgenda > backup.sql
```

### "ERROR 1045 (28000): Access denied"

```bash
# Root sem acesso

# Windows (XAMPP)
mysqldump -u root -psenha suaAgenda > backup.sql
# Sem espaço entre -p e senha

# Ou tente:
mysql -u root -p
# Digite a senha e execute SQL manualmente
```

### "Can't create database 'suaAgenda'; database exists"

```bash
# Banco já existe ao restaurar

# Deletar antes de restaurar:
mysql -u root -p -e "DROP DATABASE IF EXISTS suaAgenda;"

# Depois restaurar:
mysql -u root -p < backup.sql
```

---

## 📊 CHECKLIST APÓS BACKUP

- ☑️ Arquivo SQL criado: `ls -lh BACKUPS/*.sql`
- ☑️ Arquivo ZIP criado: `ls -lh BACKUPS/*.zip`
- ☑️ Integridade verificada: `unzip -t BACKUPS/*.zip`
- ☑️ Tamanho razoável (não vazio)
- ☑️ Timestamp correto (não antigo)
- ☑️ Backup commitado: `git log --oneline -3`
- ☑️ Backup pushado: `git push origin etapa-X.X`
- ☑️ Log atualizado: `storage/logs/backup.log`

---

## 🎯 WORKFLOW FINAL POR ETAPA

Após TUDO pronto na etapa:

```bash
# 1. Verificar tudo OK
./vendor/bin/pest              # Testes verdes
./vendor/bin/pint --test       # Lint OK
git status                     # Sem mudanças não commitadas

# 2. Fazer backup
mysqldump -u root -p suaAgenda > BACKUPS/backup-etapa-1.1.sql
zip -r BACKUPS/backup-etapa-1.1.zip . --exclude="vendor/*" --exclude="node_modules/*"

# 3. Commit backup
git add BACKUPS/
git commit -m "backup(etapa-1.1): snapshot final"

# 4. Push
git push origin etapa-1.1

# ✅ ETAPA CONCLUÍDA!
```

---

**Próximo passo:** Ler [CHECKLIST-ETAPA.md](./CHECKLIST-ETAPA.md) para saber como marcar progresso.
