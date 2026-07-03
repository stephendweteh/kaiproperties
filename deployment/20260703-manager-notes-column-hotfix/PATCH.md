# Manager Notes Column Internal Server Error Hotfix
Date: 2026-07-03
Version: 1.0
Target: cPanel production server

## Error Fixed
SQLSTATE[42S22]: Column not found: 1054
Unknown column manager_notes in SET

## Root Cause
Production database is missing ticket_phases.manager_notes while the deployed ticket phase comment flow updates that column.

## What This Fresh Fix Does
1. Ensures migration file exists in database/migrations:
- 2026_07_03_000000_add_manager_notes_to_ticket_phases_table.php
2. Runs Laravel migration with --force.
3. Verifies ticket_phases.manager_notes exists after migration.
4. Rebuilds Laravel runtime caches.

## Included Files
- apply.sh
- PATCH.md
- files/database/migrations/2026_07_03_000000_add_manager_notes_to_ticket_phases_table.php

## One-Command Apply
From project root:

bash deployment/20260703-manager-notes-column-hotfix/apply.sh

Or with explicit path:

bash deployment/20260703-manager-notes-column-hotfix/apply.sh /home/tackjdgn/kai.tacklehubs.tech

## Validation
Run:

php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasColumn('ticket_phases', 'manager_notes') ? 'yes' : 'no';"

Expected output: yes

Then retry adding phase comment and hard refresh browser (Ctrl+Shift+R).
