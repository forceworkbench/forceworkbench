#!/usr/bin/env bash

tail -F ${forceworkbench__logFile__default} &

while true; do
    $1 async_worker.php
done