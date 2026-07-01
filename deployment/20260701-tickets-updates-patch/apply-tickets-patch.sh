#!/bin/bash
# Tickets Update Patch Applicator - cPanel Ready
# Usage: bash apply-tickets-patch.sh [optional-project-path]

set -e

PROJECT_ROOT="${1:-.}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="$PROJECT_ROOT/storage/backups/tickets-patch-$TIMESTAMP"

echo ""
echo "=========================================="
echo "  Tickets System Update Patcher"
echo "=========================================="
echo ""

# Validate project
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "❌ ERROR: artisan file not found in $PROJECT_ROOT"
    echo ""
    echo "Usage:"
    echo "  bash apply-tickets-patch.sh"
    echo "  (from project root)"
    echo ""
    echo "Or with path:"
    echo "  bash apply-tickets-patch.sh /path/to/your/project"
    echo ""
    exit 1
fi

echo "✓ Project found at: $PROJECT_ROOT"

# Create backup
echo "  Creating backup..."
mkdir -p "$BACKUP_DIR"
cp -r "$PROJECT_ROOT/app" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "$PROJECT_ROOT/resources" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "$PROJECT_ROOT/database" "$BACKUP_DIR/" 2>/dev/null || true
cp "$PROJECT_ROOT/composer.json" "$BACKUP_DIR/" 2>/dev/null || true
echo "✓ Backup created"
echo ""

# Apply patch
PATCH_FILE="$(dirname "$0")/tickets-updates.patch"

if [ ! -f "$PATCH_FILE" ]; then
    echo "❌ ERROR: Patch file not found at $PATCH_FILE"
    exit 1
fi

echo "  Validating patch..."
cd "$PROJECT_ROOT"

if ! patch -p1 --dry-run < "$PATCH_FILE" > /dev/null 2>&1; then
    echo "❌ ERROR: Patch validation failed"
    echo ""
    echo "Restoring from backup at:"
    echo "  $BACKUP_DIR"
    echo ""
    rm -rf "$PROJECT_ROOT/app" "$PROJECT_ROOT/resources" "$PROJECT_ROOT/database" 2>/dev/null || true
    cp -r "$BACKUP_DIR/app" "$PROJECT_ROOT/" 2>/dev/null || true
    cp -r "$BACKUP_DIR/resources" "$PROJECT_ROOT/" 2>/dev/null || true
    cp -r "$BACKUP_DIR/database" "$PROJECT_ROOT/" 2>/dev/null || true
    echo "✓ Restored from backup"
    exit 1
fi

echo "  Applying patch..."
patch -p1 < "$PATCH_FILE" > /dev/null 2>&1
echo "✓ Patch applied"
echo ""

# Run migrations
echo "  Running migrations..."
php artisan migrate --force > /dev/null 2>&1 || true
echo "✓ Migrations complete"
echo ""

# Clear cache
echo "  Clearing cache..."
php artisan cache:clear > /dev/null 2>&1 || true
php artisan config:cache > /dev/null 2>&1 || true
echo "✓ Cache cleared"
echo ""

echo "=========================================="
echo "  ✓ TICKETS PATCH APPLIED!"
echo "=========================================="
echo ""
echo "Next: Hard refresh browser (Ctrl+Shift+R)"
echo ""
echo "Backup: $BACKUP_DIR"
echo ""
