<?php
require_once "session.php";
require_once "AsyncJob.php";

$job = new AsyncJob();
$jobId = $job->enqueue();

var_dump($job);

?>


<!--- bamboo code review-->
<!--- cisaurus-->
<!--- promotion cli-->
<!--- try bamboo-->
<!--- try anthill-->
<!--- eclipse test config-->