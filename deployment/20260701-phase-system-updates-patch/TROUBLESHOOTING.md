# 🆘 PATCH TROUBLESHOOTING GUIDE

## Quick Fix - Use Fresh Patch

If the old patch isn't working, use the **fresh patch** instead:

```bash
cd /home/tackjdgn/kai.tacklehubs.tech
bash deployment/20260701-phase-system-updates-patch/patch-simple.sh
```

This new patch:
- ✓ Creates automatic backup before applying
- ✓ Validates project structure first
- ✓ Runs a dry-run to check compatibility
- ✓ Shows clear success/error messages
- ✓ Restores backup automatically if patch fails

---

## Common Errors & Solutions

### ❌ "patch: **** malformed patch"
**This means the old patch file is corrupted.**

**Fix:** Use the fresh patch instead:
```bash
bash deployment/20260701-phase-system-updates-patch/patch-simple.sh
```

---

### ❌ "Can't open patch file"
**The patch file path is wrong.**

**Fix 1:** Make sure you're in the right directory:
```bash
cd /home/tackjdgn/kai.tacklehubs.tech
ls deployment/20260701-phase-system-updates-patch/
```

You should see: `patch-simple.sh`, `phase-system-fresh.patch`, etc.

**Fix 2:** Use absolute path:
```bash
bash /home/tackjdgn/kai.tacklehubs.tech/deployment/20260701-phase-system-updates-patch/patch-simple.sh
```

---

### ❌ "artisan file not found"
**You're not in the right project directory.**

**Fix:** Find the correct path:
```bash
find ~ -name artisan -type f 2>/dev/null
```

Then run patch from that directory:
```bash
cd /path/from/find/output
bash deployment/20260701-phase-system-updates-patch/patch-simple.sh
```

---

### ❌ "Patch failed to apply cleanly"
**Some files have been modified locally.**

**Option 1:** Restore from backup and try again:
```bash
bash deployment/20260701-phase-system-updates-patch/patch-simple.sh
```
(It will auto-restore if it fails)

**Option 2:** Manual restore - look for backup directory:
```bash
ls -la storage/backups/
```

---

## Testing the Patch

After applying, verify it worked:

1. **Check files were updated:**
   ```bash
   ls -la app/Models/TicketPhase.php
   ```
   Should exist (new file)

2. **Check database:**
   ```bash
   php artisan migrate --force
   ```

3. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:cache
   ```

4. **Hard refresh browser:**
   Press `Ctrl+Shift+R` or `Cmd+Shift+R`

---

## If ALL ELSE FAILS

### Option 1: Restore from Backup
```bash
cd /home/tackjdgn/kai.tacklehubs.tech
LATEST_BACKUP=$(ls -t storage/backups/ | head -1)
cp -r storage/backups/$LATEST_BACKUP/app app
cp -r storage/backups/$LATEST_BACKUP/resources resources
php artisan cache:clear
```

### Option 2: Download Fresh Patch

Get the latest patch zip:
```
https://github.com/stephendweteh/kaiproperties/raw/main/deployment/20260701-phase-system-updates-patch.zip
```

Unzip and try again:
```bash
cd /home/tackjdgn/kai.tacklehubs.tech
unzip -o patch.zip
bash deployment/20260701-phase-system-updates-patch/patch-simple.sh
```

### Option 3: Contact Support
If you get stuck, share:
```bash
bash deployment/20260701-phase-system-updates-patch/patch-simple.sh 2>&1 | head -20
```

Include the exact error message.

---

## Success Criteria

After patch applies, you should see:

✓ New file: `app/Models/TicketPhase.php`
✓ New file: `app/Models/PhaseAttachment.php`
✓ New migrations in `database/migrations/`
✓ Status button clickable in ticket list
✓ "Manage Work Phases" section in ticket detail (for Ops Manager)
✓ Auto-refresh now 1 hour instead of 10 seconds

---

**Still need help?**
Check repo: https://github.com/stephendweteh/kaiproperties/tree/main/deployment/20260701-phase-system-updates-patch
