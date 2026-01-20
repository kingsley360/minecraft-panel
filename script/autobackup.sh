#!/bin/bash
set -e

echo "Backup Script Started"

ROOT_DIR="/home/minecraft"
SERVER_DIR="$ROOT_DIR/Server"
BACKUP_DIR="$ROOT_DIR/BackupWorlds"
WORLD_NAME="Bedrock level"
SCREEN_NAME="bedrock"  # your server screen name
LOG_FILE="$ROOT_DIR/backup.log"
DATE=$(date +"%F_%H-%M-%S")

mkdir -p "$BACKUP_DIR"

### Helper function to send commands to server ###
send_cmd() {
    local cmd="$1"
    screen -S "$SCREEN_NAME" -p 0 -X stuff "$cmd$(printf '\r')"
}

### Logging helper ###
log() {
    echo "[$(date)] $1" | tee -a "$LOG_FILE"
}

log "Backup Script Started"

# Ensure world resumes if script fails
cleanup() {
    send_cmd "save resume" || true
}
trap cleanup EXIT

# --- Notify players ---
send_cmd "tellraw @a {\"rawtext\":[{\"text\":\"[Backup] Running World Backup...\",\"color\":\"aqua\"}]}"
sleep 3
send_cmd "tellraw @a {\"rawtext\":[{\"text\":\"[Backup] Expect some lag...\",\"color\":\"red\"}]}"
sleep 2

# --- Freeze world saving ---
send_cmd "save hold"
sleep 15

# --- Create backup ---
BACKUP_FILE="$BACKUP_DIR/bedrock_world_$DATE.tar.gz"
tar \
  --sparse \
  --ignore-failed-read \
  --warning=no-file-changed \
  -czf "$BACKUP_FILE" \
  -C "$SERVER_DIR/worlds" "$WORLD_NAME"

# --- Resume saving ---
send_cmd "save resume"
send_cmd "tellraw @a {\"rawtext\":[{\"text\":\"[Backup] Backup Completed Successfully!\",\"color\":\"green\"}]}"

log "Backup Finished Safely: $BACKUP_FILE"

# --- Keep only the newest backup ---
ls -1t "$BACKUP_DIR"/bedrock_world_*.tar.gz | tail -n +2 | xargs -r rm
log "Old backups deleted. Only the latest backup remains."
