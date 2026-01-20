<?php
ignore_user_abort(true);
set_time_limit(0);

file_put_contents("status.txt", "Starting restore...\n");

$zipFile = "uploads/world.zip";
$worldDir = "/root/minecraft/world"; // CHANGE THIS

if (!file_exists($zipFile)) {
    file_put_contents("status.txt", "? ZIP not found\n", FILE_APPEND);
    exit;
}

file_put_contents("status.txt", "Deleting old world...\n", FILE_APPEND);
shell_exec("rm -rf " . escapeshellarg($worldDir));

file_put_contents("status.txt", "Extracting new world...\n", FILE_APPEND);
shell_exec("unzip -o " . escapeshellarg($zipFile) . " -d " . escapeshellarg($worldDir));

file_put_contents("status.txt", "DONE ? Restore finished\n", FILE_APPEND);
echo "Restore started in background...";
