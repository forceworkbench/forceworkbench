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
    printf ("Requested in %01.3f sec<BR/>", WorkbenchContext::get()->getRequestProcessingTime());
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
if (WorkbenchContext::isEstablished() && (memory_get_peak_usage()/toBytes(ini_get("memory_limit"))) > 0.7) {
   WorkbenchContext::get()->clearCache();
}

if (isset($GLOBALS['REDIS'])) {
    redis()->close();
}

//USAGE: debug($showSuperVars = true, $showSoap = true, $customName = null, $customValue = null)
debug(true,true,null,null);
?>