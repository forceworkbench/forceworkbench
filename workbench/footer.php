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
