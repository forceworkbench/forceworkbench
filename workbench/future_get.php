<?php

    include_once "async/futures.php";
    session_write_close();

    try {
        echo FutureResult::fromId($_REQUEST['async_id'])->get();
    } catch (TimeoutException $e) {
        header("202 Accepted");
    }
    // TODO handle not found
?>