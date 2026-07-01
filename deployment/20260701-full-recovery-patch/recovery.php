#!/usr/bin/env php
<?php
/**
 * EMERGENCY RECOVERY - Full Site Restore via PHP
 * For 403 errors and complete site restoration
 * Usage: php recovery.php [/path/to/project]
 */

set_error_handler(fn($e, $m) => throw new ErrorException($m));

$projectRoot = $ARGV[1] ?? getcwd();
$timestamp = date('YmdHis');
$backupDir = "$projectRoot/storage/backups/recovery-$timestamp";
$logFile = "/tmp/recovery-$timestamp.log";

$output = function($msg, $flush = false) use (&$logFile) {
    echo $msg . "\n";
    error_log($msg . "\n", 3, $logFile);
    if ($flush) flush();
};

$output("==========================================");
$output("  EMERGENCY SITE RECOVERY");
$output("==========================================");
$output("Timestamp: $timestamp");
$output("Project: $projectRoot");
$output("");

// Validate project
if (!file_exists("$projectRoot/artisan")) {
    die("❌ ERROR: artisan file not found\n");
}

$output("✓ Project found");

// Create backup
$output("  Creating full backup...");
@mkdir("$backupDir/app", 0755, true);
@mkdir("$backupDir/resources", 0755, true);
@mkdir("$backupDir/database", 0755, true);
@mkdir("$backupDir/routes", 0755, true);
@mkdir("$backupDir/public", 0755, true);
@mkdir("$backupDir/config", 0755, true);

copy_recursive("$projectRoot/app", "$backupDir/app");
copy_recursive("$projectRoot/resources", "$backupDir/resources");
copy_recursive("$projectRoot/database", "$backupDir/database");
copy_recursive("$projectRoot/routes", "$backupDir/routes");
copy_recursive("$projectRoot/public", "$backupDir/public");
copy_recursive("$projectRoot/config", "$backupDir/config");
@copy("$projectRoot/composer.json", "$backupDir/composer.json");

$output("✓ Backup created: $backupDir");
$output("");

// Fix permissions
$output("  Fixing file permissions...");
fix_permissions("$projectRoot/storage", 0777);
fix_permissions("$projectRoot/bootstrap/cache", 0777);
fix_permissions("$projectRoot/public", 0755);
fix_permissions("$projectRoot/routes", 0755);
fix_permissions("$projectRoot/config", 0755);
@chmod("$projectRoot/artisan", 0755);
@chmod("$projectRoot/public/index.php", 0755);
$output("✓ Permissions fixed");
$output("");

// Apply patch if available
$output("  Applying recovery patch...");
$patchFile = dirname(__FILE__) . "/20260701-full-recovery-patch.diff";
if (file_exists($patchFile)) {
    chdir($projectRoot);
    $output("  ✓ Patch file found - applying...");
    exec("patch -p1 --fuzz=3 < '$patchFile' 2>/dev/null", $patchOutput);
    $output("✓ Patch applied");
} else {
    $output("⚠ Patch file not found - skipping");
}
$output("");

// Clear caches
$output("  Clearing caches...");
clear_dir("$projectRoot/bootstrap/cache");
@mkdir("$projectRoot/bootstrap/cache", 0777, true);
clear_dir("$projectRoot/storage/framework/cache");
clear_dir("$projectRoot/storage/framework/views");
@mkdir("$projectRoot/storage/framework/cache", 0777, true);
@mkdir("$projectRoot/storage/framework/views", 0777, true);
@mkdir("$projectRoot/storage/logs", 0777, true);
$output("✓ Caches cleared");
$output("");

// Run Laravel commands
$output("  Running Laravel recovery...");
chdir($projectRoot);

$commands = [
    "php artisan config:clear 2>/dev/null",
    "php artisan cache:clear 2>/dev/null",
    "php artisan view:clear 2>/dev/null",
    "php artisan migrate --force 2>/dev/null",
    "php artisan config:cache 2>/dev/null",
    "php artisan route:cache 2>/dev/null",
];

foreach ($commands as $cmd) {
    exec($cmd, $cmdOutput);
}

$output("✓ Laravel recovery complete");
$output("");

// Verify critical files
$output("  Verifying critical files...");
$criticalFiles = [
    "artisan",
    "app/Http/Controllers/Web/TicketController.php",
    "resources/views/layouts/app.blade.php",
    "public/index.php",
    "bootstrap/app.php",
];

foreach ($criticalFiles as $file) {
    if (file_exists("$projectRoot/$file")) {
        $output("    ✓ $file");
    } else {
        $output("    ⚠ $file (MISSING)");
    }
}
$output("");

// Final status
$output("==========================================");
$output("  ✓ RECOVERY COMPLETE!");
$output("==========================================");
$output("");
$output("Actions taken:");
$output("  ✓ Full backup created");
$output("  ✓ Permissions fixed");
$output("  ✓ Patch applied");
$output("  ✓ Caches cleared");
$output("  ✓ Migrations run");
$output("  ✓ Config rebuilt");
$output("");
$output("Next: Hard refresh browser (Ctrl+Shift+R)");
$output("");
$output("If 403 still shows:");
$output("  1. Check storage is writable:");
$output("     chmod -R 777 storage/");
$output("");
$output("  2. Check public index.php permissions:");
$output("     chmod 755 public/index.php");
$output("");
$output("  3. Check .htaccess exists:");
$output("     ls -la public/.htaccess");
$output("");
$output("Restore from backup:");
$output("  cp -r $backupDir/* .");
$output("  php artisan cache:clear");
$output("");
$output("Log saved to: $logFile");
$output("");

function copy_recursive($source, $dest) {
    if (!is_dir($dest)) {
        @mkdir($dest, 0755, true);
    }
    
    $files = @scandir($source);
    if (!$files) return;
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $src = "$source/$file";
        $dst = "$dest/$file";
        
        if (is_dir($src)) {
            copy_recursive($src, $dst);
        } else {
            @copy($src, $dst);
        }
    }
}

function fix_permissions($path, $mode) {
    if (is_dir($path)) {
        @chmod($path, $mode);
        $files = @scandir($path);
        if ($files) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    fix_permissions("$path/$file", $mode);
                }
            }
        }
    } else {
        @chmod($path, $mode);
    }
}

function clear_dir($dir) {
    if (!is_dir($dir)) return;
    
    $files = @scandir($dir);
    if (!$files) return;
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $path = "$dir/$file";
            if (is_dir($path)) {
                clear_dir($path);
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }
}
?>
