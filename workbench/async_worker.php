<?php
require_once 'config/constants.php';
require_once 'config/WorkbenchConfig.php';
require_once 'shared.php';
require_once 'context/WorkbenchContext.php';
foreach (scandir('async') as $f) {
    if ($f == "." || $f == "..") continue;
    require_once "async/$f";
}

// block direct web access
if (php_sapi_name() != 'cli') {
    httpError(404, "Not Found");
}

while (true) {
    try {
        $job = FutureTask::dequeue(30);
        $job->execute();
    } catch (TimeoutException $e) {
        continue;
    }
    exit();
}
?>
