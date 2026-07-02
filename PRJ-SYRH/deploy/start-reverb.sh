#!/bin/bash
# Start Laravel Reverb WebSocket server
DIR="$(cd "$(dirname "$0")/../src/backend" && pwd)"
cd "$DIR"
nohup php artisan reverb:start --host=0.0.0.0 --port=8081 > "$DIR/storage/logs/reverb.log" 2>&1 &
echo "Reverb started (PID $!)"
