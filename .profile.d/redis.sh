if [ -n "$REDISTOGO_URL" ]; then
  echo "Configuring RedisToGo"
  export forceworkbench__redisUrl__default="$REDISTOGO_URL"
  export forceworkbench__sessionStore__default="$REDISTOGO_URL"
fi

if [ -n "$REDIS_URL" ]; then
  echo "Configuring Redis"
  export forceworkbench__redisUrl__default="$REDIS_URL"
  export forceworkbench__sessionStore__default="$REDIS_URL"
fi
