#!/usr/bin/env bash

if [ -n "${forceworkbench__logFile__default}" ]; then
    touch "${forceworkbench__logFile__default}"
    tail -F "${forceworkbench__logFile__default}" &
fi

while true; do
    $1 async_worker.php
done