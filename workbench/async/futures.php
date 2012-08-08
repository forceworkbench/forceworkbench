<?php
include_once "redis.php";

const FUTURE_LOCK = "FUTURE_LOCK";

abstract class FutureTask {

    const QUEUE = "FUTURE_TASK_REQUESTS";

    private $asyncId;
    private $connConfig;

    function __construct() {
        $this->asyncId = uniqid();
        $this->connConfig = WorkbenchContext::get()->getConnConfig();
    }

    public function enqueue() {
        redis()->rpush(self::QUEUE, serialize($this));                         // actual job
        redis()->setex(FUTURE_LOCK . $this->asyncId, 30 * 60, session_id());   // expiring existence handle
        return new FutureResult($this->asyncId);
    }

    abstract function perform();

    function execute() {
        print "Execute asyncId " . $this->asyncId . " START" . "\n"; //TODO: log
        $future = new FutureResult($this->asyncId);
        try {
            WorkbenchContext::establish($this->connConfig);
            $future->redeem($this->perform());
            WorkbenchContext::get()->release();
        } catch (Exception $e) {
            $future->redeem($e);
        }
        print "Execute asyncId " . $this->asyncId . " END" . "\n"; //TODO: log
    }

    /**
     * @static
     * @return FutureTask
     */
    public static function dequeue($timeout) {
        $blpop = redis()->blpop(self::QUEUE, $timeout);
        if (isset($blpop[1])) {
            return unserialize($blpop[1]);
        } else {
            throw new TimeoutException();
        }
    }
}

class FutureResult {

    const RESULT = "FUTURE_RESULT";

    private $asyncId;
    private $result;

    function __construct($asyncId) {
        $this->asyncId = $asyncId;
    }

    /**
     * @static
     * @param $asyncId
     * @return FutureResult
     */
    public static function fromId($asyncId) {
        // check lock is there
        if (!redis()->exists(FUTURE_LOCK . $asyncId)) {
            throw new UnknownAsyncIdException();
        }

        return new FutureResult($asyncId);
    }

    function redeem($result) {
        $this->result = $result;
        redis()->rpush(self::RESULT . $this->asyncId, serialize($this->result));
    }

    public function getAsyncId() {
        return $this->asyncId;
    }

    public function get($timeout) {
        $blpop = redis()->blpop(self::RESULT . $this->asyncId, $timeout);
        redis()->del(FUTURE_LOCK . $this->asyncId); // remove lock

        if (isset($blpop[1])) {
            $this->result = unserialize($blpop[1]);
        } else {
            throw new TimeoutException();
        }

        if ($this->result instanceof Exception) {
            throw $this->result;
        }

        return $this->result;
    }

    public function ajax() {
        ob_start();
        require "future_ajax.js.php";
        futureAjax($this->asyncId);
        $ajax = ob_get_contents();
        ob_end_clean();
        return $ajax;
    }

}

class TimeoutException extends Exception {}
class UnknownAsyncIdException extends Exception {}
?>