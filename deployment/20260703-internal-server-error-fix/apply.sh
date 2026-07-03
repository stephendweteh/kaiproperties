#!/bin/bash
# Internal Server Error fresh fix
# Usage: bash deployment/20260703-internal-server-error-fix/apply.sh [optional-project-path]

set -euo pipefail

PROJECT_ROOT="${1:-.}"
PATCH_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILES_DIR="$PATCH_DIR/files"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$PROJECT_ROOT/storage/backups/internal-server-error-fix-$TIMESTAMP"
LOG_DIR="$PROJECT_ROOT/storage/logs"
LOG_FILE="$LOG_DIR/internal-server-error-fix-$TIMESTAMP.log"

DASHBOARD_SRC="$FILES_DIR/app/Http/Controllers/Web/DashboardController.php"
DASHBOARD_DEST="$PROJECT_ROOT/app/Http/Controllers/Web/DashboardController.php"

MIGRATION_CUSTOMERS="2026_07_03_130000_create_customers_table.php"
MIGRATION_CUSTOMER_ID="2026_07_03_130100_add_customer_id_to_properties_table.php"
MIGRATION_SRC_DIR="$FILES_DIR/database/migrations"
MIGRATION_DEST_DIR="$PROJECT_ROOT/database/migrations"

echo ""
echo "=========================================="
echo "  INTERNAL SERVER ERROR FRESH FIX"
echo "=========================================="
echo ""

if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "ERROR: artisan not found in $PROJECT_ROOT"
    echo "Run from project root or pass path as first argument."
    exit 1
fi

if [ ! -f "$DASHBOARD_SRC" ]; then
    echo "ERROR: bundled DashboardController not found in package"
    exit 1
fi

mkdir -p "$BACKUP_DIR"
mkdir -p "$LOG_DIR"
mkdir -p "$MIGRATION_DEST_DIR"

{
    echo "[$(date)] Starting internal server error fresh fix"
    echo "Project root: $PROJECT_ROOT"

    if [ -f "$DASHBOARD_DEST" ]; then
        cp "$DASHBOARD_DEST" "$BACKUP_DIR/DashboardController.php"
        echo "Backed up existing DashboardController"
    fi

    cp "$DASHBOARD_SRC" "$DASHBOARD_DEST"
    echo "Updated DashboardController with schema guards"

    if [ ! -f "$MIGRATION_DEST_DIR/$MIGRATION_CUSTOMERS" ]; then
        cp "$MIGRATION_SRC_DIR/$MIGRATION_CUSTOMERS" "$MIGRATION_DEST_DIR/$MIGRATION_CUSTOMERS"
        echo "Installed missing migration: $MIGRATION_CUSTOMERS"
    else
        echo "Migration already exists: $MIGRATION_CUSTOMERS"
    fi

    if [ ! -f "$MIGRATION_DEST_DIR/$MIGRATION_CUSTOMER_ID" ]; then
        cp "$MIGRATION_SRC_DIR/$MIGRATION_CUSTOMER_ID" "$MIGRATION_DEST_DIR/$MIGRATION_CUSTOMER_ID"
        echo "Installed missing migration: $MIGRATION_CUSTOMER_ID"
    else
        echo "Migration already exists: $MIGRATION_CUSTOMER_ID"
    fi

    cd "$PROJECT_ROOT"

    php artisan optimize:clear
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "Verification snippet:"
    grep -n "hasCustomersTable" "$DASHBOARD_DEST" || true

    echo "[$(date)] Internal server error fix completed successfully"
} 2>&1 | tee "$LOG_FILE"

echo ""
echo "Fix complete."
echo "Backup folder: $BACKUP_DIR"
echo "Log file: $LOG_FILE"
echo "Hard refresh browser: Ctrl+Shift+R"
