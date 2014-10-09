web: tail -n 0 -F /app/target/app.log &; vendor/bin/heroku-php-apache2 workbench
worker: cd target/public_html && ./async_workers.sh