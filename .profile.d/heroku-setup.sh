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

# Apache configuration
# Modifications to conf/apache2/heroku.conf from the Heroku PHP buildpack
echo "ServerSignature Off" >> /app/vendor/heroku/heroku-buildpack-php/conf/apache2/heroku.conf
echo "ServerTokens Prod" >> /app/vendor/heroku/heroku-buildpack-php/conf/apache2/heroku.conf
#echo "HostnameLookups Off" >> /app/vendor/heroku/heroku-buildpack-php/conf/apache2/heroku.conf
#echo "TraceEnable Off" >> /app/vendor/heroku/heroku-buildpack-php/conf/apache2/heroku.conf
#echo "ServerLimit 32" >> /app/vendor/heroku/heroku-buildpack-php/conf/apache2/heroku.conf
#echo "MaxClients 32" >> /app/vendor/heroku/heroku-buildpack-php/conf/apache2/heroku.conf

# PHP configuration
# Modifications to conf/php/php.ini from the Heroku PHP buildpack
# sed -i 's/^expose_php = On/expose_php = Off/' /app/vendor/heroku/heroku-buildpack-php/conf/php/php.ini
#sed -i 's/^file_uploads = On/file_uploads = Off/' /app/vendor/heroku/heroku-buildpack-php/conf/php/php.ini
#sed -i 's/^short_open_tag = On/short_open_tag = Off/' /app/vendor/heroku/heroku-buildpack-php/conf/php/php.ini
#sed -i 's/^post_max_size = 8M/post_max_size = 2M/' /app/vendor/heroku/heroku-