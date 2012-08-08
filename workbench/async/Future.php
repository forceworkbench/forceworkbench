<?php

include_once "asyncUtils.php";

class Future {

    private $asyncId;
    private $result;

    function __construct($asyncId) {
        $this->asyncId = $asyncId;
    }

    public static function fromId($asyncId) {
        return new Future($asyncId);
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
