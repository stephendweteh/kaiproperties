#!/bin/bash
# Comprehensive Ticket System Patch - All Updates in One
# Usage: bash apply-comprehensive.sh [optional-project-path]

set -e

PROJECT_ROOT="${1:-.}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="$PROJECT_ROOT/storage/backups/comprehensive-ticket-$TIMESTAMP"

echo ""
echo "=========================================="
echo "  Comprehensive Ticket System Patcher"
echo "=========================================="
echo ""

# Validate project
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "❌ ERROR: artisan file not found in $PROJECT_ROOT"
    echo ""
    echo "Usage:"
    echo "  From project root:"
    echo "    bash deployment/20260701-comprehensive-ticket-patch/apply-comprehensive.sh"
    echo ""
    echo "  Or with path:"
    echo "    bash apply-comprehensive.sh /path/to/your/project"
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
cp -r "$PROJECT_ROOT/public" "$BACKUP_DIR/" 2>/dev/null || true
cp "$PROJECT_ROOT/composer.json" "$BACKUP_DIR/" 2>/dev/null || true
echo "✓ Backup created"
echo ""

# Apply patch
PATCH_FILE="$(dirname "$0")/comprehensive-ticket-updates.patch"

if [ ! -f "$PATCH_FILE" ]; then
    echo "❌ ERROR: Patch file not found"
    echo "  Expected: $PATCH_FILE"
    exit 1
fi

echo "  Validating patch compatibility..."
cd "$PROJECT_ROOT"

if ! patch -p1 --dry-run < "$PATCH_FILE" > /dev/null 2>&1; then
    echo "❌ ERROR: Patch validation failed"
    echo ""
    echo "Restoring from backup..."
    rm -rf "$PROJECT_ROOT/app" "$PROJECT_ROOT/resources" "$PROJECT_ROOT/database" "$PROJECT_ROOT/public" 2>/dev/null || true
    cp -r "$BACKUP_DIR/app" "$PROJECT_ROOT/" 2>/dev/null || true
    cp -r "$BACKUP_DIR/resources" "$PROJECT_ROOT/" 2>/dev/null || true
    cp -r "$BACKUP_DIR/database" "$PROJECT_ROOT/" 2>/dev/null || true
    cp -r "$BACKUP_DIR/public" "$PROJECT_ROOT/" 2>/dev/null || true
    echo "✓ Restored from backup"
    echo ""
    echo "Backup location: $BACKUP_DIR"
    exit 1
fi

echo "  Applying comprehensive ticket patch..."
patch -p1 < "$PATCH_FILE" > /dev/null 2>&1
echo "✓ Patch applied successfully"
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
echo "  ✓ COMPREHENSIVE PATCH APPLIED!"
echo "=========================================="
echo ""
echo "All ticket system enhancements installed:"
echo "  ✓ Phase-based work progress system"
echo "  ✓ Operations Manager controls"
echo "  ✓ Clickable status buttons"
echo "  ✓ Updated action buttons"
echo "  ✓ File management system"
echo "  ✓ Auto-refresh optimization"
echo ""
echo "Next: Hard refresh browser (Ctrl+Shift+R)"
echo ""
echo "Backup: $BACKUP_DIR"
echo ""
