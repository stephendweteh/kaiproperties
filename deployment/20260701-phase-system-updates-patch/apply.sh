#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PATCH_FILE="$SCRIPT_DIR/phase-system-updates.patch"

cd "$REPO_ROOT"

if [ ! -f "$PATCH_FILE" ]; then
  echo "ERROR: Patch file not found: $PATCH_FILE" >&2
  exit 1
fi

# Check if patch has already been applied
if git rev-parse --verify 128c595 >/dev/null 2>&1; then
  echo "Patch already applied. Current commit: $(git rev-parse --short HEAD)"
  exit 0
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
