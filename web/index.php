<?php
session_start();

/* ===== LOGIN PROTECTION ===== */
if (!isset($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit;
}

/* ===== CONFIG ===== */
$screen_name = "bedrock";$server_dir  = "/home/minecraft/Server";
$log_file    = "/home/minecraft/minecraft.log"; // combined mc + backup logs
$manage_script = "/home/minecraft/manage_screen.sh";

$gamerules = [
    "announceAdvancements","commandBlockOutput","disableElytraMovementCheck",
    "doDaylightCycle","doEntityDrops","doFireTick","doLimitedCrafting",
    "doMobLoot","doMobSpawning","doTileDrops","doWeatherCycle",
    "drowningDamage","fallDamage","fireDamage","keepInventory",
    "logAdminCommands","maxCommandChainLength","mobGriefing",
    "naturalRegeneration","pvp","sendCommandFeedback",
    "showCoordinates","showDeathMessages","spawnRadius",
    "tntExplodes","disableInsomnia"
];

$message = "";
$console_output = "";

/* ===== HELPERS ===== */
function run_script($script){
    $script = escapeshellcmd($script);
    exec("$script 2>&1", $output);
    return implode("\n", $output);
}

function screen_running(){
    global $manage_script;
    $status = run_script("$manage_script status");
    return trim($status) === "Online";
}

function screen_cmd($cmd){
    global $screen_name;

    // Check screen session exists
    exec("screen -ls | grep '\\.$screen_name'", $out);
    if(empty($out)) return false;

    // Escape quotes and other dangerous characters
    $cmd_escaped = str_replace(
        ['"', '`', '$', ';', '|', '&', '>', '<', '\\'],
        ['&quot;', '', '', '', '', '', '', '', ''],
        $cmd
    );

    // Force Minecraft chat only
    exec("screen -S $screen_name -p 0 -X stuff \"say $cmd_escaped\n\"");

    return true;
}

function get_uptime() {
    if (file_exists("/proc/uptime")) {
        $uptime = file_get_contents("/proc/uptime");
        $seconds = (int)explode(' ', $uptime)[0];
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return "{$days}d {$hours}h {$minutes}m {$secs}s";
    }
    return "N/A";
}

function get_cpu_load() {
    $load = sys_getloadavg(); // [1min, 5min, 15min]
    return "1m: {$load[0]}, 5m: {$load[1]}, 15m: {$load[2]}";
}

function get_memory_usage() {
    $mem = shell_exec("free -m");
    if ($mem) {
        preg_match_all('/\d+/', $mem, $matches);
        $total = $matches[0][0];
        $used  = $matches[0][1];
        $free  = $matches[0][2];
        return "{$used}MB / {$total}MB used ({$free}MB free)";
    }
    return "N/A";
}

function get_disk_usage($path = "/") {
    $total = disk_total_space($path);
    $free  = disk_free_space($path);
    $used  = $total - $free;
    $percent = round($used / $total * 100, 1);
    return format_bytes($used) . " / " . format_bytes($total) . " ({$percent}%)";
}

function format_bytes($bytes) {
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units)-1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes,2) . ' ' . $units[$i];
}

function get_ping($host = "127.0.0.1") {
    $ping = shell_exec("ping -c 1 $host");
    if (preg_match('/time=(\d+\.\d+) ms/', $ping, $matches)) {
        return $matches[1] . " ms";
    }
    return "N/A";
}


/* ===== LOG ACTION ===== */
function log_action($msg){
    global $log_file;
    $time = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$time] $msg\n", FILE_APPEND);
}

/* ===== ACTION HANDLER ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    switch($_POST["action"]) {
        case "start":
            $message = run_script("$manage_script start");
            log_action($message);
            break;
        case "stop":
            $message = run_script("$manage_script stop");
            log_action($message);
            break;
        case "restart":
            $message = run_script("$manage_script restart");
            log_action($message);
            break;
        case "clean":
            $message = run_script("/home/minecraft/bedrock-clean.sh");
            log_action($message);
            break;
        case "backup":
$message = run_script("/home/minecraft/autobackup.sh");
    //$backup_script = "/home/minecraft/autobackup.sh";
    // Run as minecraft user
    //$output = run_script("/home/minecraft/autobackup.sh");
    $message = "? Backup completed! Check console for details.";
    break;
case "clear_console":
    if(file_exists($log_file)){
        file_put_contents($log_file, ""); // Truncate the log
        $console_output = "";
        $message = "?? Console cleared";
    } else {
        $message = "? Log file not found";
    }
break;


	case "install":
    $install_script = "/home/minecraft/install_Server.sh";
    if (!file_exists($install_script)) {
        file_put_contents($install_script, <<<EOT
#!/bin/bash
set -e
ROOT_DIR="/home/minecraft"
SERVER_DIR="\$ROOT_DIR/Server"
mkdir -p "\$SERVER_DIR"
cd "\$SERVER_DIR"
wget -O bedrock.zip "https://www.minecraft.net/bedrockdedicatedserver/bin-linux/bedrock-server-1.21.131.1.zip"
unzip -o bedrock.zip
rm -f bedrock.zip
echo "[\$(date)] Installation completed in \$SERVER_DIR"
EOT
        );
        run_script("chmod +x $install_script && chown www-data:www-data $install_script");
    }
    $message = run_script("bash $install_script 2>&1");
    log_action($message);
    break;

case "uninstall":
    $uninstall_script = "/home/minecraft/uninstall.sh";
    if (!file_exists($uninstall_script)) {
        file_put_contents($uninstall_script, <<<EOT
#!/bin/bash
set -e
ROOT_DIR="/home/minecraft"
SERVER_DIR="\$ROOT_DIR/servertest"
LOG_FILE="\$ROOT_DIR/minecraft.log"

echo "[\$(date)] Uninstall started..." >> "\$LOG_FILE"

# Stop server if running
screen -S bedrock -X quit || true
sleep 2

# Remove server files
rm -rf "\$SERVER_DIR"

echo "[\$(date)] Uninstall completed." >> "\$LOG_FILE"
EOT
        );
        run_script("chmod +x $uninstall_script && chown www-data:www-data $uninstall_script");
    }
    // Run directly as www-data (no sudo)
    $message = run_script("bash $uninstall_script 2>&1");
    log_action($message);
    break;

case "reinstall":
    // Uninstall then install
    $message = run_script("bash /home/minecraft/uninstall.sh && bash /home/minecraft/install.sh 2>&1");
    log_action($message);
    break;
	


        case "command":
    if(screen_running($screen_name)){
        $msg = trim($_POST["command"]);
        if($msg !== ""){

            // Sanitize input
            $msg_safe = str_replace(
                ['"', '`', '$', ';', '|', '&', '>', '<', '\\'],
                ['&quot;', '', '', '', '', '', '', '', ''],
                $msg
            );

            // Send to Minecraft chat safely
            screen_cmd($msg_safe);

            // Log panel message
            $logLine = "[" . date("Y-m-d H:i:s") . "] [ADMIN] " . $msg_safe . PHP_EOL;
            file_put_contents("/home/minecraft/minecraft.log", $logLine, FILE_APPEND | LOCK_EX);

            $message = "?? Message sent & logged safely: " . htmlspecialchars($msg_safe);

        } else {
            $message = "? Message is empty";
        }
    } else {
        $message = "? Server is offline";
    }
break;
        case "gamerule":
            if(screen_running()){
                $rule  = $_POST["gamerule"];
                $value = $_POST["value"];
                screen_cmd("gamerule $rule $value");
                screen_cmd("say §b[Gamerule] §a$rule §7? §e$value");
                $message = "?? Gamerule updated: $rule ? $value";
            } else {
                $message = "? Server is offline";
            }
            break;
        case "console":
            if(file_exists($log_file)){
                $console_output = shell_exec("tail -n 100 $log_file");
            } else {
                $console_output = "Log file not found";
            }
            break;
    }
}

/* ===== Live console auto-refresh ===== */
if(isset($_GET['live'])){
    header('Content-Type: text/plain');

    $mc_log_file = "/home/minecraft/minecraft.log";
    $mc_log = file_exists($mc_log_file) 
        ? shell_exec("tail -n 50 " . escapeshellarg($mc_log_file)) 
        : "Minecraft log not found";

    echo "=== Live Console Log ===\n$mc_log";
    exit;
}
?>

<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* ===== SAFE BACKUP DOWNLOAD ===== */
$backup_dir = "/home/minecraft/BackupWorlds";

if (isset($_GET['download_backup']) && $_GET['download_backup'] === 'latest') {

    $files = glob($backup_dir . "/bedrock_world_*.tar.gz");
    if (!$files) {
        http_response_code(404);
        exit("No backup found");
    }

    rsort($files);
    $file = $files[0];

    if (!is_file($file) || !is_readable($file)) {
        http_response_code(404);
        exit("Backup not readable");
    }

    /* --- CRITICAL FIXES --- */
    while (ob_get_level()) {
        ob_end_clean();
    }

    ini_set('zlib.output_compression', 'Off');
    ini_set('output_buffering', 'Off');

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $fp = fopen($file, 'rb');
    fpassthru($fp);
    fclose($fp);

    exit; // ?? DO NOT REMOVE
}




?>


<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>Minecraft Panel</title>
<style>




body{margin:10px;font-family:Segoe UI,Arial;background:linear-gradient(135deg,#1f1c2c,#928dab);color:#fff}
header{text-align:center;padding:20px;font-size:20px;font-weight:bold;background:#5d3a9b}
main{max-width:900px;margin:20px auto;padding:20px;background:rgba(0,0,0,.7);border-radius:14px}
.section{margin-bottom:30px;border-bottom:1px solid #555;padding-bottom:20px}
h2{color:#00bcd4}
input, select, button{width:100%;margin:8px 0;padding:10px;border-radius:8px;border:none;font-size:15px;box-sizing:border-box}
button{background:#5d3a9b;color:#fff;cursor:pointer}
button:hover{background:#7c53c0}
.message{background:#00bcd4;color:#000;padding:10px;border-radius:8px;margin-bottom:15px}
pre{background:#111;padding:10px;border-radius:8px;max-height:300px;overflow:auto}
a{color:#00e5ff;text-decoration:none}
.backup-item{display:flex;justify-content:space-between;margin-bottom:8px}
.download-btn{background:#0f0;color:#000;padding:5px 10px;border-radius:5px;text-decoration:none}

.icon-frame {
    width:56px;
    height:56px;
    border:2px solid #00bcd4;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition: all 0.18s ease;
}

.icon-frame:hover {
    background: rgba(255,255,255,0.15);
    transform: scale(1.12);
    box-shadow: 0 0 10px rgba(255,255,255,0.6);
}

.top-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 22px;
    font-size:20px;
    font-weight:bold;
    background:#5d3a9b;
}

.logout-btn{
    width:38px;
    height:38px;
    border:2px solid #fff;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:0.2s;
}

.logout-btn:hover{
    background:rgba(255,255,255,0.15);
    transform:scale(1.1);
}

.download-btn{
    display:inline-block;
    background:#00e676;
    color:#000;
    padding:12px 18px;
    border-radius:10px;
    font-weight:bold;
    text-decoration:none;
    transition:0.2s;
}
.download-btn:hover{
    background:#1aff88;
    transform:scale(1.05);
}



</style>
<script>
function refreshConsole(){
    fetch('?live=1').then(res=>res.text()).then(txt=>{
        document.getElementById('console').textContent = txt;
    });
}
setInterval(refreshConsole,3000);
</script>


<script>
function downloadBackup(){
    const url = "?download_backup=latest";
    const xhr = new XMLHttpRequest();

    const box = document.getElementById("progress-box");
    const bar = document.getElementById("progress-bar");
    const text = document.getElementById("progress-text");

    box.style.display = "block";
    bar.style.width = "0%";
    text.textContent = "Starting...";

    xhr.open("GET", url, true);
    xhr.responseType = "blob";

    xhr.onprogress = function(e){
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            bar.style.width = percent + "%";
            text.textContent = percent + "%";
        }
    };

    xhr.onload = function(){
        if (xhr.status === 200) {
            bar.style.width = "100%";
            text.textContent = "Download complete";

            const blob = xhr.response;
            const link = document.createElement("a");
            link.href = window.URL.createObjectURL(blob);
            link.download = "bedrock_backup.tar.gz";
            link.click();
        } else {
            text.textContent = "Download failed";
        }
    };

    xhr.onerror = function(){
        text.textContent = "Error downloading backup";
    };

    xhr.send();
}
</script>


</head>
<body>
<header class="top-header">
    <span>Minecraft Panel</span>

    <a href="?logout=1" class="logout-btn" title="Logout">
        <img src="https://img.icons8.com/ios-filled/22/ffffff/logout-rounded.png">
    </a>
</header>

<main>

<div class="section">
<h2>Server Status</h2>
<p>Status: <?php echo screen_running($screen_name) ? '<span style="color:#0f0;">Online</span>' : '<span style="color:#f00;">Offline</span>'; ?></p>
</div>

<div class="section">
<h2>Server Controls</h2>

<div style="
    width:100%;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:28px;
    margin-top:15px;
">

    <!-- START -->
    <div class="icon-frame" onclick="document.getElementById('action-start').click();">
        <img src="https://img.icons8.com/ios-filled/36/ffffff/play.png" title="Start Server">
    </div>

    <!-- STOP -->
    <div class="icon-frame" onclick="document.getElementById('action-stop').click();">
        <img src="https://img.icons8.com/ios-filled/36/ffffff/stop.png" title="Stop Server">
    </div>

    <!-- RESTART -->
    <div class="icon-frame" onclick="document.getElementById('action-restart').click();">
        <img src="https://img.icons8.com/ios-filled/36/ffffff/refresh.png" title="Restart Server">
    </div>

</div>

<form method="post" style="display:none">
    <button id="action-start" name="action" value="start"></button>
    <button id="action-stop" name="action" value="stop"></button>
    <button id="action-restart" name="action" value="restart"></button>
</form>
</div>

<div class="section">
<h2>Other Services</h2>
<form method="post">
<button name="action" value="clean">Clean</button>
<button name="action" value="backup">Backup</button>
</form>
</div>


<!-- Hidden form buttons to trigger PHP -->
<form id="server-actions" method="post" style="display:none;">
    <button id="action-start" name="action" value="start"></button>
    <button id="action-stop" name="action" value="stop"></button>
    <button id="action-restart" name="action" value="restart"></button>
</form>

<div class="section">
<h2>Send Server Message</h2>
<form method="post">
<input name="command" placeholder="say Hello players">
<button name="action" value="command">Send</button>
</form>
</div>

<div class="section">
<h2>Gamerule Editor</h2>
<form method="post">
<select name="gamerule">
<?php foreach($gamerules as $g) echo "<option>$g</option>"; ?>
</select>
<input name="value" placeholder="true / false / number">
<button name="action" value="gamerule">Apply</button>
</form>
</div>

<div class="section">
<h2>Live Console</h2>
<form method="post" style="display:flex; gap:10px;">
    <button name="action" value="console">Refresh</button>
    <button name="action" value="clear_console">Clear Console</button>
</form>
<pre id="console">Loading...</pre>
</div>


<div class="section">
<h2>Download Backup</h2>

<div class="backup-item">
    <span>Latest Backup</span>
    <a href="#" class="download-btn" onclick="downloadBackup();return false;">
        Download
    </a>
</div>

<!-- Progress bar -->
<div id="progress-box" style="display:none;margin-top:10px;">
    <div style="background:#333;border-radius:6px;overflow:hidden;">
        <div id="progress-bar"
             style="width:0%;height:14px;background:#00e676;"></div>
    </div>
    <small id="progress-text">0%</small>
</div>

</div>

<div class="section">
<h2>System Status</h2>
<ul>
    <li><strong>Uptime:</strong> <?= get_uptime() ?></li>
    <li><strong>CPU Load:</strong> <?= get_cpu_load() ?></li>
    <li><strong>Memory Usage:</strong> <?= get_memory_usage() ?></li>
    <li><strong>Disk Usage:</strong> <?= get_disk_usage("/") ?></li>
    <li><strong>Ping:</strong> <?= get_ping("127.0.0.1") ?></li>
</ul>
</div>

<div class="section">
<h2>Server Address</h2>
<div style="display:flex;align-items:center;gap:8px;">
    <span id="server-address" style="background:#111;color:#0ff;padding:8px 12px;border-radius:8px;font-family:monospace;font-size:14px;">
        minecraft.erwan.sbs:24680
    </span>
    <img src="https://img.icons8.com/ios-filled/24/ffffff/copy.png" 
         alt="Copy" 
         style="cursor:pointer;" 
         title="Click to copy"
         onclick="copyServerAddress()" />
</div>
</div>

<script>
function copyServerAddress(){
    const text = document.getElementById('server-address').textContent.trim();
    // Create a temporary textarea
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    textarea.setSelectionRange(0, 99999); // for mobile
    document.execCommand('copy');
    document.body.removeChild(textarea);
}
</script>

<div class="section">
<h2>Server Management</h2>
<form method="post">
    <button name="action" value="install">Install Minecraft Server</button>
    <button name="action" value="uninstall">Uninstall Minecraft Server</button>
    <button name="action" value="reinstall">Reinstall Minecraft Server</button>
</form>
</div>


<!-- Button to open Restore Panel -->
<button onclick="openUploadRestore()">Upload & Restore</button>

<!-- Button to open server.properties -->
<button onclick="openServerProperties()">server.properties</button>

<script>
function openUploadRestore(){
  // Open UI in new tab
  window.open('ui.php', '_blank');
}

function openServerProperties(){
  // Open editor in new tab
  window.open('edit_server.php', '_blank');
}
</script>

</main>
</body>
</html>