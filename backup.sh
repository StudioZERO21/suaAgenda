#!/bin/bash

# ╔════════════════════════════════════════════════════════════════════════════╗
# ║                      BACKUP AUTOMÁTICO - suaAgenda.pro                     ║
# ║                                                                            ║
# ║  Uso: ./backup.sh [etapa-numero]                                          ║
# ║  Exemplo: ./backup.sh 1.1                                                 ║
# ║                                                                            ║
# ║  Faz:                                                                      ║
# ║  ✅ Backup banco de dados (comprimido)                                    ║
# ║  ✅ Backup arquivos (tar.gz)                                              ║
# ║  ✅ Verificação integridade                                               ║
# ║  ✅ Git commit e push                                                     ║
# ║  ✅ Log automático                                                        ║
# ╚════════════════════════════════════════════════════════════════════════════╝

set -e  # Exit on error

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
ETAPA="${1:-unknown}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="BACKUPS"
DB_NAME="suaAgenda"
DB_USER="root"
LOG_FILE="storage/logs/backup.log"

# Criar diretório se não existir
mkdir -p "$BACKUP_DIR"
mkdir -p "storage/logs"

# ════════════════════════════════════════════════════════════════════════════
# FUNÇÕES
# ════════════════════════════════════════════════════════════════════════════

log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] SUCCESS: $1" >> "$LOG_FILE"
}

error() {
    echo -e "${RED}❌ ERRO: $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: $1" >> "$LOG_FILE"
}

check_dependencies() {
    log "Verificando dependências..."
    
    command -v mysqldump >/dev/null 2>&1 || error "mysqldump não encontrado. Instale MySQL client."
    command -v git >/dev/null 2>&1 || error "git não encontrado"
    
    success "Dependências verificadas"
}

backup_database() {
    log "Fazendo backup do banco de dados..."
    
    BACKUP_FILE="$BACKUP_DIR/backup-etapa-${ETAPA}-${TIMESTAMP}.sql.gz"
    
    if mysqldump -u "$DB_USER" "$DB_NAME" 2>/dev/null | gzip > "$BACKUP_FILE"; then
        local size=$(du -h "$BACKUP_FILE" | cut -f1)
        success "Banco de dados backup: $BACKUP_FILE ($size)"
        echo "$BACKUP_FILE"
    else
        error "Falha ao fazer backup do banco de dados"
    fi
}

backup_files() {
    log "Fazendo backup dos arquivos..."
    
    BACKUP_FILE="$BACKUP_DIR/backup-etapa-${ETAPA}-${TIMESTAMP}.tar.gz"
    
    tar -czf "$BACKUP_FILE" . \
        --exclude=.git \
        --exclude=node_modules \
        --exclude=vendor \
        --exclude=storage/logs \
        --exclude=.env \
        --exclude='*.log' \
        2>/dev/null || error "Falha ao fazer backup dos arquivos"
    
    local size=$(du -h "$BACKUP_FILE" | cut -f1)
    success "Arquivos backup: $BACKUP_FILE ($size)"
    echo "$BACKUP_FILE"
}

verify_backup() {
    log "Verificando integridade dos backups..."
    
    local sql_backup="$1"
    local tar_backup="$2"
    
    # Verificar SQL
    if gunzip -t "$sql_backup" 2>/dev/null; then
        success "Banco de dados backup verificado ✓"
    else
        error "Banco de dados backup corrompido"
    fi
    
    # Verificar TAR
    if tar -tzf "$tar_backup" >/dev/null 2>&1; then
        success "Arquivos backup verificado ✓"
    else
        error "Arquivos backup corrompido"
    fi
}

git_backup() {
    log "Fazendo git commit e push..."
    
    if ! git status --porcelain | grep -q .; then
        warning "Nenhuma mudança para commitar (apenas backup)"
    else
        git add . || error "Falha em git add"
        git commit -m "backup(etapa-${ETAPA}): snapshot ${TIMESTAMP}

Database: backup-etapa-${ETAPA}-${TIMESTAMP}.sql.gz
Files: backup-etapa-${ETAPA}-${TIMESTAMP}.tar.gz

Status: Backup realizado com sucesso" || error "Falha em git commit"
    fi
    
    git add BACKUPS/ || true
    git commit -m "backup(etapa-${ETAPA}): arquivos de backup" 2>/dev/null || true
    
    if git push origin $(git rev-parse --abbrev-ref HEAD) 2>/dev/null; then
        success "Git push realizado"
    else
        warning "Git push falhou (branch remoto pode não existir)"
    fi
}

cleanup_old_backups() {
    log "Limpando backups antigos (> 60 dias)..."
    
    local removed=0
    while IFS= read -r file; do
        rm -f "$file"
        removed=$((removed + 1))
        warning "Removido: $file"
    done < <(find "$BACKUP_DIR" -name "backup-*" -mtime +60 2>/dev/null)
    
    if [ $removed -gt 0 ]; then
        success "$removed arquivo(s) antigo(s) removido(s)"
    else
        log "Nenhum backup antigo para remover"
    fi
}

show_summary() {
    echo ""
    echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}✅ BACKUP COMPLETO!${NC}"
    echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
    echo ""
    echo "  Etapa: $ETAPA"
    echo "  Timestamp: $TIMESTAMP"
    echo "  Diretório: $BACKUP_DIR"
    echo "  Log: $LOG_FILE"
    echo ""
    echo -e "${YELLOW}Próximas ações:${NC}"
    echo "  1. Verificar: ls -lh BACKUPS/backup-etapa-${ETAPA}-*"
    echo "  2. Git push: git push origin \$(git rev-parse --abbrev-ref HEAD)"
    echo "  3. Continuar desenvolvimento ou fazer merge"
    echo ""
}

# ════════════════════════════════════════════════════════════════════════════
# EXECUÇÃO
# ════════════════════════════════════════════════════════════════════════════

main() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║          BACKUP AUTOMÁTICO - suaAgenda.pro                ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    if [ "$ETAPA" == "unknown" ]; then
        error "Uso: $0 [etapa-numero]\nExemplo: $0 1.1"
    fi
    
    check_dependencies
    
    # Fazer backups
    sql_backup=$(backup_database)
    tar_backup=$(backup_files)
    
    # Verificar
    verify_backup "$sql_backup" "$tar_backup"
    
    # Git
    git_backup
    
    # Limpeza
    cleanup_old_backups
    
    # Resumo
    show_summary
}

# Rodar
main
