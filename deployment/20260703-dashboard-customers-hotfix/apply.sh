#!/bin/bash
# Dashboard + Customers Production Hotfix
# Usage: bash deployment/20260703-dashboard-customers-hotfix/apply.sh [optional-project-path]

set -euo pipefail

PROJECT_ROOT="${1:-.}"
PATCH_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PATCH_FILE="$PATCH_DIR/dashboard-customers-guard.patch"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$PROJECT_ROOT/storage/backups/dashboard-customers-hotfix-$TIMESTAMP"
CONTROLLER="$PROJECT_ROOT/app/Http/Controllers/Web/DashboardController.php"

echo ""
echo "=========================================="
echo "  Dashboard + Customers Hotfix"
echo "=========================================="
echo ""

if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "ERROR: artisan not found in $PROJECT_ROOT"
    echo "Run from project root or pass project path as first argument."
    exit 1
fi

if [ ! -f "$PATCH_FILE" ]; then
    echo "ERROR: patch file not found at $PATCH_FILE"
    exit 1
fi

if [ ! -f "$CONTROLLER" ]; then
    echo "ERROR: DashboardController not found at $CONTROLLER"
    exit 1
fi

echo "Project: $PROJECT_ROOT"
mkdir -p "$BACKUP_DIR"
cp "$CONTROLLER" "$BACKUP_DIR/DashboardController.php"
echo "Backup saved: $BACKUP_DIR/DashboardController.php"

echo ""
echo "Applying code patch..."
cd "$PROJECT_ROOT"

if patch -p1 --dry-run < "$PATCH_FILE" > /dev/null 2>&1; then
    patch -p1 < "$PATCH_FILE" > /dev/null
    echo "Code patch applied"
else
    if grep -q "hasCustomersTable" "$CONTROLLER"; then
        echo "Code patch already present, continuing"
    else
        echo "ERROR: patch could not be applied and target code is not already patched"
        echo "Restoring backup..."
        cp "$BACKUP_DIR/DashboardController.php" "$CONTROLLER"
        exit 1
    fi
fi

echo ""
echo "Running migrations..."
CUSTOMERS_MIGRATION="database/migrations/2026_07_03_130000_create_customers_table.php"
PROPERTIES_CUSTOMER_MIGRATION="database/migrations/2026_07_03_130100_add_customer_id_to_properties_table.php"

if [ -f "$PROJECT_ROOT/$CUSTOMERS_MIGRATION" ] && [ -f "$PROJECT_ROOT/$PROPERTIES_CUSTOMER_MIGRATION" ]; then
    php artisan migrate --path="$CUSTOMERS_MIGRATION" --force
    php artisan migrate --path="$PROPERTIES_CUSTOMER_MIGRATION" --force
else
    echo "WARNING: Expected customer migration files not found in this codebase."
    echo "WARNING: Running full migrations as fallback."
    php artisan migrate --force
fi

echo ""
echo "Rebuilding caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "=========================================="
echo "  HOTFIX COMPLETE"
echo "=========================================="
echo ""
echo "Validation hints:"
echo "  1) grep -n 'hasCustomersTable' app/Http/Controllers/Web/DashboardController.php"
echo "  2) php artisan migrate:status | grep customers"
echo ""
echo "Next: hard-refresh the browser (Ctrl+Shift+R)."
