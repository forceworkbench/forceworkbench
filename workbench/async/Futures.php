<?php
include_once "redis.php";

abstract class FutureTask {

    const QUEUE = "FUTURE_TASK_REQUESTS";

    private $asyncId;
    private $connConfig;

    function __construct() {
        $this->asyncId = uniqid();
        $this->connConfig = WorkbenchContext::get()->getConnConfig();
    }

    public function enqueue() {
        redis()->rpush(self::QUEUE, serialize($this));
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
    public static function dequeue() {
        $blpop = redis()->blpop(self::QUEUE, 30);
        if (isset($blpop[1])) {
            return unserialize($blpop[1]);
        } else {
            return null;
        }
    }
}

class FutureResult {

    private $asyncId;
    private $result;

    function __construct($asyncId) {
        $this->asyncId = $asyncId;
    }

    public static function fromId($asyncId) {
        return new FutureResult($asyncId);
    }

    function redeem($result) {
        $this->result = $result;
        redis()->rpush($this->asyncId, serialize($this->result));
    }

    public function getAsyncId() {
        return $this->asyncId;
    }

    public function get() {
        $blpop = redis()->blpop($this->asyncId, 10);

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

}

?>
