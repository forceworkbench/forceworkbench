<?php

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
        redis()->setex(FUTURE_LOCK . $this->asyncId, 30 * 60, session_id());   // expiring existence handle
        redis()->rpush(self::QUEUE, serialize($this));                         // actual job
        workbenchLog(LOG_INFO, "FutureTaskEnqueue", $this->asyncId);
        return new FutureResult($this->asyncId);
    }

    abstract function perform();

    function execute() {
        workbenchLog(LOG_INFO, "FutureTaskExecuteStart", $this->asyncId);
        $future = new FutureResult($this->asyncId);
        try {
            WorkbenchContext::establish($this->connConfig);
            $future->redeem($this->perform());
            WorkbenchContext::get()->release();
        } catch (Exception $e) {
            $future->redeem($e);
        }
        workbenchLog(LOG_INFO, "FutureTaskExecuteEnd", $this->asyncId);
    }

    /**
     * @static
     * @return FutureTask
     */
    public static function dequeue($timeout) {
        $blpop = redis()->blpop(self::QUEUE, $timeout);
        if (isset($blpop[1])) {
            $task = unserialize($blpop[1]);

            if (!redis()->exists(FUTURE_LOCK . $task->asyncId)) {
                workbenchLog(LOG_INFO, "FutureTaskGC", $task->asyncId);
                throw new TimeoutException();
            }

            return $task;
        }

        throw new TimeoutException();
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
        // check lock is there and is for this session
        $sid = redis()->get(FUTURE_LOCK . $asyncId);
        if ($sid == null || $sid !== session_id()) {
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

        if (isset($blpop[1])) {
            $this->result = unserialize($blpop[1]);
        } else {
            throw new TimeoutException();
        }

        redis()->del(FUTURE_LOCK . $this->asyncId); // remove lock

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