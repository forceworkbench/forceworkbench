<?php

function redis() {
    $r = new Redis();
    $r->connect(parse_url($_ENV['REDISTOGO_URL'], PHP_URL_HOST), parse_url($_ENV['REDISTOGO_URL'], PHP_URL_PORT));
    if (!is_array(parse_url($_ENV['REDISTOGO_URL'], PHP_URL_PASS))) {
        $r->auth(parse_url($_ENV['REDISTOGO_URL'], PHP_URL_PASS));
    }
    return $r;
}

class TimeoutException extends Exception {

}

?>