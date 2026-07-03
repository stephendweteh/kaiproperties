# Internal Server Error Fresh Fix
Date: 2026-07-03
Version: 1.0
Target: cPanel production hotfix

## Error Fixed
SQLSTATE[42S02]: Base table or view not found: 1146
Table tackjdgn_kaisystem.customers doesn't exist

## Root Cause
1. Server was running an old DashboardController that queried customers directly without schema guards.
2. Customer migration files were missing or not yet applied on production.

## This Fresh Fix Includes
1. Safe DashboardController replacement with schema-aware guards.
2. Bundled migration files:
- 2026_07_03_130000_create_customers_table.php
- 2026_07_03_130100_add_customer_id_to_properties_table.php
3. One-command apply script that:
- Backs up current DashboardController
- Installs missing migrations if absent
- Runs migrate --force
- Clears and rebuilds caches
- Logs full output to storage/logs

## One-Command Apply
From project root:

bash deployment/20260703-internal-server-error-fix/apply.sh

Or explicitly:

bash deployment/20260703-internal-server-error-fix/apply.sh /home/tackjdgn/kai.tacklehubs.tech

## Package Layout
- apply.sh
- PATCH.md
- files/app/Http/Controllers/Web/DashboardController.php
- files/database/migrations/2026_07_03_130000_create_customers_table.php
- files/database/migrations/2026_07_03_130100_add_customer_id_to_properties_table.php

## Validation After Apply
Run:

grep -n "hasCustomersTable" app/Http/Controllers/Web/DashboardController.php
php artisan migrate:status | grep -E "customers|customer_id"

Dashboard should stop throwing 500 on customer table lookup.
