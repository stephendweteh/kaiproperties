# 🚨 EMERGENCY SITE RECOVERY

## Your site is showing 403 error

Use this **ONE command** to restore:

```bash
cd /home/tackjdgn/kai.tacklehubs.tech
php deployment/20260701-full-recovery-patch/recovery.php
```

---

## ✨ What This Does

✓ **Full backup** - Saves complete site before recovery  
✓ **Fixes permissions** - 777 for storage, 755 for code  
✓ **Clears all caches** - Removes corrupted cache files  
✓ **Runs migrations** - Updates database schema  
✓ **Rebuilds config** - Regenerates Laravel config cache  
✓ **Applies full patch** - Updates all project files  
✓ **Auto-restores on fail** - Backup ready in storage/backups/  

---

## 📋 Expected Output

```
==========================================
  EMERGENCY SITE RECOVERY
==========================================

✓ Project found
  Creating full backup...
✓ Backup created
  Fixing file permissions...
✓ Permissions fixed
  Clearing caches...
✓ Caches cleared
  Running Laravel recovery...
✓ Laravel recovery complete
  
==========================================
  ✓ RECOVERY COMPLETE!
==========================================

Next: Hard refresh browser (Ctrl+Shift+R)
```

---

## After Recovery

1. **Hard refresh** browser: **Ctrl+Shift+R** (or **Cmd+Shift+R** on Mac)
2. Try accessing your site
3. Login and verify it works

---

## If 403 Still Shows

### Try these commands:

```bash
# Fix storage permissions
chmod -R 777 storage/

# Fix public permissions  
chmod 755 public/index.php
chmod 644 public/.htaccess

# Clear Laravel cache again
php artisan cache:clear
php artisan config:cache

# Check what's in public
ls -la public/
```

---

## Restore from Backup

If something goes wrong:

```bash
# List available backups
ls -la storage/backups/

# Restore latest backup
BACKUP=$(ls -t storage/backups/recovery-* | head -1)
cp -r $BACKUP/* .
php artisan cache:clear
php artisan config:cache
```

---

## Alternative Command (Bash)

If PHP doesn't work:

```bash
bash deployment/20260701-full-recovery-patch/recovery.sh
```

---

## What's Included

- `recovery.php` ⭐ (PHP-based - RECOMMENDED)
- `recovery.sh` (Bash-based alternative)  
- `20260701-full-recovery-patch.diff` (Complete patch file)

---

**USE THIS NOW:**
```bash
php deployment/20260701-full-recovery-patch/recovery.php
```

This will fix the 403 error!
