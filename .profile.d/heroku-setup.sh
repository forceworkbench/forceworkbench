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

if [ "$forceworkbench__logHandler__default" = "file" ] && [ "$forceworkbench__logFile__default" = "/app/target/app.log" ]; then
  echo "Removing deprecated use of log handler: /app/target/app.log"
  echo "Run 'heroku config:unset forceworkbench__logHandler__default forceworkbench__logFile__default' to fix"
  unset forceworkbench__logHandler__default
  unset forceworkbench__logFile__default
fi