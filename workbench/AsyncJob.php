<?php

class AsyncJob{

    const ASYNC_JOB_QUEUE = "ASYNC_JOB_QUEUE";

    private $asyncId;
    private $connConfig;

    function __construct() {
        $this->asyncId = uniqid();
        $this->connConfig = WorkbenchContext::get()->getConnConfig();
    }

    function getAsyncId() {
        return $this->asyncId;
    }

    function getConnConfig() {
        return $this->connConfig;
    }

    public function enqueue() {
        self::redis()->rpush(self::ASYNC_JOB_QUEUE, serialize($this));
        return $this->asyncId;
    }

    /**
     * @static
     * @return AsyncJob
     */
    public static function dequeue() {
        $blpop = self::redis()->blpop(self::ASYNC_JOB_QUEUE, 30);
        if (isset($blpop[1])) {
            return unserialize($blpop[1]);
        } else {
            return null;
        }
    }

    private static function redis() {
        $r = new Redis();
        $r->connect(parse_url($_ENV['REDISTOGO_URL'], PHP_URL_HOST), parse_url($_ENV['REDISTOGO_URL'], PHP_URL_PORT));
        if (!is_array(parse_url($_ENV['REDISTOGO_URL'], PHP_URL_PASS))) {
            $r->auth(parse_url($_ENV['REDISTOGO_URL'], PHP_URL_PASS));
        }
        return $r;
    }

}
?>
