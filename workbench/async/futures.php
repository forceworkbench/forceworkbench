<?php

define("FUTURE_LOCK", "FUTURE_LOCK");

/**
 * A task intended to be performed asynchronously. Should be subclassed for particular tasks.
 */
abstract class FutureTask {

    const QUEUE = "FUTURE_TASK_REQUESTS";

    private $asyncId;
    private $connConfig;
    private $cookies;
    private $enqueueTime;

    function __construct() {
        $this->asyncId = uniqid();
        $this->requestId = isset($_SERVER['HTTP_X_REQUEST_ID']) ? $_SERVER['HTTP_X_REQUEST_ID'] : null;
        $this->connConfig = WorkbenchContext::get()->getConnConfig();
        $this->cookies = $_COOKIE;
    }

    /**
     * Convenience method that
     * enqueues for async processing if Redis is available;
     * otherwise performs sync processing.
     * Either way, callers should `echo` the return value back to the page.
     *
     * @return string
     */
    public function enqueueOrPerform() {
        if (hasRedis()) {
            $future = $this->enqueue();
            return $future->ajax();
        } else {
            return $this->perform();
        }
    }

    /**
     * Enqueue for async processing.
     * A FutureResult is returned immediately while processing continues asynchronously in the background.
     *
     * @return FutureResult
     * @throws WorkbenchHandledException
     */
    public function enqueue() {
        if (WorkbenchConfig::get()->isConfigured("blockFutureTaskEnqueue")) {
            throw new WorkbenchHandledException("Tasks are currently not being accepted. Try again in a few moments.");
        }

        $this->enqueueTime = time();
        WorkbenchContext::get()->getPartnerConnection()->getServerTimestamp();                                                                // check user has active session before going into async land
        redis()->setex(FUTURE_LOCK . $this->asyncId, WorkbenchConfig::get()->value('asyncTimeoutSeconds'), crypto_serialize(session_id()));   // set an expiring lock on this async id so GC doesn't get it
        $payload = crypto_serialize($this);
        redis()->rpush(self::QUEUE, $payload);                                                                                 // place actual job on the queue
        workbenchLog(LOG_INFO, "FutureTaskEnqueue", array(
            "async_id" =>  $this->asyncId,
            "source" => get_class($this),
            "measure.async.enqueue.size" => strlen($payload). "byte"));
        return new FutureResult($this->asyncId);
    }

    /**
     * Subclasses should override for whatever they need to perform.
     * This can be called for sync processing of task
     *
     * @abstract
     * @return mixed
     */
    abstract function perform();

    /**
     * Executes the task within the user's config and context.
     * The result or any thrown exception is redeemed to a FutureResult with the same async id.
     * Only to be called by CLI. Will error if called from a web process.
     */
    function execute() {
        verifyCallingFromCLI();

        $execStartTime = time();
        $future = new FutureResult($this->asyncId);
        try {
            WorkbenchConfig::destroy(); // destroy the WorkbenchConfig, if one happens to exist
            $_SERVER['HTTP_X_REQUEST_ID'] = $this->requestId; // reestablish the original requestId for logging
            $_COOKIE = $this->cookies;  // reestablish the user's cookies so they'll be picked up by new WorkbenchConfig, if required
            WorkbenchContext::establish($this->connConfig);
            WorkbenchContext::get()->agreeToTerms();

            workbenchLog(LOG_INFO, "FutureTaskExecuteStart", array(
                "async_id" =>  $this->asyncId,
                "source" => get_class($this),
                "measure.async.queue_time" => ($execStartTime - $this->enqueueTime) . "sec"));

            $future->redeem($this->perform());
        } catch (Exception $e) {
            $future->redeem($e);
        }

        workbenchLog(LOG_INFO, "FutureTaskExecuteEnd", array(
            "async_id" =>  $this->asyncId,
            "source" => get_class($this),
            "measure.async.exec_time" => (time() - $execStartTime) . "sec"));

        WorkbenchContext::get()->release();
        WorkbenchConfig::destroy();
        $_COOKIE = array();
    }

    /**
     * Dequeues the next task for processing. Blocks until $timeout is reached.
     * Only to be called by CLI. Will error if called from a web process.
     *
     * @static
     * @return FutureTask
     */
    public static function dequeue($timeout) {
        verifyCallingFromCLI();

        $blpop = redis()->blpop(self::QUEUE, $timeout);
        if (isset($blpop[1])) {
            $task = crypto_unserialize($blpop[1]);

            if (!redis()->exists(FUTURE_LOCK . $task->asyncId)) {
                workbenchLog(LOG_INFO, "FutureTaskGC", array(
                    "async_id" =>  $task->asyncId,
                    "request_id" =>  $task->requestId, // explicitly log here because not available in context yet
                    "source" => get_class($task),
                    "measure.async.gc.task" => 1 . "task"));
                throw new TimeoutException();
            }

            return $task;
        }

        throw new TimeoutException();
    }
}

/**
 * The promise of the result of a FutureTask.
 * This is returned immediately by FutureTask::enqueue()
 * as a handle for the caller to get the actual result once it has been redeemed.
 */
class FutureResult {

    const RESULT = "FUTURE_RESULT";

    private $asyncId;
    private $result;

    function __construct($asyncId) {
        $this->asyncId = $asyncId;
    }

    /**
     * Get the FutureResult associated with an asyncId.
     * Users can only get FutureResults of FutureTasks they previously enqueued.
     *
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

    /**
     * Redeems the result (or exception) after async processing has completed.
     *
     * @param $result
     */
    function redeem($result) {
        verifyCallingFromCLI();
        $this->result = $result;
        redis()->rpush(self::RESULT . $this->asyncId, crypto_serialize($this->result));
    }


    /**
     * Gets the actual result when available.
     * If timeout is reached before actual result is redeemed, a TimeoutException will be thrown.
     * If the actual result is an Exception, that Exception will be thrown.
     *
     * @param $timeout
     * @return mixed
     * @throws TimeoutException
     * @throws
     */
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

    /**
     * Returns HTML and JavaScript snippet that can be rendered to the page
     * to call get() on this FutureResult until actual result redeemed.
     *
     * @return string
     */
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