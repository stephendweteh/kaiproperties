#!/bin/bash
# Full Database Migration Runner for cPanel deployments
# Usage: bash deployment/20260703-full-database-migration/apply.sh [optional-project-path]

set -euo pipefail

PROJECT_ROOT="${1:-.}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
LOG_DIR="$PROJECT_ROOT/storage/logs"
LOG_FILE="$LOG_DIR/full-database-migration-$TIMESTAMP.log"
BACKUP_DIR="$PROJECT_ROOT/storage/backups/database-migration-$TIMESTAMP"

echo ""
echo "=========================================="
echo "  FULL DATABASE MIGRATION"
echo "=========================================="
echo ""

if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "ERROR: artisan not found in $PROJECT_ROOT"
    echo "Run from project root or pass path as first argument."
    exit 1
fi

mkdir -p "$LOG_DIR"
mkdir -p "$BACKUP_DIR"

{
    echo "[$(date)] Starting full database migration"
    echo "Project root: $PROJECT_ROOT"

    cd "$PROJECT_ROOT"

    echo ""
    echo "1) Clearing Laravel optimization caches"
    php artisan optimize:clear

    echo ""
    echo "2) Current migration status (before)"
    php artisan migrate:status || true

    echo ""
    echo "3) Running all pending migrations"
    php artisan migrate --force

    echo ""
    echo "4) Current migration status (after)"
    php artisan migrate:status

    echo ""
    echo "5) Rebuilding runtime caches"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo ""
    echo "[$(date)] Full database migration completed successfully"
} 2>&1 | tee "$LOG_FILE"

echo ""
echo "Migration complete."
echo "Log file: $LOG_FILE"
echo "Backup marker dir: $BACKUP_DIR"
echo ""
echo "If the site was open in browser, hard refresh with Ctrl+Shift+R."
