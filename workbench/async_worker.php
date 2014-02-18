<?php
require_once 'shared.php';
require_once 'config/constants.php';
require_once 'config/WorkbenchConfig.php';
require_once 'context/WorkbenchContext.php';
require_once 'soxl/QueryObjects.php';
foreach (scandir('async') as $f) {
    if ($f == "." || $f == "..") continue;
    require_once "async/$f";
}

// block direct web access
if (php_sapi_name() != 'cli') {
    httpError(404, "Not Found");
}

$_SERVER['REMOTE_ADDR'] = 'CLI-' . getmypid();
$_SERVER['REQUEST_METHOD'] = 'ASYNC';

// future result gc
$frKeys = redis()->keys(FutureResult::RESULT . "*");
foreach ($frKeys as $frKey) {
    $asyncId = substr($frKey, strlen(FutureResult::RESULT));
    if (!redis()->exists(FUTURE_LOCK . $asyncId)) {
        redis()->del($frKey);
        workbenchLog(LOG_INFO, "FutureResultGC", array("async_id" => $asyncId, "request_id" =>  $task->requestId, "measure.async.gc.result" => 1 . "result"));
    }
}

workbenchLog(LOG_INFO, "FutureTaskQueueDepth", array("measure.async.queue_depth" => redis()->llen(FutureTask::QUEUE) . "task"));

while (true) {
    try {
        $job = FutureTask::dequeue(30);
        set_time_limit(WorkbenchConfig::get()->value('asyncTimeoutSeconds'));
        $job->execute();
    } catch (TimeoutException $e) {
        continue;
    }
    redis()->close();
    exit();
}
?>
