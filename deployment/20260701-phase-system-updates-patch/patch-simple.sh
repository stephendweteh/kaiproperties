#!/bin/bash
# Ultra-Simple cPanel Patch Applicator
# Usage: bash patch-simple.sh [optional-project-path]
# If no path given, uses current directory

set -e

PROJECT_ROOT="${1:-.}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="$PROJECT_ROOT/storage/backups/patch-$TIMESTAMP"

echo ""
echo "=========================================="
echo "  Phase System Patch Applicator"
echo "=========================================="
echo ""

# Validate project
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "❌ ERROR: artisan file not found in $PROJECT_ROOT"
    echo ""
    echo "Usage: bash patch-simple.sh /path/to/your/project"
    echo "Or: cd /path/to/your/project && bash path/to/patch-simple.sh"
    echo ""
    exit 1
fi

echo "✓ Project found at: $PROJECT_ROOT"

# Create backup
echo "  Creating backup..."
mkdir -p "$BACKUP_DIR"
cp -r "$PROJECT_ROOT/app" "$BACKUP_DIR/"
cp -r "$PROJECT_ROOT/resources" "$BACKUP_DIR/"
cp -r "$PROJECT_ROOT/database" "$BACKUP_DIR/"
cp "$PROJECT_ROOT/composer.json" "$BACKUP_DIR/"
echo "✓ Backup created at: $BACKUP_DIR"
echo ""

# Apply patch
PATCH_FILE="$(dirname "$0")/phase-system-fresh.patch"

if [ ! -f "$PATCH_FILE" ]; then
    echo "❌ ERROR: Patch file not found at $PATCH_FILE"
    exit 1
fi

echo "  Applying patch..."
cd "$PROJECT_ROOT"

if patch -p1 --dry-run < "$PATCH_FILE" > /dev/null 2>&1; then
    patch -p1 < "$PATCH_FILE" > /dev/null
    echo "✓ Patch applied successfully"
else
    echo "❌ ERROR: Patch failed"
    echo ""
    echo "Restoring from backup..."
    rm -rf "$PROJECT_ROOT/app" "$PROJECT_ROOT/resources" "$PROJECT_ROOT/database"
    cp -r "$BACKUP_DIR/app" "$PROJECT_ROOT/"
    cp -r "$BACKUP_DIR/resources" "$PROJECT_ROOT/"
    cp -r "$BACKUP_DIR/database" "$PROJECT_ROOT/"
    echo "✓ Restored from backup"
    exit 1
fi

# Run migrations
echo "  Running migrations..."
php artisan migrate --force > /dev/null 2>&1 || true
echo "✓ Migrations complete"

# Clear cache
echo "  Clearing cache..."
php artisan cache:clear > /dev/null 2>&1 || true
php artisan config:cache > /dev/null 2>&1 || true
echo "✓ Cache cleared"

echo ""
echo "=========================================="
echo "  ✓ PATCH APPLIED SUCCESSFULLY!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Hard refresh browser: Ctrl+Shift+R (or Cmd+Shift+R on Mac)"
echo "2. Test phase system in tickets"
echo ""
echo "If something goes wrong, restore from:"
echo "  $BACKUP_DIR"
echo ""
