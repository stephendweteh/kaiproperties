# Full Database Migration Package
Date: 2026-07-03
Version: 1.0
Target: cPanel production server

## Purpose
This package runs all pending Laravel database migrations safely on the target server and rebuilds caches.

## Included Files
- apply.sh

## What apply.sh does
1. Validates Laravel project root (checks artisan file)
2. Clears optimization caches
3. Prints migration status before migration
4. Runs all pending migrations with --force
5. Prints migration status after migration
6. Rebuilds config, route, and view caches
7. Writes a full execution log into storage/logs

## One-command usage
From project root:

bash deployment/20260703-full-database-migration/apply.sh

Or with explicit path:

bash deployment/20260703-full-database-migration/apply.sh /home/tackjdgn/kai.tacklehubs.tech

## Output log
The script writes a timestamped log file to:

storage/logs/full-database-migration-YYYYmmdd-HHMMSS.log

## Notes
- This script does not overwrite application code.
- It only applies pending database migrations.
- Existing migrations are not rerun by Laravel.
