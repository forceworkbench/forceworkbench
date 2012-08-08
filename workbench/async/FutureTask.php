<?php

include_once "asyncUtils.php";
include_once "Future.php";

abstract class FutureTask {

    const ASYNC_JOB_QUEUE = "ASYNC_JOB_QUEUE";

    private $asyncId;
    private $connConfig;

    function __construct() {
        $this->asyncId = uniqid();
        $this->connConfig = WorkbenchContext::get()->getConnConfig();
    }

    public function enqueue() {
        redis()->rpush(self::ASYNC_JOB_QUEUE, serialize($this));
        return new Future($this->asyncId);
    }

    abstract function perform();

    function execute() {
        $future = new Future($this->asyncId);
        try {
            WorkbenchContext::establish($this->connConfig);
            $future->redeem($this->perform());
            WorkbenchContext::get()->release();
        } catch (Exception $e) {
            $future->redeem($e);
        }
    }

    /**
     * @static
     * @return FutureTask
     */
    public static function dequeue() {
        $blpop = redis()->blpop(self::ASYNC_JOB_QUEUE, 30);
        if (isset($blpop[1])) {
            return unserialize($blpop[1]);
        } else {
            return null;
        }
    }
}
?>
