#!/bin/bash

docker run --rm --interactive --tty --volume $PWD:/app composer update --ignore-platform-reqs --no-scripts