# Estimated Cost Currency Internal Server Error Hotfix
Date: 2026-07-03
Version: 1.0
Target: cPanel production server

## Error Fixed
SQLSTATE[42S22]: Column not found: 1054
Unknown column estimated_cost_currency in SET

## Root Cause
Production database is missing the tickets.estimated_cost_currency column while the deployed ticket update code writes to that field.

## What This Fresh Fix Does
1. Ensures migration file exists in database/migrations:
- 2026_07_03_120000_add_estimated_cost_currency_to_tickets_table.php
2. Runs Laravel migrations in production with --force.
3. Verifies tickets.estimated_cost_currency column exists after migration.
4. Rebuilds Laravel runtime caches.

## Included Files
- apply.sh
- PATCH.md
- files/database/migrations/2026_07_03_120000_add_estimated_cost_currency_to_tickets_table.php

## One-Command Apply
From project root:

bash deployment/20260703-estimated-cost-currency-hotfix/apply.sh

Or with explicit path:

bash deployment/20260703-estimated-cost-currency-hotfix/apply.sh /home/tackjdgn/kai.tacklehubs.tech

## Validation
Run:

php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasColumn('tickets', 'estimated_cost_currency') ? 'yes' : 'no';"

Expected output: yes

Then retry ticket update and hard refresh browser (Ctrl+Shift+R).
