#!/bin/bash
set -e
ROOT_DIR="/home/minecraft"
SERVER_DIR="$ROOT_DIR/Server"
mkdir -p "$SERVER_DIR"
cd "$SERVER_DIR"
wget -O bedrock.zip "https://www.minecraft.net/bedrockdedicatedserver/bin-linux/bedrock-server-1.21.131.1.zip"
unzip -o bedrock.zip
rm -f bedrock.zip
echo "[$(date)] Installation completed in $SERVER_DIR"