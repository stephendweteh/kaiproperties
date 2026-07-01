#!/usr/bin/env php
<?php
/**
 * Ticket System Update - PHP Direct File Downloader
 * Most reliable method - downloads files directly from GitHub
 * Usage: php apply-direct.php [/path/to/project]
 */

set_error_handler(fn($e, $m) => throw new ErrorException($m));

$projectRoot = $ARGV[1] ?? getcwd();
$timestamp = date('YmdHis');
$backupDir = "$projectRoot/storage/backups/ticket-updates-$timestamp";

echo "\n";
echo "==========================================\n";
echo "  Ticket System Update - PHP Method\n";
echo "==========================================\n";
echo "\n";

// Validate project
if (!file_exists("$projectRoot/artisan")) {
    die("❌ ERROR: artisan file not found in $projectRoot\n\n");
}

echo "✓ Project found at: $projectRoot\n";

// Create backup
echo "  Creating backup...\n";
@mkdir("$backupDir/app", 0755, true);
@mkdir("$backupDir/resources", 0755, true);
@mkdir("$backupDir/database", 0755, true);

copy_recursive("$projectRoot/app", "$backupDir/app");
copy_recursive("$projectRoot/resources", "$backupDir/resources");
copy_recursive("$projectRoot/database", "$backupDir/database");

echo "✓ Backup created: $backupDir\n";
echo "\n";

$files = [
    "app/Http/Controllers/Web/TicketController.php" => 1,
    "app/Models/TicketPhase.php" => 1,
    "app/Models/PhaseAttachment.php" => 1,
    "resources/views/tickets/index.blade.php" => 1,
    "resources/views/tickets/show.blade.php" => 1,
    "resources/views/tickets/partials/technician-form.blade.php" => 0,
    "database/migrations/2026_07_01_000000_create_ticket_phases_table.php" => 1,
    "database/migrations/2026_07_01_000001_create_phase_attachments_table.php" => 1,
    "public/css/site.css" => 1,
];

echo "  Downloading files from GitHub...\n";
$success = 0;
$failed = 0;

$baseUrl = "https://raw.githubusercontent.com/stephendweteh/kaiproperties/128c595/";

foreach ($files as $file => $required) {
    $url = $baseUrl . $file;
    $localPath = "$projectRoot/$file";
    
    // Create directory if needed
    $dir = dirname($localPath);
    @mkdir($dir, 0755, true);
    
    // Download file
    $content = @file_get_contents($url);
    
    if ($content === false) {
        if ($required) {
            echo "    ❌ $file (FAILED)\n";
            $failed++;
        } else {
            echo "    ⚠ $file (skipped)\n";
        }
    } else {
        if (file_put_contents($localPath, $content) !== false) {
            echo "    ✓ $file\n";
            $success++;
        } else {
            echo "    ❌ $file (write failed)\n";
            $failed++;
        }
    }
}

echo "\n";
echo "  Summary: $success files updated, $failed issues\n";
echo "\n";

// Run migrations
echo "  Running database migrations...\n";
chdir($projectRoot);
exec("php artisan migrate --force 2>/dev/null", $output);
echo "✓ Migrations complete\n";
echo "\n";

// Clear cache
echo "  Clearing cache...\n";
exec("php artisan cache:clear 2>/dev/null", $output);
exec("php artisan config:cache 2>/dev/null", $output);
echo "✓ Cache cleared\n";
echo "\n";

echo "==========================================\n";
echo "  ✓ TICKET SYSTEM UPDATED!\n";
echo "==========================================\n";
echo "\n";
echo "Updated $success files successfully.\n";
echo "\n";
echo "Next: Hard refresh browser (Ctrl+Shift+R)\n";
echo "\n";
echo "Backup: $backupDir\n";
echo "Restore: cp -r $backupDir/* .\n";
echo "\n";

function copy_recursive($source, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $dir = opendir($source);
    while (false !== ($file = readdir($dir))) {
        if ($file != '.' && $file != '..') {
            $srcFile = "$source/$file";
            $dstFile = "$dest/$file";
            
            if (is_dir($srcFile)) {
                copy_recursive($srcFile, $dstFile);
            } else {
                @copy($srcFile, $dstFile);
            }
        }
    }
    closedir($dir);
}
?>
