<?php
include_once 'shared.php';
?>
</div>

<div id='disclaimer'><br />

<?php
if (WorkbenchContext::isEstablished() && isset($_SESSION["config"]["checkSSL"]) && $_SESSION["config"]["checkSSL"]) {
    //is connection unsecure from this machine to Workbench?
    $unsecureLocal2Wb = !isset($_SERVER['HTTPS']) && $_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1' && $_SERVER['SERVER_NAME'] !== 'workbench';

    //is connection unsecure from Workbench to Salesforce?
    $unsecureWb2sfdc = !WorkbenchContext::get()->isSecure();

    if ($unsecureLocal2Wb || $unsecureWb2sfdc) {
        print "<span style='font-size: 8pt; color: red;'>WARNING: Unsecure connection detected";

        if($unsecureLocal2Wb) print " to Workbench";
        if($unsecureLocal2Wb && $unsecureWb2sfdc) print " and";
        if($unsecureWb2sfdc) print " to Salesforce";

        print "</span><br/>";
    }
}

if (WorkbenchContext::isEstablished() && getConfig("displayRequestTime")) {
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
//USAGE: debug($showSuperVars = true, $showSoap = true, $customName = null, $customValue = null)
debug(true,true,null,null);
?>