<?php

    include_once "session.php";
    include_once "shared.php";
    include_once "async/futures.php";
    session_write_close();

    try {
        echo FutureResult::fromId($_REQUEST['async_id'])->get(5);
    } catch (TimeoutException $te) {
        httpError("202", "Accepted");
    } catch (UnknownAsyncIdException $ue) {
        httpError("404", "Not Found");
    }
?>