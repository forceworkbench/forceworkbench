<?php function futureAjax($asyncId) { ?>

<div id="async-container-<?php echo $asyncId ?>"></div>

<script type='text/javascript' src='<?php echo getPathToStaticResource('/script/getElementsByClassName.js') ?>'></script>

<script type="text/javascript">
    <!--

    var WorkbenchFuture<?php echo $asyncId ?> = new function() {
        // Get the HTTP Object
        this.getHTTPObject = function() {
            if (window.ActiveXObject) {
                return new ActiveXObject("Microsoft.XMLHTTP");
            } else if (window.XMLHttpRequest) {
                return new XMLHttpRequest();
            } else {
                alert("Your browser does not support AJAX.");
                return null;
            }
        };

        this.getFuture = function() {
            this.disableWhileAsyncLoading(true);
            var container = document.getElementById('async-container-<?php echo $asyncId ?>');
            container.innerHTML = "<img src='<?php echo getPathToStaticResource('/images/wait16trans.gif') ?>'/>&nbsp; Loading...";
            this.getFutureInternal(container, 0);
        };

        this.getFutureInternal = function(container, totalTimeWaiting) {
            var ajax = this.getHTTPObject();
            if (ajax != null) {
                var longPollTimeout = 10;
                ajax.open("GET", "future_get.php?async_id=<?php echo $asyncId ?>&wait_for=" + longPollTimeout, true);
                ajax.send(null);
                ajax.onreadystatechange = function () {
                    if (ajax.readyState == 4) {
                        if (ajax.status == 200) {
                            container.innerHTML = ajax.responseText;
                            var evalables = getElementsByClassName("evalable", "script", container);
                            for (i in evalables) {
                                eval(evalables[i].innerHTML)
                            }
                        } else if (ajax.status == 202) {
                            // 202 means that long poll ended, but still waiting for result
                            container.innerHTML += ".";
                            if (totalTimeWaiting > (<?php echo WorkbenchConfig::get()->value('asyncTimeoutSeconds'); ?>)) {
                                container.innerHTML = "<span style='color:red;'>Timed out waiting for asynchronous job to complete</span>";
                            } else {
                                WorkbenchFuture<?php echo $asyncId ?>.getFutureInternal(container, totalTimeWaiting + longPollTimeout);
                                return;
                            }
                        } else if (ajax.status == 404) {
                            container.innerHTML = "<span style='color:red;'>Unknown Asynchronous Job</span>";
                        } else if (ajax.status == 503) {
                            container.innerHTML = "<span style='color:red;'>Service was temporarily interrupted or is unavailable. Please try again in a moment.</span>";
                        } else {
                            container.innerHTML = "<span style='color:red;'>Unknown Asynchronous State</span>";
                        }
                        WorkbenchFuture<?php echo $asyncId ?>.disableWhileAsyncLoading(false);
                    }
                };
            } else {
                container.innerHTML = "Unknown error loading content";
            }
        };

        this.disableWhileAsyncLoading = function(isAllowed) {
            var disableWhileAsyncLoadingElements = getElementsByClassName("disableWhileAsyncLoading");

            for (i in disableWhileAsyncLoadingElements) {
                disableWhileAsyncLoadingElements[i].disabled = isAllowed;
            }
        };
    };

    WorkbenchFuture<?php echo $asyncId ?>.getFuture();

    //-->
</script>

<?php } ?>