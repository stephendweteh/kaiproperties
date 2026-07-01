#!/usr/bin/env php
<?php
/**
 * FULL PROJECT DEPLOYMENT - PHP Version
 * Extracts and overwrites all project files
 * Usage: php deploy.php [/path/to/project]
 */

set_error_handler(fn($e, $m) => throw new ErrorException($m));

$projectRoot = $ARGV[1] ?? getcwd();
$timestamp = date('YmdHis');
$backupDir = "$projectRoot/storage/backups/deployment-$timestamp";

echo "\n";
echo "==========================================\n";
echo "  FULL PROJECT DEPLOYMENT - PHP\n";
echo "==========================================\n";
echo "\n";

// Validate project
if (!file_exists("$projectRoot/artisan")) {
    die("❌ ERROR: artisan file not found\n");
}

echo "✓ Project found\n";
chdir($projectRoot);

// Create backup
echo "  Creating backup...\n";
@mkdir("$backupDir/app", 0755, true);
@mkdir("$backupDir/resources", 0755, true);
@mkdir("$backupDir/database", 0755, true);

copy_recursive("app", "$backupDir/app");
copy_recursive("resources", "$backupDir/resources");
copy_recursive("database", "$backupDir/database");

if (file_exists(".env")) {
    @copy(".env", "$backupDir/.env");
}

echo "✓ Backup created: $backupDir\n";
echo "\n";

// Extract deployment archive
echo "  Extracting deployment files...\n";
$deployFile = dirname(__FILE__) . "/kai-built-project.tar.gz";

if (!file_exists($deployFile)) {
    die("❌ ERROR: Deployment file not found: $deployFile\n");
}

$tmpExtract = "/tmp/kai-deploy-$timestamp";
@mkdir($tmpExtract);

// Extract tar.gz
$cmd = "tar -xzf '$deployFile' -C '$tmpExtract' 2>/dev/null";
exec($cmd, $output, $returnCode);

if ($returnCode !== 0) {
    die("❌ ERROR: Failed to extract deployment file\n");
}

echo "✓ Files extracted\n";
echo "\n";

// Copy files
echo "  Installing files...\n";

$dirs = ['app', 'resources', 'routes', 'bootstrap', 'database', 'config', 'public'];
$count = 0;

foreach ($dirs as $dir) {
    $src = "$tmpExtract/$dir";
    $dst = "$projectRoot/$dir";
    
    if (is_dir($src)) {
        if ($dir === 'public') {
            // Preserve storage symlink for public
            if (is_link("$dst/storage")) {
                $storageLink = readlink("$dst/storage");
            }
        }
        
        if ($dir === 'database' || $dir === 'config') {
            // Merge these directories instead of replacing
            copy_recursive_merge($src, $dst);
        } else {
            // Replace other directories
            remove_dir($dst);
            copy_recursive($src, $dst);
        }
        
        // Restore symlinks
        if ($dir === 'public' && isset($storageLink)) {
            if (!is_link("$dst/storage")) {
                @symlink($storageLink, "$dst/storage");
            }
        }
        
        echo "    ✓ $dir/\n";
        $count++;
    }
}

// Handle vendor separately (it's large)
if (is_dir("$tmpExtract/vendor")) {
    echo "    Installing vendor packages...\n";
    remove_dir("$projectRoot/vendor");
    copy_recursive("$tmpExtract/vendor", "$projectRoot/vendor");
    echo "    ✓ vendor/\n";
    $count++;
}

echo "\n";

// Fix permissions
echo "  Setting permissions...\n";
set_permissions("app", 0755);
set_permissions("resources", 0755);
set_permissions("routes", 0755);
set_permissions("config", 0755);
set_permissions("database", 0755);
set_permissions("public", 0755);
set_permissions("bootstrap", 0755);
set_permissions("storage", 0777);
@chmod("artisan", 0755);
@chmod("public/index.php", 0755);
echo "✓ Permissions set\n";
echo "\n";

// Clear caches
echo "  Clearing caches...\n";
clear_dir("bootstrap/cache");
clear_dir("storage/framework/cache");
clear_dir("storage/framework/views");
@mkdir("bootstrap/cache", 0777, true);
@mkdir("storage/framework/cache", 0777, true);
@mkdir("storage/framework/views", 0777, true);
@mkdir("storage/logs", 0777, true);
echo "✓ Caches cleared\n";
echo "\n";

// Run Laravel commands
echo "  Running Laravel optimization...\n";
$commands = [
    "php artisan config:clear 2>/dev/null",
    "php artisan cache:clear 2>/dev/null",
    "php artisan view:clear 2>/dev/null",
    "php artisan migrate --force 2>/dev/null",
    "php artisan config:cache 2>/dev/null",
];

foreach ($commands as $cmd) {
    exec($cmd, $output);
}

echo "✓ Laravel optimization complete\n";
echo "\n";

// Cleanup
remove_dir($tmpExtract);

echo "==========================================\n";
echo "  ✓ DEPLOYMENT COMPLETE!\n";
echo "==========================================\n";
echo "\n";
echo "Deployed: $count major components\n";
echo "Backup: $backupDir\n";
echo "\n";
echo "Next: Hard refresh browser (Ctrl+Shift+R)\n";
echo "\n";

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

function copy_recursive_merge($source, $dest) {
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
            @mkdir($dst, 0755, true);
            copy_recursive_merge($src, $dst);
        } else {
            @copy($src, $dst);
        }
    }
}

function remove_dir($dir) {
    if (!is_dir($dir)) return;
    
    $files = @scandir($dir);
    if (!$files) return;
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $path = "$dir/$file";
            if (is_dir($path)) {
                remove_dir($path);
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }
}

function set_permissions($path, $mode) {
    if (!is_dir($path)) return;
    
    @chmod($path, $mode);
    $files = @scandir($path);
    if (!$files) return;
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            set_permissions("$path/$file", $mode);
        }
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
