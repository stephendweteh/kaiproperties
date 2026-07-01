# ⚡ QUICK CPANEL PATCH - Simple & Fast

## 🚀 One Command to Rule Them All

**Copy and paste this into your cPanel Terminal:**

```bash
cd /home/tackjdgn/kai.tacklehubs.tech && bash deployment/20260701-phase-system-updates-patch/patch-cpanel.sh
```

Replace `/home/tackjdgn/kai.tacklehubs.tech` with your actual project path.

---

## ✅ That's It!

The script will:
1. ✓ Find the patch files
2. ✓ Apply the patch
3. ✓ Clear cache
4. ✓ Show success message

---

## 📋 Step-by-Step

### Step 1: SSH to Your Server
```bash
ssh user@yourdomain.com
```

### Step 2: Navigate to Project
```bash
cd /home/tackjdgn/kai.tacklehubs.tech
```

### Step 3: Run the Patcher
```bash
bash deployment/20260701-phase-system-updates-patch/patch-cpanel.sh
```

### Step 4: Hard Refresh Browser
Press: `Ctrl+Shift+R` (or `Cmd+Shift+R` on Mac)

---

## 🆘 If It Still Fails

### Find Your Correct Path
```bash
pwd
```

This shows your current directory. Use that in the command.

### Or Try Direct PHP
```bash
php deployment/20260701-phase-system-updates-patch/patch.php /path/to/project
```

---

## ✓ What Gets Patched

- Status button is now clickable
- "Click to view" helper text added
- Auto-refresh changed to 1 hour
- Ops Manager can manage phases
- Proper authorization updates

---

## 🎯 Most Common Error & Fix

**Error:** "artisan file not found"
**Fix:** Make sure you're in the correct project directory
```bash
ls artisan  # Should show: artisan
```

If it shows "No such file", you're in the wrong directory. Use the `find` command:
```bash
find ~ -name artisan -type f 2>/dev/null
```

Then navigate there and run the patch script.

---

## 💡 Simplest Approach

1. Get the exact path where `artisan` file is
2. Navigate there
3. Run: `bash deployment/20260701-phase-system-updates-patch/patch-cpanel.sh`
4. Done!

---

## ⏱️ Time to Patch

- Download/extract: 1 minute
- Run patch: 30 seconds
- Browser refresh: 10 seconds
- **Total: ~2 minutes**

---

That's it! Simple and straightforward. 🎉

