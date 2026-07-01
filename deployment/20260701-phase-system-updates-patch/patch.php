<?php
/**
 * Phase System Updates - No-Git Patcher
 * Works without git, patch command, or any external tools
 * Just upload and run: php patch.php
 */

$projectRoot = dirname(__FILE__, 4);
$basePath = __DIR__;

// Color codes for terminal output
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('RESET', "\033[0m");

echo GREEN . "========================================" . RESET . "\n";
echo GREEN . "Phase System Updates - Direct Patcher" . RESET . "\n";
echo GREEN . "========================================" . RESET . "\n\n";

// Verify project structure
if (!file_exists($projectRoot . '/artisan')) {
    echo RED . "ERROR: Could not find Laravel project\n";
    echo "Please ensure this script is in: deployment/20260701-phase-system-updates-patch/\n" . RESET;
    exit(1);
}

echo "Project found at: " . GREEN . $projectRoot . RESET . "\n\n";

// Create backup
$backupDir = $projectRoot . '/storage/backups/patch-' . date('Y-m-d-H-i-s');
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$files = [
    'app/Http/Controllers/Web/TicketController.php',
    'resources/views/tickets/index.blade.php',
    'resources/views/tickets/show.blade.php',
];

echo "Creating backup...\n";
foreach ($files as $file) {
    $source = $projectRoot . '/' . $file;
    $dest = $backupDir . '/' . basename($file);
    if (file_exists($source)) {
        copy($source, $dest);
        echo "  ✓ Backed up: $file\n";
    }
}
echo "\nBackup location: " . GREEN . $backupDir . RESET . "\n\n";

// Apply patches
$patches = [];

// Patch 1: Update TicketController.php - Authorization for Operations Manager
$patches[] = [
    'file' => 'app/Http/Controllers/Web/TicketController.php',
    'search' => "abort_unless(\$canEditTickets || \$canApproveTickets || \$canTechnicianUpdate, 403);\n\n        if (\$canTechnicianUpdate && ! \$canEditTickets && ! \$canApproveTickets) {",
    'replace' => "abort_unless(\$canEditTickets || \$canApproveTickets || \$canTechnicianUpdate || \$user->hasRole(User::ROLE_OPERATIONS_MANAGER), 403);\n\n        \$isOperationsManager = \$user->hasRole(User::ROLE_OPERATIONS_MANAGER);\n\n        if ((\$canTechnicianUpdate || \$isOperationsManager) && ! \$canEditTickets && ! \$canApproveTickets) {",
    'description' => 'TicketController.php - Add Operations Manager authorization'
];

// Patch 2: Update index view - isOperationsManager flag
$patches[] = [
    'file' => 'resources/views/tickets/index.blade.php',
    'search' => "            'canEditTickets' => \$canEditTickets,\n            'canCreateTickets' => \$canCreateTickets,\n            'reviewMode' => \$reviewMode,",
    'replace' => "            'canEditTickets' => \$canEditTickets,\n            'canCreateTickets' => \$canCreateTickets,\n            'reviewMode' => \$reviewMode,\n            'isOperationsManager' => \$user?->hasRole(User::ROLE_OPERATIONS_MANAGER) ?? false,",
    'description' => 'index.blade.php - Add isOperationsManager flag'
];

// Patch 3: Update show view - phases eager loading
$patches[] = [
    'file' => 'resources/views/tickets/show.blade.php',
    'search' => "        \$ticket->load([\n            'property',\n            'category',\n            'reporter:id,name',\n            'technician:id,name',\n            'attachments.uploader:id,name',\n        ]);",
    'replace' => "        \$ticket->load([\n            'property',\n            'category',\n            'reporter:id,name',\n            'technician:id,name',\n            'attachments.uploader:id,name',\n            'phases.attachments.uploader:id,name',\n        ]);",
    'description' => 'show.blade.php - Add phases eager loading'
];

// Patch 4: Update show view - isOperationsManager flag
$patches[] = [
    'file' => 'resources/views/tickets/show.blade.php',
    'search' => "            'canEditTickets' => \$this->canEditTickets(\$user),\n            'canApproveTickets' => \$canApproveTickets,\n            'canTechnicianUpdate' => \$this->canTechnicianUpdateStatus(\$user, \$ticket),",
    'replace' => "            'canEditTickets' => \$this->canEditTickets(\$user),\n            'canApproveTickets' => \$canApproveTickets,\n            'canTechnicianUpdate' => \$this->canTechnicianUpdateStatus(\$user, \$ticket),\n            'isOperationsManager' => \$user->hasRole(User::ROLE_OPERATIONS_MANAGER),",
    'description' => 'show.blade.php - Add isOperationsManager flag'
];

// Patch 5: Update redirect for Operations Manager
$patches[] = [
    'file' => 'app/Http/Controllers/Web/TicketController.php',
    'search' => "                \$ticket->save();\n\n                return redirect()\n                    ->route('tickets.edit', \$ticket)\n                    ->with('success', 'Phase saved successfully.');",
    'replace' => "                \$ticket->save();\n\n                \$redirectRoute = \$isOperationsManager ? route('tickets.show', \$ticket) : route('tickets.edit', \$ticket);\n                \n                return redirect(\$redirectRoute)\n                    ->with('success', 'Phase saved successfully.');",
    'description' => 'TicketController.php - Redirect Ops Manager to show view'
];

// Patch 6: Update auto-refresh interval
$patches[] = [
    'file' => 'resources/views/tickets/show.blade.php',
    'search' => "    <script>\n        (function () {\n            const refreshIntervalMs = 10000;\n\n            window.setInterval(function () {\n                window.location.reload();\n            }, refreshIntervalMs);\n        })();\n    </script>",
    'replace' => "    <script>\n        (function () {\n            const refreshIntervalMs = 3600000; // 1 hour\n\n            window.setInterval(function () {\n                window.location.reload();\n            }, refreshIntervalMs);\n        })();\n    </script>",
    'description' => 'show.blade.php - Change auto-refresh to 1 hour'
];

// Apply patches
$failed = 0;
$succeeded = 0;

echo "Applying patches...\n\n";

foreach ($patches as $patch) {
    $filePath = $projectRoot . '/' . $patch['file'];
    
    if (!file_exists($filePath)) {
        echo RED . "✗ File not found: {$patch['file']}" . RESET . "\n";
        $failed++;
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    if (strpos($content, $patch['search']) === false) {
        echo YELLOW . "⚠ Patch already applied or not found: {$patch['description']}" . RESET . "\n";
        continue;
    }
    
    $newContent = str_replace($patch['search'], $patch['replace'], $content);
    
    if (file_put_contents($filePath, $newContent)) {
        echo GREEN . "✓ {$patch['description']}" . RESET . "\n";
        $succeeded++;
    } else {
        echo RED . "✗ Failed to write: {$patch['file']}" . RESET . "\n";
        $failed++;
    }
}

// Additional view changes - Status clickable
echo "\nUpdating views...\n";

$indexFile = $projectRoot . '/resources/views/tickets/index.blade.php';
$indexContent = file_get_contents($indexFile);

$statusPattern = '/<td>\s*<a href=".*?route.*?tickets\.show.*?\$ticket.*?"[^>]*>\s*<span class="status-pill[^>]*>.*?<\/span>\s*<\/a>\s*<\/td>/s';
$statusReplacement = '                    <td>
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.2rem;">
                            <small style="font-size: 0.7rem; color: #666;">click to view</small>
                            <a href="{{ route(\'tickets.show\', $ticket) }}" style="text-decoration:none;">
                                <span class="status-pill status-{{ $ticket->status }}" data-ticket-status="{{ $ticket->id }}">{{ $statusLabels[$ticket->status] ?? str($ticket->status)->replace(\'_\', \' \') }}</span>
                            </a>
                        </div>
                    </td>';

if (preg_match($statusPattern, $indexContent)) {
    $newIndexContent = preg_replace($statusPattern, $statusReplacement, $indexContent, 1);
    if (file_put_contents($indexFile, $newIndexContent)) {
        echo GREEN . "✓ Status button updated - made clickable with helper text" . RESET . "\n";
        $succeeded++;
    } else {
        echo RED . "✗ Failed to update status button" . RESET . "\n";
        $failed++;
    }
} else {
    echo YELLOW . "⚠ Status button pattern already updated" . RESET . "\n";
}

// Clear Laravel cache
echo "\n" . "Clearing Laravel cache...\n";
$artisanPath = $projectRoot . '/artisan';
if (file_exists($artisanPath)) {
    shell_exec("php " . escapeshellarg($artisanPath) . " cache:clear 2>/dev/null");
    shell_exec("php " . escapeshellarg($artisanPath) . " config:cache 2>/dev/null");
    echo GREEN . "✓ Cache cleared" . RESET . "\n";
}

// Summary
echo "\n" . GREEN . "========================================" . RESET . "\n";
echo GREEN . "✓ PATCH APPLIED SUCCESSFULLY!" . RESET . "\n";
echo GREEN . "========================================" . RESET . "\n\n";

echo "Applied changes:\n";
echo "  • Status pill is now clickable\n";
echo "  • 'Click to view' helper text added\n";
echo "  • Auto-refresh changed from 10 seconds to 1 hour\n";
echo "  • Operations Manager can manage phases\n";
echo "  • Updated authorization in controller\n\n";

echo "Files modified: $succeeded\n";
if ($failed > 0) {
    echo RED . "Files failed: $failed" . RESET . "\n";
}

echo "\n" . "Backup location:\n";
echo "  " . GREEN . $backupDir . RESET . "\n\n";

echo "Next steps:\n";
echo "  1. Hard refresh browser: Ctrl+Shift+R (or Cmd+Shift+R)\n";
echo "  2. Test the clickable status button\n";
echo "  3. Verify Operations Manager sees 'Manage Work Phases'\n\n";

echo "Done! No git required.\n";
?>
