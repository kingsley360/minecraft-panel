#!/bin/bash
# Minecraft Screen Manager Script
# Place in /home/minecraft/manage_screen.sh
# Make it executable: chmod +x /home/minecraft/manage_screen.sh

SCREEN_NAME="bedrock"
SERVER_DIR="/home/minecraft/Server"
LOG_FILE="/home/minecraft/minecraft.log"

start(){
    status
    if [[ "$STATUS" == "Online" ]]; then
        echo "Server already running. Killing old session..."
        stop
    fi

    cd "$SERVER_DIR"
    screen -dmS "$SCREEN_NAME" bash -c "./bedrock_server >> $LOG_FILE 2>&1"
    echo "Server started"
}

stop(){
    status
    if [[ "$STATUS" == "Offline" ]]; then
        echo "Server not running"
        return
    fi
    for S in $(screen -ls | grep "\.$SCREEN_NAME" | awk '{print $1}'); do
        screen -S "$S" -X quit
    done
    echo "Server stopped"
}

restart(){
    stop
    sleep 1
    start
}

status(){
    if screen -ls | grep -q "\.$SCREEN_NAME"; then
        STATUS="Online"
    else
        STATUS="Offline"
    fi
    echo "$STATUS"
}

case "$1" in
    start) start ;;
    stop) stop ;;
    restart) restart ;;
    status) status ;;
    *) echo "Usage: $0 {start|stop|restart|status}" ;;
esac
