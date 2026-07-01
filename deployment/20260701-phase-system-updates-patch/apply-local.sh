#!/usr/bin/env bash
# SIMPLE LOCAL APPLY - Works in current directory
# Usage: bash apply-local.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(pwd)"

echo "=========================================="
echo "Phase System Updates - Local Patcher"
echo "=========================================="
echo ""

# Validate we're in the right place
if [ ! -f "$REPO_ROOT/artisan" ]; then
  echo "ERROR: artisan file not found in current directory!"
  echo ""
  echo "Please navigate to your project root first:"
  echo "  cd /path/to/kai-maintenance-system"
  echo "  bash deployment/20260701-phase-system-updates-patch/apply-local.sh"
  echo ""
  exit 1
fi

echo "Project directory: $REPO_ROOT"
echo ""

# Check for patch.php and use that instead
if [ -f "$SCRIPT_DIR/patch.php" ]; then
  echo "Found patch.php - using PHP patcher (more reliable)..."
  echo ""
  php "$SCRIPT_DIR/patch.php"
  
  echo ""
  echo "Clearing Laravel cache..."
  php artisan cache:clear 2>/dev/null || true
  php artisan config:cache 2>/dev/null || true
  
  echo ""
  echo "=========================================="
  echo "✓ Patch Applied Successfully!"
  echo "=========================================="
  echo ""
  echo "Next steps:"
  echo "  1. Hard refresh browser: Ctrl+Shift+R"
  echo "  2. Test status button is clickable"
  echo "  3. Verify 'click to view' text appears"
  echo ""
  exit 0
fi

# Fallback to patch command if patch.php not found
PATCH_FILE="$SCRIPT_DIR/phase-system-updates.patch"

if [ ! -f "$PATCH_FILE" ]; then
  echo "ERROR: Could not find patch files!"
  exit 1
fi

echo "Applying patch using: patch command"
echo ""

if patch -p1 --dry-run < "$PATCH_FILE" > /dev/null 2>&1; then
  echo "✓ Patch validation passed"
  patch -p1 < "$PATCH_FILE"
  echo "✓ Patch applied"
else
  echo "ERROR: Patch validation failed - files may already be patched"
  exit 1
fi

echo ""
echo "Clearing Laravel cache..."
php artisan cache:clear 2>/dev/null || true
php artisan config:cache 2>/dev/null || true

echo ""
echo "=========================================="
echo "✓ Patch Applied Successfully!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Hard refresh browser: Ctrl+Shift+R"
echo "  2. Test status button is clickable"
echo ""
