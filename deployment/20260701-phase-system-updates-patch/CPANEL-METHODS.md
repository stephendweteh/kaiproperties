# cPanel Deployment Methods - Choose One

## 🟢 METHOD 1: Git Pull (Easiest - Recommended)

**Requirements:** SSH/Terminal access with git installed

**In cPanel Terminal:**
```bash
cd /path/to/kai-maintenance-system
git fetch origin
git pull origin main
php artisan cache:clear
php artisan config:cache
```

**Status:**
- ✓ Fully automatic
- ✓ Safest method
- ✓ Works with conflicts
- ✓ Creates automatic backups via git

---

## 🟡 METHOD 2: Simple Script

**Requirements:** SSH/Terminal access

**In cPanel Terminal:**
```bash
bash /path/to/deployment/20260701-phase-system-updates-patch/simple-apply.sh /path/to/kai-maintenance-system
```

Or if in project directory:
```bash
cd /path/to/kai-maintenance-system
bash ../../deployment/20260701-phase-system-updates-patch/simple-apply.sh
```

**Status:**
- ✓ Semi-automatic
- ✓ Better error handling
- ✓ Shows progress
- ✓ Requires git

---

## 🔴 METHOD 3: Manual Patch Apply

**Requirements:** SSH/Terminal access with git and patch command

**Step 1: Download patch**
```bash
cd /path/to/kai-maintenance-system
wget https://raw.githubusercontent.com/stephendweteh/kaiproperties/main/deployment/20260701-phase-system-updates-patch/phase-system-updates.patch
```

**Step 2: Apply patch**
```bash
git apply phase-system-updates.patch
```

**Step 3: Clean up and cache**
```bash
rm phase-system-updates.patch
php artisan cache:clear
php artisan config:cache
```

**Status:**
- ✗ More complex
- ✓ Works without git pull
- ✓ Manual control
- ⚠️ May have issues with conflicts

---

## ⚫ METHOD 4: No Terminal Access? Use cPanel File Manager

**Requirements:** cPanel File Manager access only

**Instructions:**

1. **Download files** from your local machine:
   - Go to: https://github.com/stephendweteh/kaiproperties
   - Navigate to: deployment/20260701-phase-system-updates-patch
   - Download: `phase-system-updates.patch`

2. **Upload to server** via cPanel File Manager:
   - Upload to project root: `/public_html/kai-maintenance-system/`

3. **Use Terminal** within cPanel to apply:
   ```bash
   cd /path/to/kai-maintenance-system
   git apply phase-system-updates.patch
   php artisan cache:clear
   php artisan config:cache
   ```

**Status:**
- ⚠️ Requires File Manager + Terminal
- ✓ No git pull needed
- ✓ Manual but straightforward

---

## ❓ WHICH METHOD TO USE?

| Method | Speed | Reliability | Ease | Recommended For |
|--------|-------|-------------|------|-----------------|
| Method 1 | ⚡⚡⚡ | ⭐⭐⭐ | ⭐⭐⭐ | **Everyone** |
| Method 2 | ⚡⚡ | ⭐⭐ | ⭐⭐ | Automation |
| Method 3 | ⚡ | ⭐⭐ | ⭐ | Advanced users |
| Method 4 | ⚡ | ⭐ | ⭐⭐ | No Terminal |

**👉 Start with Method 1 (git pull)**

---

## 🆘 TROUBLESHOOTING

### Error: "command not found: git"
- Git not installed on your hosting
- Contact your provider to enable git
- Try Method 4 instead

### Error: "Permission denied"
```bash
chmod -R 755 /path/to/kai-maintenance-system
```

### Error: "fatal: not a git repository"
- You're in wrong directory
- Make sure you cd to project root (where `artisan` file is)

### Error: "CONFLICT"
Run this to resolve:
```bash
cd /path/to/kai-maintenance-system
git reset --hard origin/main
git pull origin main
php artisan cache:clear
```

---

## ✅ VERIFY PATCH WAS APPLIED

After running any method above, verify:

```bash
git log -1 --oneline
```

Should show commit starting with `dd4b154` or later

---

## 🔙 ROLLBACK IF NEEDED

If something goes wrong:

```bash
cd /path/to/kai-maintenance-system
git reset --hard origin/main~1
php artisan cache:clear
```

Or restore from backup:
```bash
git reflog
git reset --hard <previous-commit-hash>
```

---

## ⚡ QUICK REFERENCE

**One-liner to copy and paste:**
```bash
cd /path/to/kai-maintenance-system && git pull origin main && php artisan cache:clear && php artisan config:cache && echo "✓ Done!"
```

**Don't forget to:**
1. Replace `/path/to/kai-maintenance-system` with your actual path
2. Hard refresh browser after (Ctrl+Shift+R or Cmd+Shift+R)
3. Check the browser console for any errors (F12)

