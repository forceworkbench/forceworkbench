<?php
include_once 'shared.php';
?>
</div>

<div id='disclaimer'><br />

<?php
if (getConfig("checkSSL") && !usingSslEndToEnd()) {
    print "<div style='font-size: 8pt; color: orange;'>WARNING: Unsecure connection detected</div>";
}

if (WorkbenchContext::isEstablished() && WorkbenchContext::get()->isRequestStartTimeSet() && getConfig("displayRequestTime")) {
    printf ("Requested in %01.3f sec<BR/>", WorkbenchContext::get()->getRequestProcessingTime());
}

print "Workbench " . ($GLOBALS["WORKBENCH_VERSION"] != "trunk" ? $GLOBALS["WORKBENCH_VERSION"] : "") . "<br/>\n";

?></div>

</body>

<script type="text/javascript" src="<?php echo getStaticResourcesPath(); ?>/script/wz_tooltip.js"></script>

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

//USAGE: debug($showSuperVars = true, $showSoap = true, $customName = null, $customValue = null)
debug(true,true,null,null);
?>