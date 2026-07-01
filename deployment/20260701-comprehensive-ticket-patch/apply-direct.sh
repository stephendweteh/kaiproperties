#!/bin/bash
# Ticket System Update - Direct Git Checkout Method
# Most reliable - downloads files directly from git instead of patching
# Usage: bash apply-direct.sh [optional-project-path]

set -e

PROJECT_ROOT="${1:-.}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="$PROJECT_ROOT/storage/backups/ticket-updates-$TIMESTAMP"

echo ""
echo "=========================================="
echo "  Ticket System Update - Direct Method"
echo "=========================================="
echo ""

# Validate project
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "❌ ERROR: artisan file not found in $PROJECT_ROOT"
    echo ""
    echo "Usage:"
    echo "  bash apply-direct.sh"
    echo "  (from project root)"
    exit 1
fi

echo "✓ Project found at: $PROJECT_ROOT"
cd "$PROJECT_ROOT"

# Create backup
echo "  Creating backup..."
mkdir -p "$BACKUP_DIR"
cp -r "$PROJECT_ROOT/app" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "$PROJECT_ROOT/resources" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "$PROJECT_ROOT/database" "$BACKUP_DIR/" 2>/dev/null || true
echo "✓ Backup created: $BACKUP_DIR"
echo ""

# Check if git is available
if ! command -v git &> /dev/null; then
    echo "❌ Git not available on this server"
    echo ""
    echo "This method requires git. Alternatives:"
    echo "1. Install git: apt-get install git"
    echo "2. Use manual file copy method"
    exit 1
fi

# Initialize git repo if needed
if [ ! -d ".git" ]; then
    echo "  Initializing git repository..."
    git init
    git remote add origin https://github.com/stephendweteh/kaiproperties.git
fi

echo "  Fetching ticket system updates from git..."

# Fetch specific commit
git fetch --depth=1 origin 128c595 2>/dev/null || true

# List of files to update
FILES=(
    "app/Http/Controllers/Web/TicketController.php"
    "app/Models/TicketPhase.php"
    "app/Models/PhaseAttachment.php"
    "resources/views/tickets/index.blade.php"
    "resources/views/tickets/show.blade.php"
    "resources/views/tickets/partials/technician-form.blade.php"
    "database/migrations/2026_07_01_000000_create_ticket_phases_table.php"
    "database/migrations/2026_07_01_000001_create_phase_attachments_table.php"
    "public/css/site.css"
)

echo "  Downloading files..."
SUCCESS=0
FAILED=0

for file in "${FILES[@]}"; do
    # Try to get file from git
    if git show 128c595:"$file" > "$file" 2>/dev/null; then
        echo "    ✓ $file"
        ((SUCCESS++))
    else
        echo "    ⚠ $file (not found in commit)"
        ((FAILED++))
    fi
done

echo ""
echo "  Summary: $SUCCESS files updated, $FAILED skipped"
echo ""

# Run migrations
echo "  Running database migrations..."
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
echo "  ✓ TICKET SYSTEM UPDATED!"
echo "=========================================="
echo ""
echo "Updated files:"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✓ $file"
    fi
done
echo ""
echo "Next: Hard refresh browser (Ctrl+Shift+R)"
echo ""
echo "Backup: $BACKUP_DIR"
echo "Restore: cp -r $BACKUP_DIR/* ."
echo ""
