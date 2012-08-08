<?php
require_once 'config/constants.php';
require_once 'config/WorkbenchConfig.php';
require_once 'shared.php';
require_once 'context/WorkbenchContext.php';
foreach (scandir('async') as $f) {
    if ($f == "." || $f == "..") continue;
    require_once "async/$f";
}

while (true) {
    $job = FutureTask::dequeue();

    if ($job == null) {
        continue;
    }

    $job->execute();
}
?>
