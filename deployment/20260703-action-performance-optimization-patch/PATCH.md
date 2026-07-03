# Action Performance Optimization Patch
Date: 2026-07-03
Scope: Ticket actions, user creation, signup, and notification timing

## Problem
Ticket creation, ticket status changes, and user creation/update flows were spending request time on synchronous email/SMS notifications and repeated lookup work.

## Changes
1. Deferred notification sends until after the response lifecycle for the main ticket and user/auth action paths.
2. Removed repeated settings table checks and added a tiny in-process cache for settings lookups.
3. Kept the existing behavior of ticket, user, and auth actions intact while reducing visible latency.
4. Preserved the Operations Manager regression coverage and the ticket update fix.

## Files Included
- app/Http/Controllers/Api/Admin/UserController.php
- app/Http/Controllers/Api/AuthController.php
- app/Http/Controllers/Api/TicketController.php
- app/Http/Controllers/Web/Admin/UserController.php
- app/Http/Controllers/Web/AuthController.php
- app/Http/Controllers/Web/TicketController.php
- app/Models/Setting.php
- tests/Feature/OperationsManagerRoleTest.php

## Apply
Run from anywhere:

```bash
bash deployment/20260703-action-performance-optimization-patch/apply.sh
```

## Verify
If dev dependencies are installed:

```bash
./vendor/bin/phpunit --filter 'OperationsManagerRoleTest|ManagementRolesTicketFlowTest|TicketAttachmentTest|TicketOverdueStatusTest'
```

Expected result: focused ticket and operations manager tests pass.
