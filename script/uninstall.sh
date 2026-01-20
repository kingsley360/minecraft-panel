#!/bin/bash
set -e
ROOT_DIR="/home/minecraft"
SERVER_DIR="$ROOT_DIR/Server"
LOG_FILE="$ROOT_DIR/minecraft.log"

echo "[$(date)] Uninstall started..." >> "$LOG_FILE"

# Stop server if running
screen -S bedrock -X quit || true
sleep 2

# Remove server files
rm -rf "$SERVER_DIR"

echo "[$(date)] Uninstall completed." >> "$LOG_FILE"