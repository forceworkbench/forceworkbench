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

        var ajax = getHTTPObject();
        if (ajax != null) {
            ajax.open("GET", "future_get.php?async_id=<?php echo $asyncId ?>", true);
            ajax.send(null);
            ajax.onreadystatechange = function() {
                if (ajax.readyState == 4) {
                    container.innerHTML = ajax.responseText;
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