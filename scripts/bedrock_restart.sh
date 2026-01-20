#!/bin/bash
# File: /home/minecraft/bedrock_restart.sh
LOG_FILE="/home/minecraft/minecraft.log"
SCREEN_NAME="bedrock"
SERVER_DIR="/home/minecraft/Server"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Restart Script Started" >> "$LOG_FILE"

# Kill any existing server
screen -S "$SCREEN_NAME" -X quit 2>/dev/null

# Start server in detached screen
screen -dmS "$SCREEN_NAME" bash -c "cd $SERVER_DIR && ./bedrock_server >> /home/minecraft/minecraft.log 2>&1"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Server Restarted" >> "$LOG_FILE"
