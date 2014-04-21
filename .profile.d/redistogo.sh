if [ -n "$REDISTOGO_URL" ]; then
  echo "Configuring RedisToGo"
  export forceworkbench__redisUrl__default="$REDISTOGO_URL"
  export forceworkbench__sessionStore__default="$REDISTOGO_URL"
fi
