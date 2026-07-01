#!/bin/bash
# One-liner installer for cPanel
# Just run this single line from your project root:
# bash <(curl -s https://raw.githubusercontent.com/stephendweteh/kaiproperties/main/deployment/20260701-phase-system-updates-patch/one-liner.sh)

cd "$(pwd)" && \
if [ -f "artisan" ]; then \
    if [ -f "deployment/20260701-phase-system-updates-patch/patch.php" ]; then \
        php deployment/20260701-phase-system-updates-patch/patch.php "$(pwd)" && \
        php artisan cache:clear && \
        php artisan config:cache && \
        echo "" && \
        echo "✓ Patch Applied! Hard refresh browser (Ctrl+Shift+R)"; \
    else \
        echo "ERROR: Patch files not found"; \
    fi; \
else \
    echo "ERROR: Not in project root. Run from directory with artisan file."; \
fi
