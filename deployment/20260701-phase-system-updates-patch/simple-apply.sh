#!/bin/bash
# CPANEL SIMPLE PATCH APPLY - No dependencies required
# Just copy and paste the commands below into your cPanel terminal

echo "=========================================="
echo "Phase System Updates for cPanel"
echo "=========================================="
echo ""

# Get project path
REPO_ROOT="${1:-.}"

if [ ! -f "$REPO_ROOT/artisan" ]; then
  echo "ERROR: artisan file not found in: $REPO_ROOT"
  echo "Usage: bash simple-apply.sh /path/to/kai-maintenance-system"
  exit 1
fi

cd "$REPO_ROOT"

echo "Current location: $(pwd)"
echo ""

# Check if git is available
if ! command -v git &> /dev/null; then
  echo "⚠️  Git not found on your server."
  echo ""
  echo "If you have shell/terminal access via cPanel, try:"
  echo "1. Use File Manager to manually upload updated files"
  echo "2. Contact your hosting provider to enable git"
  echo "3. Use the git pull command from terminal:"
  echo "   cd $REPO_ROOT && git pull origin main"
  exit 1
fi

echo "Fetching latest code from GitHub..."
git fetch origin

echo "Pulling latest changes..."
if git pull origin main; then
  echo "✓ Code updated successfully"
else
  echo "⚠️  Pull had conflicts. Using hard reset..."
  git reset --hard origin/main
  echo "✓ Reset to latest version"
fi

echo ""
echo "Clearing application cache..."
php artisan cache:clear
php artisan config:cache

echo ""
echo "=========================================="
echo "✓ PATCH APPLIED SUCCESSFULLY!"
echo "=========================================="
echo ""
echo "Changes made:"
echo "  • Status pill is now clickable"
echo "  • 'Click to view' helper text added"
echo "  • Auto-refresh changed to 1 hour"
echo "  • Operations Manager can manage phases"
echo ""
echo "Next steps:"
echo "  1. Hard refresh your browser (Ctrl+Shift+R or Cmd+Shift+R)"
echo "  2. Test status button clickability"
echo "  3. Verify Operations Manager sees 'Manage Work Phases' section"
echo ""
echo "Current commit: $(git log -1 --oneline)"
echo ""
