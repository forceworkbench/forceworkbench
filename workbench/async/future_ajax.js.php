<?php function futureAjax($asyncId) { ?>

<div id="async-container-<?php echo $asyncId ?>"></div>

<script type="text/javascript">
    <!--
    // Get the HTTP Object
    function getHTTPObject() {
        if (window.ActiveXObject) {
            return new ActiveXObject("Microsoft.XMLHTTP");
        } else if (window.XMLHttpRequest) {
            return new XMLHttpRequest();
        } else {
            alert("Your browser does not support AJAX.");
            return null;
        }
    }

    function getFuture() {
        var container = document.getElementById('async-container-<?php echo $asyncId ?>');
        container.innerHTML = "<img src='<?php echo getPathToStaticResource('/images/wait16trans.gif') ?>'/>&nbsp; Loading...";

        getFutureInternal(container, 0);
    }

    function getFutureInternal(container, attempts) {
        var ajax = getHTTPObject();
        if (ajax != null) {
            ajax.open("GET", "future_get.php?async_id=<?php echo $asyncId ?>", true);
            ajax.send(null);
            ajax.onreadystatechange = function () {
                if (ajax.readyState == 4) {
                    if (ajax.status == 200) {
                        container.innerHTML = ajax.responseText;
                    } else if (ajax.status == 202) {
                        container.innerHTML += ".";
                        if (attempts > 50){
                            container.innerHTML = "<span style='color:red;'>Timed out waiting for asynchronous job to complete</span>";
                        } else {
                            getFutureInternal(container, attempts++);
                        }
                    } else if (ajax.status == 404) {
                        container.innerHTML = "<span style='color:red;'>Unknown Asynchronous Job</span>";
                    } else {
                        container.innerHTML = "<span style='color:red;'>Unknown Asynchronous State</span>";
                    }
                }
            };
        } else {
            container.innerHTML = "Unknown error loading content";
        }
    }


    getFuture();

    //-->
</script>

<?php } ?>