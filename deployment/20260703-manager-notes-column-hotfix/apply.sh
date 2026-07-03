#!/bin/bash
# Manager Notes Column Hotfix
# Usage: bash deployment/20260703-manager-notes-column-hotfix/apply.sh [optional-project-path]

set -euo pipefail

PROJECT_ROOT="${1:-.}"
PATCH_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILES_DIR="$PATCH_DIR/files"
MIGRATION_NAME="2026_07_03_000000_add_manager_notes_to_ticket_phases_table.php"
MIGRATION_SRC="$FILES_DIR/database/migrations/$MIGRATION_NAME"
MIGRATION_DEST_DIR="$PROJECT_ROOT/database/migrations"
MIGRATION_DEST="$MIGRATION_DEST_DIR/$MIGRATION_NAME"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
LOG_DIR="$PROJECT_ROOT/storage/logs"
LOG_FILE="$LOG_DIR/manager-notes-column-hotfix-$TIMESTAMP.log"

echo ""
echo "=========================================="
echo "  MANAGER NOTES COLUMN HOTFIX"
echo "=========================================="
echo ""

if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "ERROR: artisan not found in $PROJECT_ROOT"
    echo "Run from project root or pass path as first argument."
    exit 1
fi

if [ ! -f "$MIGRATION_SRC" ]; then
    echo "ERROR: bundled migration file missing from hotfix package"
    exit 1
fi

mkdir -p "$MIGRATION_DEST_DIR"
mkdir -p "$LOG_DIR"

{
    echo "[$(date)] Starting manager_notes hotfix"
    echo "Project root: $PROJECT_ROOT"

    if [ ! -f "$MIGRATION_DEST" ]; then
        cp "$MIGRATION_SRC" "$MIGRATION_DEST"
        echo "Installed missing migration: $MIGRATION_NAME"
    else
        echo "Migration already exists: $MIGRATION_NAME"
    fi

    cd "$PROJECT_ROOT"

    php artisan optimize:clear
    php artisan migrate --path="database/migrations/$MIGRATION_NAME" --force || php artisan migrate --force

    echo "Verifying ticket_phases.manager_notes column..."
    HAS_COLUMN=$(php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasColumn('ticket_phases', 'manager_notes') ? 'yes' : 'no';" | tr -d '\r' | tail -n 1)

    if [ "$HAS_COLUMN" != "yes" ]; then
        echo "ERROR: Column ticket_phases.manager_notes was not created."
        exit 1
    fi

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "[$(date)] Hotfix completed successfully"
} 2>&1 | tee "$LOG_FILE"

echo ""
echo "Hotfix complete."
echo "Log file: $LOG_FILE"
echo "Hard refresh browser: Ctrl+Shift+R"
