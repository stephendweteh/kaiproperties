# Dashboard + Customers Production Hotfix
Date: 2026-07-03
Version: 1.0
Impact: Production Crash Fix

## Problem
Production dashboard was crashing with:

SQLSTATE[42S02]: Base table or view not found: 1146 Table ... customers doesn't exist

The crash happened because dashboard customer queries executed directly even when the customers table or properties.customer_id column was missing.

## What This Patch Does
1. Adds schema-aware guards in DashboardController:
- Checks table and column availability before customer/property customer-linked queries.
- Falls back to safe zeroed metrics when schema is missing.
- Wraps risky dashboard queries in QueryException handling.

2. Applies customer schema migrations explicitly:
- 2026_07_03_130000_create_customers_table.php
- 2026_07_03_130100_add_customer_id_to_properties_table.php

3. Clears and rebuilds Laravel caches:
- optimize:clear
- config:cache
- route:cache
- view:cache

## Files In This Patch
- apply.sh
- dashboard-customers-guard.patch

## One-Command Apply
Run from project root:

bash deployment/20260703-dashboard-customers-hotfix/apply.sh

Or provide path explicitly:

bash deployment/20260703-dashboard-customers-hotfix/apply.sh /path/to/kai-maintenance-system

## Validation After Apply
Run:

grep -n "hasCustomersTable" app/Http/Controllers/Web/DashboardController.php
php artisan migrate:status | grep customers

Dashboard should load without 500 even if customers schema is temporarily unavailable.

## Rollback
The script creates a timestamped backup of DashboardController at:

storage/backups/dashboard-customers-hotfix-<timestamp>/DashboardController.php

To rollback controller only:

cp storage/backups/dashboard-customers-hotfix-<timestamp>/DashboardController.php app/Http/Controllers/Web/DashboardController.php
php artisan optimize:clear
