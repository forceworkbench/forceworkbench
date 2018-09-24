#!/bin/bash

function fix_linux_internal_host() {
  DOCKER_INTERNAL_HOST="host.docker.internal"
  host $DOCKER_INTERNAL_HOST | grep "has address" > /dev/null 2>&1

  if [ $? -eq 1 ] ; then
    DOCKER_INTERNAL_IP=`/sbin/ip route | awk '/default/ { print $3 }' | awk '!seen[$0]++'`
    echo -e "$DOCKER_INTERNAL_IP\t$DOCKER_INTERNAL_HOST" | tee -a /etc/hosts > /dev/null
    echo "Added $DOCKER_INTERNAL_HOST to hosts /etc/hosts"
  fi
}

if [ -d .profile.d ]; then
  for i in .profile.d/*.sh; do
    if [ -r $i ]; then
      . $i
    fi
  done
  unset i
fi

fix_linux_internal_host