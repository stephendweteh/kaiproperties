#!/bin/bash
# FULL PROJECT DEPLOYMENT - Overwrite Existing Installation
# This script extracts and overwrites all project files on cPanel
# Usage: bash deploy.sh [optional-project-path]

set -e

PROJECT_ROOT="${1:-.}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="$PROJECT_ROOT/storage/backups/deployment-$TIMESTAMP"

echo ""
echo "=========================================="
echo "  FULL PROJECT DEPLOYMENT"
echo "=========================================="
echo ""

# Validate project
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "❌ ERROR: artisan file not found in $PROJECT_ROOT"
    echo ""
    echo "Usage:"
    echo "  bash deploy.sh"
    echo "  (from project root)"
    exit 1
fi

echo "✓ Project found at: $PROJECT_ROOT"
cd "$PROJECT_ROOT"

# Create backup of critical directories
echo "  Creating backup..."
mkdir -p "$BACKUP_DIR"
cp -r "app" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "resources" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "database" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "routes" "$BACKUP_DIR/" 2>/dev/null || true
cp "composer.json" "$BACKUP_DIR/" 2>/dev/null || true
cp ".env" "$BACKUP_DIR/" 2>/dev/null || true
echo "✓ Backup created: $BACKUP_DIR"
echo ""

# Extract deployment archive
echo "  Extracting deployment files..."
DEPLOY_FILE="$(dirname "$0")/kai-built-project.tar.gz"

if [ ! -f "$DEPLOY_FILE" ]; then
    echo "❌ ERROR: Deployment file not found: $DEPLOY_FILE"
    echo ""
    echo "Expected: $(dirname "$0")/kai-built-project.tar.gz"
    exit 1
fi

# Extract to temporary directory
TMP_EXTRACT="/tmp/kai-deploy-$TIMESTAMP"
mkdir -p "$TMP_EXTRACT"
tar -xzf "$DEPLOY_FILE" -C "$TMP_EXTRACT"

echo "✓ Files extracted"
echo ""

# Copy files, preserving .env and important configs
echo "  Installing files..."

# Copy app directory
if [ -d "$TMP_EXTRACT/app" ]; then
    rm -rf "app"
    cp -r "$TMP_EXTRACT/app" "app"
    echo "    ✓ app/"
fi

# Copy resources directory
if [ -d "$TMP_EXTRACT/resources" ]; then
    rm -rf "resources"
    cp -r "$TMP_EXTRACT/resources" "resources"
    echo "    ✓ resources/"
fi

# Copy routes directory
if [ -d "$TMP_EXTRACT/routes" ]; then
    rm -rf "routes"
    cp -r "$TMP_EXTRACT/routes" "routes"
    echo "    ✓ routes/"
fi

# Copy config directory
if [ -d "$TMP_EXTRACT/config" ]; then
    cp -r "$TMP_EXTRACT/config"/* "config/" 2>/dev/null || true
    echo "    ✓ config/"
fi

# Copy bootstrap (but preserve cache)
if [ -d "$TMP_EXTRACT/bootstrap" ]; then
    cp "$TMP_EXTRACT/bootstrap/app.php" "bootstrap/app.php" 2>/dev/null || true
    cp "$TMP_EXTRACT/bootstrap/providers.php" "bootstrap/providers.php" 2>/dev/null || true
    echo "    ✓ bootstrap/"
fi

# Copy database migrations
if [ -d "$TMP_EXTRACT/database/migrations" ]; then
    mkdir -p "database/migrations"
    cp -r "$TMP_EXTRACT/database/migrations"/* "database/migrations/" 2>/dev/null || true
    echo "    ✓ database/migrations/"
fi

# Copy public files (assets, css, js)
if [ -d "$TMP_EXTRACT/public" ]; then
    # Keep storage symlink
    if [ -L "public/storage" ]; then
        STORAGE_LINK=$(readlink "public/storage")
    fi
    
    # Copy public files
    cp -r "$TMP_EXTRACT/public"/* "public/" 2>/dev/null || true
    echo "    ✓ public/"
    
    # Restore storage symlink if needed
    if [ ! -z "$STORAGE_LINK" ] && [ ! -L "public/storage" ]; then
        ln -s "$STORAGE_LINK" "public/storage"
    fi
fi

# Copy vendor directory
if [ -d "$TMP_EXTRACT/vendor" ]; then
    echo "    Installing vendor packages (this may take a minute)..."
    rm -rf "vendor"
    cp -r "$TMP_EXTRACT/vendor" "vendor"
    echo "    ✓ vendor/"
fi

echo ""

# Fix permissions
echo "  Setting permissions..."
chmod -R 755 app/ resources/ routes/ config/ database/ public/ bootstrap/ 2>/dev/null || true
chmod -R 777 storage/ bootstrap/cache/ 2>/dev/null || true
chmod 755 artisan 2>/dev/null || true
chmod 755 public/index.php 2>/dev/null || true
echo "✓ Permissions set"
echo ""

# Clear caches
echo "  Clearing caches..."
rm -rf bootstrap/cache/*.php 2>/dev/null || true
rm -rf storage/framework/cache/* 2>/dev/null || true
rm -rf storage/framework/views/* 2>/dev/null || true
mkdir -p storage/framework/{cache,views}
mkdir -p storage/logs
echo "✓ Caches cleared"
echo ""

# Run Laravel optimization
echo "  Running Laravel optimization..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan migrate --force 2>/dev/null || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
echo "✓ Optimization complete"
echo ""

# Cleanup
rm -rf "$TMP_EXTRACT"

echo "=========================================="
echo "  ✓ DEPLOYMENT COMPLETE!"
echo "=========================================="
echo ""
echo "Files deployed:"
echo "  ✓ Application code (app/)"
echo "  ✓ Views & resources (resources/)"
echo "  ✓ Routes (routes/)"
echo "  ✓ Configuration (config/)"
echo "  ✓ Database migrations"
echo "  ✓ Public assets (build/)"
echo "  ✓ Vendor packages"
echo ""
echo "Next: Hard refresh browser (Ctrl+Shift+R)"
echo ""
echo "Backup location: $BACKUP_DIR"
echo "Restore: cp -r $BACKUP_DIR/* ."
echo ""

# Show file count
DEPLOYMENT_TIME=$(($(date +%s) - $(stat -f %B "$DEPLOY_FILE" 2>/dev/null || stat -c %Y "$DEPLOY_FILE")))
echo "Deployment took: $(printf '%02d:%02d' $((DEPLOYMENT_TIME / 60)) $((DEPLOYMENT_TIME % 60))) seconds"
echo ""
