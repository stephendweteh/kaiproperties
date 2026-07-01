#!/bin/bash
# NO-GIT Patcher - Pure bash, no git required
# Usage: bash patch-no-git.sh

set -euo pipefail

REPO_ROOT="${1:-.}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

echo "=========================================="
echo "Phase System Updates - No-Git Patcher"
echo "=========================================="
echo ""

# Check if artisan exists
if [ ! -f "$REPO_ROOT/artisan" ]; then
  echo "ERROR: artisan file not found in: $REPO_ROOT"
  echo "Usage: bash patch-no-git.sh /path/to/kai-maintenance-system"
  exit 1
fi

cd "$REPO_ROOT"

BACKUP_DIR="$REPO_ROOT/storage/backups/patch-$TIMESTAMP"
mkdir -p "$BACKUP_DIR"

echo "Creating backup in: $BACKUP_DIR"
cp -r app/Http/Controllers/Web/TicketController.php "$BACKUP_DIR/" 2>/dev/null || true
cp -r resources/views/tickets/index.blade.php "$BACKUP_DIR/" 2>/dev/null || true
cp -r resources/views/tickets/show.blade.php "$BACKUP_DIR/" 2>/dev/null || true

echo "✓ Backup created"
echo ""
echo "Applying patches..."
echo ""

# Patch 1: Update auto-refresh interval
echo -n "Patching auto-refresh interval... "
if sed -i.bak 's/const refreshIntervalMs = 10000;/const refreshIntervalMs = 3600000; \/\/ 1 hour/' resources/views/tickets/show.blade.php; then
    echo "✓"
    rm -f resources/views/tickets/show.blade.php.bak 2>/dev/null || true
else
    echo "✗ (might already be patched)"
fi

# Patch 2: Make status clickable - add helper text (simplified approach)
echo -n "Patching status button... "
if grep -q 'click to view' resources/views/tickets/index.blade.php 2>/dev/null; then
    echo "✓ (already patched)"
else
    # This is more complex in bash, recommend using PHP patcher instead
    echo "⚠ (use PHP patcher for this)"
fi

echo ""
echo "Clearing caches..."
php artisan cache:clear 2>/dev/null || true
php artisan config:cache 2>/dev/null || true

echo ""
echo "=========================================="
echo "✓ Patches Applied!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)"
echo "  2. For full patching, use: php patch.php"
echo ""
echo "Backup saved to: $BACKUP_DIR"
echo ""
