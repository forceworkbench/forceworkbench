#!/bin/bash

# Modify your composer.json and then run this script from the top-level (e.g. ./scripts/update-composer-lock.sh)
# This calls out to the official composer docker image to run the update
# See https://github.com/docker-library/docs/blob/master/composer/README.md#php-extensions

APPPATH=$(pwd)

docker run --rm --interactive --tty --volume $APPPATH:/app composer update --ignore-platform-reqs --no-scripts