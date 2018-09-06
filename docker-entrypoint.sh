#!/bin/bash

function fix_linux_internal_host() {
  DOCKER_INTERNAL_HOST="host.docker.internal"
  ping -q -c1 $DOCKER_INTERNAL_HOST > /dev/null 2>&1

  if [ $? -ne 0 ] && ! grep $DOCKER_INTERNAL_HOST /etc/hosts > /dev/null ; then
    DOCKER_INTERNAL_IP=`/sbin/ip route | awk '/default/ { print $3 }' | awk '!seen[$0]++'`
    echo -e "$DOCKER_INTERNAL_IP\t$DOCKER_INTERNAL_HOST" | tee -a /etc/hosts > /dev/null
    echo "Added $DOCKER_INTERNAL_HOST to hosts /etc/hosts"
  fi
}

fix_linux_internal_host
vendor/bin/heroku-php-apache2 -F fpm_custom_local.conf -i local_php.ini workbench