<?php
include_once 'shared.php';
?>
</div>

<div id='disclaimer'><br />

<?php
if (WorkbenchConfig::get()->value("checkSSL") && !usingSslEndToEnd()) {
    print "<div style='font-size: 8pt; color: orange;'>WARNING: Unsecure connection detected</div>";
}

if (WorkbenchContext::isEstablished() && WorkbenchContext::get()->isRequestStartTimeSet() && WorkbenchConfig::get()->value("displayRequestTime")) {
    $requestProcessingTime = WorkbenchContext::get()->getRequestProcessingTime();
    workbenchLog(LOG_INFO, "RequestProcessingMetrics", array(("measure.request.service") => $requestProcessingTime . "sec", "source" => basename($_SERVER['SCRIPT_NAME'])));
    printf ("Requested in %01.3f sec<BR/>", $requestProcessingTime);
}

print "Workbench " . ($GLOBALS["WORKBENCH_VERSION"] != "trunk" ? $GLOBALS["WORKBENCH_VERSION"] : "") . "<br/>\n";
?></div>

</body>

<script type="text/javascript" src="<?php echo getPathToStaticResource('/script/wz_tooltip.js'); ?>"></script>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
<script src="<?php echo getPathToStaticResource('/script/bootstrap.js'); ?>"></script>

<?php
if (isset($_REQUEST["footerScripts"])) {
    foreach ($_REQUEST["footerScripts"] as $script) {
        print $script . "\n";
    }
}
?>

</html>

<?php
$peak = memory_get_peak_usage();
workbenchLog(LOG_INFO, "MemoryUsageCheck", array("measure.memory.peak" => $peak . "byte"));
if (WorkbenchContext::isEstablished() && ($peak/toBytes(ini_get("memory_limit"))) > 0.7) {
   WorkbenchContext::get()->clearCache();
   workbenchLog(LOG_INFO, "MemoryUsageCacheClear", array("measure.memory.cache_clear" => 1));
}

if (isset($GLOBALS['REDIS'])) {
    redis()->close();
}

?>
