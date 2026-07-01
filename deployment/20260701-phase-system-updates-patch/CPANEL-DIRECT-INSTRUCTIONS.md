# Phase System Updates - cPanel Direct Deployment Guide

**Patch Date:** 2026-07-01  
**Version:** 1.0

## ⚠️ Simple One-Line Deployment for cPanel

If you can't run the shell scripts, use these direct commands instead:

### Step 1: SSH into cPanel Terminal
Navigate to your project root directory where `artisan` file is located

### Step 2: Download and Apply Patch (Copy & Paste Command)

```bash
cd /path/to/kai-maintenance-system && git pull origin main && php artisan cache:clear && php artisan config:cache && echo "✓ Patch applied successfully!"
```

**Replace `/path/to/kai-maintenance-system` with your actual project path**

### Step 3: Clear Browser Cache
Hard refresh your browser:
- **Windows/Linux:** `Ctrl + Shift + R`
- **Mac:** `Cmd + Shift + R`

---

## What Gets Updated

The patch automatically applies these changes:
- ✓ Ticket status becomes clickable
- ✓ Adds "click to view" helper text
- ✓ Changes auto-refresh from 10 seconds to 1 hour
- ✓ Enables Operations Manager to manage phases
- ✓ Updates controller authorization

---

## If Git Pull Shows Conflicts

If you see merge conflicts, run this instead:

```bash
cd /path/to/kai-maintenance-system
git fetch origin
git reset --hard origin/main
php artisan cache:clear
php artisan config:cache
```

This will reset to the latest code from GitHub.

---

## If You Need the Patch File Directly

Download the patch file from GitHub and apply it manually:

```bash
cd /path/to/kai-maintenance-system
curl -o phase-patch.patch https://raw.githubusercontent.com/stephendweteh/kaiproperties/main/deployment/20260701-phase-system-updates-patch/phase-system-updates.patch
git apply phase-patch.patch
php artisan cache:clear
php artisan config:cache
rm phase-patch.patch
```

---

## Verify Deployment Was Successful

After running commands above, check:

1. **Via GitHub:** Verify your repo is at commit `dd4b154` or later
   ```bash
   git log -1 --oneline
   ```

2. **In Application:** 
   - Ticket status in list should show "click to view" text above it
   - Clicking status should open ticket detail
   - Operations Managers should see "Manage Work Phases" section in ticket detail

---

## Troubleshooting

### "Command not found: git"
- cPanel likely doesn't have git installed
- Contact your hosting provider to enable git
- Alternative: Manual file upload (see below)

### "Permission denied"
```bash
chmod 644 /path/to/kai-maintenance-system/resources/views/tickets/index.blade.php
chmod 644 /path/to/kai-maintenance-system/resources/views/tickets/show.blade.php
```

### Need Manual Update (No Git Access)?
Files to update in cPanel File Manager:

**File 1: `/resources/views/tickets/index.blade.php`**
- Find the status pill around line 77
- Add above it: `<small style="font-size: 0.7rem; color: #666;">click to view</small>`

**File 2: `/resources/views/tickets/show.blade.php`**
- Around line 235: Change `const refreshIntervalMs = 10000;` to `const refreshIntervalMs = 3600000; // 1 hour`
- Add phase management form (see patch file for details)

---

## Quick Reference - All Files Modified

1. `app/Http/Controllers/Web/TicketController.php`
2. `resources/views/tickets/index.blade.php`
3. `resources/views/tickets/show.blade.php`
4. `resources/views/layouts/app.blade.php`
5. `public/css/site.css`

---

## Need More Help?

**Recommended:** Use git pull command:
```bash
cd /path/to/kai-maintenance-system && git pull origin main
```

This is the safest and most reliable method for cPanel.

