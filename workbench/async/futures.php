<?php
include_once 'rc4.php';

define("FUTURE_LOCK", "FUTURE_LOCK");

function crypto_serialize($data) {
    return rc4(base64_encode(serialize($data)), WorkbenchConfig::get()->value("futureSecret"), true);
}

function crypto_unserialize($data) {
    return unserialize(base64_decode(rc4($data, WorkbenchConfig::get()->value("futureSecret"), false)));
}

abstract class FutureTask {

    const QUEUE = "FUTURE_TASK_REQUESTS";

    private $asyncId;
    private $connConfig;
    private $cookies;
    private $enqueueTime;

    function __construct() {
        $this->asyncId = uniqid();
        $this->connConfig = WorkbenchContext::get()->getConnConfig();
        $this->cookies = $_COOKIE;
    }

    public function enqueueAndGet($timeout) {
        $future = $this->enqueue();
        try {
            return $future->get($timeout);
        } catch (TimeoutException $e) {
            return $future->ajax();
        }
    }

    public function enqueue() {
        if (WorkbenchConfig::get()->isConfigured("blockFutureTaskEnqueue")) {
            throw new WorkbenchHandledException("Tasks are currently not being accepted. Try again in a few moments.");
        }

        $this->enqueueTime = time();
        WorkbenchContext::get()->getPartnerConnection()->getServerTimestamp(); // check user has active session before going into async land
        redis()->setex(FUTURE_LOCK . $this->asyncId, 30 * 60, crypto_serialize(session_id()));   // set an expiring lock on this async id so GC doesn't get it
        redis()->rpush(self::QUEUE, crypto_serialize($this));                         // place actual job on the queue
        workbenchLog(LOG_INFO, "FutureTaskEnqueue", get_class($this) . "-" . $this->asyncId);
        return new FutureResult($this->asyncId);
    }

    abstract function perform();

    function execute() {
        $execStartTime = time();
        $future = new FutureResult($this->asyncId);
        try {
            WorkbenchConfig::destroy(); // destroy the WorkbenchConfig, if one happens to exist
            $_COOKIE = $this->cookies;  // reestablish the user's cookies so they'll be picked up by new WorkbenchConfig, if required
            WorkbenchContext::establish($this->connConfig);

            workbenchLog(LOG_INFO, "FutureTaskExecuteStart", get_class($this) . "-" . $this->asyncId);

            $future->redeem($this->perform());
        } catch (Exception $e) {
            $future->redeem($e);
        }

        workbenchLog(LOG_INFO, "FutureTaskExecuteEnd",
            get_class($this) . "-" . $this->asyncId .
            " queueTime=" . ($execStartTime - $this->enqueueTime) .
            " execTime=" . (time() - $execStartTime));

        WorkbenchContext::get()->release();
        WorkbenchConfig::destroy();
        $_COOKIE = array();
    }

    /**
     * @static
     * @return FutureTask
     */
    public static function dequeue($timeout) {
        $blpop = redis()->blpop(self::QUEUE, $timeout);
        if (isset($blpop[1])) {
            $task = crypto_unserialize($blpop[1]);

            if (!redis()->exists(FUTURE_LOCK . $task->asyncId)) {
                workbenchLog(LOG_INFO, "FutureTaskGC", get_class($task) . "-" . $task->asyncId);
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
        $sid = crypto_unserialize(redis()->get(FUTURE_LOCK . $asyncId));
        if ($sid == null || $sid !== session_id()) {
            throw new UnknownAsyncIdException();
        }

        return new FutureResult($asyncId);
    }

    function redeem($result) {
        $this->result = $result;
        redis()->rpush(self::RESULT . $this->asyncId, crypto_serialize($this->result));
    }

    public function getAsyncId() {
        return $this->asyncId;
    }

    public function get($timeout) {
        $blpop = redis()->blpop(self::RESULT . $this->asyncId, $timeout);

        if (isset($blpop[1])) {
            $this->result = crypto_unserialize($blpop[1]);
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