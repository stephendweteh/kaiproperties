#!/usr/bin/env bash
# ONE-COMMAND PATCH DEPLOYMENT SCRIPT
# Usage: bash apply-to-live.sh
# This script applies the phase system updates patch to a live server

set -euo pipefail

REPO_ROOT="${1:-.}"
PATCH_URL="https://raw.githubusercontent.com/stephendweteh/kaiproperties/main/deployment/20260701-phase-system-updates-patch/phase-system-updates.patch"
PATCH_FILE="/tmp/phase-system-updates-$(date +%s).patch"

echo "=========================================="
echo "Phase System Updates - Live Server Patch"
echo "=========================================="
echo ""
echo "Target directory: $REPO_ROOT"
echo ""

# Validate directory
if [ ! -d "$REPO_ROOT" ] || [ ! -f "$REPO_ROOT/artisan" ]; then
  echo "ERROR: Invalid Laravel project directory: $REPO_ROOT" >&2
  echo "Usage: bash apply-to-live.sh /path/to/kai-maintenance-system" >&2
  exit 1
fi

cd "$REPO_ROOT"

# Download patch
echo "Downloading patch..."
if ! curl -fsSL -o "$PATCH_FILE" "$PATCH_URL"; then
  echo "ERROR: Failed to download patch from GitHub" >&2
  exit 1
fi

# Check if patch applies cleanly
echo "Validating patch..."
if ! git apply --check "$PATCH_FILE" 2>/dev/null; then
  echo "ERROR: Patch validation failed. There may be conflicts." >&2
  rm "$PATCH_FILE"
  exit 1
fi

# Backup current state
echo "Creating backup..."
BACKUP_DIR="/tmp/kai-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"
git diff HEAD > "$BACKUP_DIR/pre-patch.diff"
git status > "$BACKUP_DIR/status.txt"

# Apply patch
echo "Applying patch..."
if git apply "$PATCH_FILE"; then
  echo "✓ Patch applied successfully!"
else
  echo "ERROR: Failed to apply patch" >&2
  exit 1
fi

rm "$PATCH_FILE"

# Post-patch tasks
echo ""
echo "Running post-patch setup..."
php artisan cache:clear
php artisan config:cache

echo ""
echo "=========================================="
echo "✓ Deployment Complete!"
echo "=========================================="
echo ""
echo "Applied changes:"
echo "  • Made status pill clickable"
echo "  • Added 'click to view' helper text"
echo "  • Changed auto-refresh to 1 hour"
echo "  • Enabled Operations Manager phase management"
echo ""
echo "Files modified:"
echo "  • app/Http/Controllers/Web/TicketController.php"
echo "  • resources/views/tickets/index.blade.php"
echo "  • resources/views/tickets/show.blade.php"
echo "  • resources/views/layouts/app.blade.php"
echo "  • public/css/site.css"
echo ""
echo "Next steps:"
echo "  1. Hard refresh browser: Ctrl+Shift+R (or Cmd+Shift+R)"
echo "  2. Test Operations Manager phase management"
echo "  3. Verify status button is clickable and shows 'click to view'"
echo ""
echo "Backup saved to: $BACKUP_DIR"
echo ""
