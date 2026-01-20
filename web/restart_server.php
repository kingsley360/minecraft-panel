<?php
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Server Restart Started ===\n";

/* =====================
   CONFIG
===================== */
$screenName = "bedrock"; // screen name of server
$serverStartCmd = "/home/minecraft/Server/start.sh"; // adjust if needed

/* =====================
   STOP MINECRAFT SERVER
===================== */
echo "Stopping Minecraft server...\n";
exec("screen -S " . escapeshellarg($screenName) . " -X quit");
sleep(5);

/* =====================
   CLEAR SYSTEM CACHE
===================== */
echo "Clearing system cache...\n";
exec("sync");
exec("echo 3 | sudo tee /proc/sys/vm/drop_caches");

/* =====================
   RESTART MINECRAFT SERVER
===================== */
echo "Starting Minecraft server...\n";
exec("screen -dmS " . escapeshellarg($screenName) . " bash -c " . escapeshellarg($serverStartCmd));

echo "=== Restart Completed ===\n";
