#!/usr/bin/env bash

MAX_WORKERS=${MAX_WORKERS:-1}

if [ $MAX_WORKERS -eq 0 ]; then
    echo "MAX_WORKERS of 0 not allowed"
    exit 1
fi

inf() {
    while true; do
        echo 0
    done
}

inf | xargs --max-args=1 --max-procs=$MAX_WORKERS php async_worker.php