#!/bin/bash
# SIMPLE CPANEL PATCHER - Works from project root
# Usage: cd /your/project && bash patch-cpanel.sh

set -e

PROJECT_ROOT="$(pwd)"

echo "=========================================="
echo "Simple cPanel Patcher"
echo "=========================================="
echo ""
echo "Project: $PROJECT_ROOT"
echo ""

# Check if we're in the right place
if [ ! -f "artisan" ]; then
    echo "ERROR: artisan file not found!"
    echo "Please run from your project root directory"
    exit 1
fi

echo "✓ Project found"
echo ""

# Try PHP patcher first
if [ -f "deployment/20260701-phase-system-updates-patch/patch.php" ]; then
    echo "Running PHP patcher..."
    php deployment/20260701-phase-system-updates-patch/patch.php "$PROJECT_ROOT"
    
    echo ""
    echo "Clearing cache..."
    php artisan cache:clear
    php artisan config:cache
    
    echo ""
    echo "=========================================="
    echo "✓ Patch Applied!"
    echo "=========================================="
    echo ""
    echo "Next: Hard refresh browser (Ctrl+Shift+R)"
    exit 0
fi

# Fallback to patch command
PATCH_FILE="deployment/20260701-phase-system-updates-patch/phase-system-updates.patch"

if [ ! -f "$PATCH_FILE" ]; then
    echo "ERROR: patch files not found in deployment folder"
    exit 1
fi

echo "Applying patch..."
if patch -p1 < "$PATCH_FILE"; then
    echo "✓ Patch applied successfully"
else
    echo "ERROR: Patch failed"
    exit 1
fi

echo ""
echo "Clearing cache..."
php artisan cache:clear
php artisan config:cache

echo ""
echo "=========================================="
echo "✓ Patch Applied!"
echo "=========================================="
echo ""
echo "Next: Hard refresh browser (Ctrl+Shift+R)"
