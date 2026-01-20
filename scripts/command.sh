#!/bin/bash

SESSION="bedrock"
COMMAND="$*"

if [ -z "$COMMAND" ]; then
  echo "Usage: ./command.sh <bedrock-command>"
  exit 1
fi

screen -S "$SESSION" -X stuff "$COMMAND$(printf '\r')"
