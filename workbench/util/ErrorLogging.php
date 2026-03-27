<?php
if (getenv('SENTRY_DSN') !== false) {
    \Sentry\init(['dsn' => getenv('SENTRY_DSN')]);
}