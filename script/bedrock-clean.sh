#!/bin/bash
# File: /home/minecraft/bedrock-clean.sh
LOG_FILE="/home/minecraft/minecraft.log"
SERVER_DIR="/home/minecraft/Server"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Clean Script Started" >> "$LOG_FILE"

# Remove temporary files, crash logs, and cache
find "$SERVER_DIR" -type f \( -name "*.log" -o -name "*.tmp" -o -name "*.lock" \) -delete

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Clean Script Completed" >> "$LOG_FILE"
