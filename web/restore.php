<?php
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_implicit_flush(true);
ob_end_flush();

// ----------------------
// CONFIG
$uploadDir = __DIR__ . '/uploads/';
$backupDir = __DIR__ . '/backups/';
$worldsDir = '/home/minecraft/Server/worlds/';

// Create folders if missing
@mkdir($uploadDir, 0777, true);
@mkdir($backupDir, 0777, true);
@mkdir($worldsDir, 0777, true);

// ----------------------
// Find backup
$files = glob($uploadDir . '*.zip');
if (empty($files)) $files = glob($uploadDir . '*.tar.gz');
if (empty($files)) { echo "? No backup file found\n"; exit; }

$backupFile = $files[0];
$filename = basename($backupFile);

// Detect extension
if (str_ends_with($backupFile, '.tar.gz')) {
    $ext = 'tar.gz';
} elseif (str_ends_with($backupFile, '.zip')) {
    $ext = 'zip';
} else {
    echo "? Unsupported file type\n"; exit;
}

// ----------------------
// Extract the archive temporarily to detect inner folder name
$tmpExtract = $worldsDir . '__tmp_restore__/';
@mkdir($tmpExtract, 0777, true);

echo "Extracting archive temporarily to detect inner folder...\n"; flush();
if ($ext === 'zip') {
    exec("unzip -o " . escapeshellarg($backupFile) . " -d " . escapeshellarg($tmpExtract) . " 2>&1", $output, $ret);
} else {
    exec("tar -xzf " . escapeshellarg($backupFile) . " -C " . escapeshellarg($tmpExtract) . " 2>&1", $output, $ret);
}
foreach ($output as $line){ echo $line . "\n"; flush(); }
if ($ret !== 0){ echo "? Extraction failed\n"; exit; }

// Detect the first folder inside the archive
$contents = glob($tmpExtract . '*');
$innerFolder = '';
foreach ($contents as $item) {
    if (is_dir($item)) {
        $innerFolder = basename($item);
        break;
    }
}
if (!$innerFolder) { echo "? Could not find folder inside archive\n"; exit; }

$targetPath = $worldsDir . $innerFolder . '/';

// ----------------------
// Backup current world if exists
$time = date('Y-m-d_H-i-s');
if (is_dir($targetPath) && count(scandir($targetPath)) > 2) {
    $backupWorlds = $backupDir . $innerFolder . "_backup_$time.tar.gz";
    echo "Backing up existing '$innerFolder' to $backupWorlds ...\n"; flush();
    exec("tar -czf " . escapeshellarg($backupWorlds) . " -C " . escapeshellarg($worldsDir) . " " . escapeshellarg($innerFolder) . " 2>&1", $output, $ret);
    foreach ($output as $line){ echo $line . "\n"; flush(); }
    echo ($ret === 0 ? "? Backup complete\n" : "? Backup failed\n"); flush();

    // Delete old folder
    echo "Deleting old folder '$innerFolder'...\n"; flush();
    exec("rm -rf " . escapeshellarg($targetPath));
}

// ----------------------
// Move extracted folder to worlds
rename($tmpExtract . $innerFolder, $targetPath);

// Remove temporary folder
exec("rm -rf " . escapeshellarg($tmpExtract));

echo "? Extraction complete\n";
echo "Restore finished! '$innerFolder' replaced successfully.\n";
flush();

/* ----------------------
   CLEAN UP UPLOADS FOLDER
---------------------- */
echo "Cleaning uploads folder...\n"; flush();

$uploadFiles = glob($uploadDir . '*');

foreach ($uploadFiles as $file) {
    if (is_file($file)) {
        unlink($file);
    } elseif (is_dir($file)) {
        exec("rm -rf " . escapeshellarg($file));
    }
}

echo "Uploads folder cleaned successfully.\n";
flush();
