#!/bin/bash
set -e

# ================= CONFIG =================
GITHUB_REPO="https://github.com/kingsley360/minecraft-panel"
PHP_VERSION="8.4"
IP=$(curl -4s https://api.ipify.org)

echo "============================================"
echo " Minecraft Panel Full Installer"
echo "============================================"

# ================= SYSTEM UPDATE =================
echo "[1/10] Updating system..."
apt update -y && apt upgrade -y

# ================= DEPENDENCIES =================
echo "[2/10] Installing dependencies..."
apt install -y lsb-release ca-certificates apt-transport-https software-properties-common gnupg2 curl sudo git screen ufw unzip tar wget

# Install Nginx or Apache if not found
if ! command -v nginx &>/dev/null; then
    echo "[2a/10] Installing Nginx..."
    apt install -y nginx
fi

if ! command -v apache2 &>/dev/null; then
    echo "[2b/10] Installing Apache..."
    apt install -y apache2
fi

# ================= PHP =================
echo "[3/10] Installing PHP $PHP_VERSION..."
if ! command -v php &>/dev/null; then
    curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/trusted.gpg.d/sury-php.gpg
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    apt update -y
    apt install -y php$PHP_VERSION php$PHP_VERSION-fpm php$PHP_VERSION-cli php$PHP_VERSION-curl php$PHP_VERSION-zip php$PHP_VERSION-mbstring
fi

# ================= USERS =================
echo "[4/10] Creating minecraft user..."
id minecraft &>/dev/null || useradd -m -s /bin/bash minecraft

# ================= PANEL =================
echo "[5/10] Installing panel..."
rm -rf /tmp/minecraft-panel
git clone "$GITHUB_REPO" /tmp/minecraft-panel

rm -rf /var/www/*
cp -r /tmp/minecraft-panel/web/* /var/www/
chown -R www-data:www-data /var/www
chmod -R 755 /var/www

# ================= CONFIG.PHP =================
echo "[6/10] Creating config.php..."
cat <<'EOF' > /var/www/config.php
<?php
$admin_user = "admin";
$admin_pass = "changeme";
$panel_version = "1.0";
$server_dir = "/home/minecraft/Server";
$backup_dir = "/home/minecraft/BackupWorlds";
EOF
chown www-data:www-data /var/www/config.php
chmod 644 /var/www/config.php

# ================= SCRIPTS =================
echo "[7/10] Installing server scripts..."
mkdir -p /home/minecraft/Server/worlds
mkdir -p /home/minecraft/BackupWorlds
cp -r /tmp/minecraft-panel/scripts/* /home/minecraft/

# ----- manage_screen.sh -----
cat <<'EOF' > /home/minecraft/manage_screen.sh
#!/bin/bash
SCREEN_NAME="bedrock"
SERVER_DIR="/home/minecraft/Server"
LOG_FILE="/home/minecraft/minecraft.log"

start(){
    status
    if [[ "$STATUS" == "Online" ]]; then stop; fi
    cd "$SERVER_DIR"
    screen -dmS "$SCREEN_NAME" bash -c "./bedrock_server >> $LOG_FILE 2>&1"
    echo "Server started"
}

stop(){
    status
    if [[ "$STATUS" == "Offline" ]]; then echo "Server not running"; return; fi
    for S in $(screen -ls | grep "\.$SCREEN_NAME" | awk '{print $1}'); do
        screen -S "$S" -X quit
    done
    echo "Server stopped"
}

restart(){ stop; sleep 1; start; }
status(){ if screen -ls | grep -q "\.$SCREEN_NAME"; then STATUS="Online"; else STATUS="Offline"; fi; echo "$STATUS"; }

case "$1" in
    start) start ;;
    stop) stop ;;
    restart) restart ;;
    status) status ;;
    *) echo "Usage: $0 {start|stop|restart|status}" ;;
esac
EOF

# ----- install/uninstall scripts -----
cp /tmp/minecraft-panel/scripts/install_Server.sh /home/minecraft/
cp /tmp/minecraft-panel/scripts/uninstall.sh /home/minecraft/

# Set ownership and permissions
chown -R minecraft:minecraft /home/minecraft
chmod +x /home/minecraft/*.sh

# Ensure log file exists
touch /home/minecraft/minecraft.log
chown minecraft:www-data /home/minecraft/minecraft.log
chmod 664 /home/minecraft/minecraft.log

# ================= SUDO =================
echo "[8/10] Setting sudo permissions..."
cat > /etc/sudoers.d/minecraft-panel <<EOF
www-data ALL=(minecraft) NOPASSWD: /home/minecraft/*.sh
EOF
chmod 440 /etc/sudoers.d/minecraft-panel

# ================= NGINX =================
echo "[9/10] Configuring Nginx..."
cp /tmp/minecraft-panel/nginx/minecraft-panel.conf /etc/nginx/sites-available/minecraft-panel
sed -i 's|root .*;|root /var/www;|' /etc/nginx/sites-available/minecraft-panel
ln -sf /etc/nginx/sites-available/minecraft-panel /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Large uploads
PHP_INI="/etc/php/$PHP_VERSION/fpm/php.ini"
PHP_POOL="/etc/php/$PHP_VERSION/fpm/pool.d/www.conf"
NGINX_CONF="/etc/nginx/nginx.conf"

sed -i 's/^upload_max_filesize.*/upload_max_filesize = 50G/' $PHP_INI || true
sed -i 's/^post_max_size.*/post_max_size = 50G/' $PHP_INI || true
sed -i 's/^memory_limit.*/memory_limit = 1024M/' $PHP_INI || true
sed -i 's/^max_execution_time.*/max_execution_time = 0/' $PHP_INI || true
sed -i 's/^max_input_time.*/max_input_time = 0/' $PHP_INI || true

systemctl restart php$PHP_VERSION-fpm
systemctl restart nginx

# ================= FIREWALL =================
echo "[10/10] Configuring firewall..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 19132/udp
ufw allow 24680/udp
ufw --force enable

# ================= PERMISSION FIX (ADDED) =================
echo "[FIX] Applying final permissions..."

chown -R www-data:www-data /home/minecraft
chmod +x /home/minecraft/manage_screen.sh

if [ -f /home/minecraft/Server/bedrock_server ]; then
    chmod +x /home/minecraft/Server/bedrock_server
fi

# Navigate to your server directory
cd /home/minecraft/Server

# Ensure the log file exists
touch minecraft.log

# Make it owned by the server user and group
chown minecraft:minecraft minecraft.log

# Give read/write permissions to owner, read for group/others
chmod 644 minecraft.log

# Ensure the folder itself is writable
chmod 755 /home/minecraft/Server

# ================= FINAL =================
echo ""
echo "============================================"
echo " ✅ INSTALL COMPLETE!"
echo "============================================"
echo " 🌍 Panel: http://$IP"
echo "============================================"
