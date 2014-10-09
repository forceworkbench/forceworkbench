for fifo in /app/apache/logs/error_log /app/apache/logs/access_log /app/target/app.log; do
    mkdir -p $(dirname $fifo)
    mkfifo $fifo
    tail -n 0 -F $fifo &
done

vendor/bin/heroku-php-apache2 workbench