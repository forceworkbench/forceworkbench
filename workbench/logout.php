<?php
require_once 'session.php';
require_once 'shared.php';

if ($_SESSION) {
    if (getConfig("invalidateSessionOnLogout")) {
        try {
            if (WorkbenchContext::isEstablished()) {
                WorkbenchContext::get()->getPartnerConnection()->logout();
            }
            $apiSessionInvalidated = true;
        } catch(Exception $e) {
            $apiSessionInvalidated = false;
        }

        if (isset($_SESSION['oauth']['serverUrlPrefix'])) {
            $uiLogoutIFrame = "<iframe src='". $_SESSION['oauth']['serverUrlPrefix'] . "/secur/logout.jsp' width='0' height='0' style='display:none;'></iframe>\n";
        }
    } else {
        $apiSessionInvalidated = false;
    }

    session_unset();
    session_destroy();
    
    require_once 'header.php';
    print "<p/>";

    if (isset($uiLogoutIFrame)) {
        print $uiLogoutIFrame;
    }

    if ($apiSessionInvalidated) {
        displayInfo('You have been successfully logged out of Workbench and Salesforce.');
    } else {
        displayInfo('You have been successfully logged out of Workbench.');
    }
    print "<script type='text/javascript'>setTimeout(\"location.href = 'login.php';\",3000);</script>";

    include_once 'footer.php';
} else {
    session_unset();
    session_destroy();

    header('Location: login.php');
}
?>
