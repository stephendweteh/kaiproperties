#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Get project root - try multiple methods
if [ -z "${1:-}" ]; then
  # Try to auto-detect by going up 2 directories
  REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
else
  # Use provided path
  REPO_ROOT="$1"
fi

PATCH_FILE="$SCRIPT_DIR/phase-system-updates.patch"

# Validate project root
if [ ! -f "$REPO_ROOT/artisan" ]; then
  echo "ERROR: Could not find Laravel project at: $REPO_ROOT"
  echo ""
  echo "Usage: $0 /path/to/kai-maintenance-system"
  echo ""
  echo "Or extract zip in your project and run:"
  echo "  cd /path/to/kai-maintenance-system"
  echo "  bash deployment/20260701-phase-system-updates-patch/apply.sh"
  exit 1
fi

cd "$REPO_ROOT"

if [ ! -f "$PATCH_FILE" ]; then
  echo "ERROR: Patch file not found: $PATCH_FILE" >&2
  exit 1
fi

echo "Applying phase system updates patch..."

# Apply the patch
if git apply --check "$PATCH_FILE" 2>/dev/null; then
  git apply "$PATCH_FILE"
  echo "✓ Patch applied successfully!"
  echo ""
  echo "Applied changes:"
  echo "  - Made status pill clickable to view ticket details"
  echo "  - Added 'click to view' helper text above status button"
  echo "  - Changed auto-refresh interval from 10 seconds to 1 hour"
  echo "  - Enabled Operations Manager to manage phases from ticket view"
  echo "  - Updated controller authorization for ops manager phase actions"
  echo "  - Added phase management form in show view"
  echo ""
  echo "Files modified:"
  echo "  - app/Http/Controllers/Web/TicketController.php"
  echo "  - resources/views/tickets/index.blade.php"
  echo "  - resources/views/tickets/show.blade.php"
  echo "  - resources/views/layouts/app.blade.php"
  echo "  - public/css/site.css"
  echo ""
  echo "Next steps:"
  echo "  1. Run: php artisan migrate (if needed)"
  echo "  2. Clear cache: php artisan cache:clear"
  echo "  3. Hard refresh browser: Ctrl+Shift+R (or Cmd+Shift+R on Mac)"
else
  echo "ERROR: Patch could not be applied. There may be conflicts." >&2
  echo "Try manually applying with: patch -p1 < $PATCH_FILE" >&2
  exit 1
fi
