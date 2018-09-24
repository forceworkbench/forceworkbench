#!/bin/bash

SCRIPT=$(readlink -f "$0")
SCRIPTPATH=$(dirname "$SCRIPT")

source $SCRIPTPATH/common.sh
vendor/bin/heroku-php-apache2 -F fpm_custom.conf -i /local-development/local_php.ini workbench
