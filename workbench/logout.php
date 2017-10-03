<?php
require_once 'session.php';
require_once 'shared.php';

if ($_SESSION) {
    $redirectTime = 2000;

    if (isset($_REQUEST['invalidateSession']) || (WorkbenchContext::isEstablished() && WorkbenchConfig::get()->value("invalidateSessionOnLogout"))) {
        try {
            if (WorkbenchContext::isEstablished()) {
                WorkbenchContext::get()->getPartnerConnection()->logout();
            }
            $apiSessionInvalidated = true;
        } catch(Exception $e) {
            $apiSessionInvalidated = false;
        }

        if (isset($_SESSION['oauth']['serverUrlPrefix']) && !empty($_SESSION['oauth']['serverUrlPrefix'])) {
            $redirectTime = 5000;
            $uiLogoutIFrame = "<iframe src='". htmlspecialchars($_SESSION['oauth']['serverUrlPrefix']) .
                              "/secur/logout.jsp' width='0' height='0' style='display:none;'></iframe>\n";
        }
    } else {
        $apiSessionInvalidated = false;
    }

    session_unset();
    session_destroy();

    $executing_script = basename($_SERVER['PHP_SELF']);
    //if 'noHeaderFooterForException' page property is set to false, include header
    foreach ($GLOBALS["MENUS"] as $type => $list_of_pages) {
      if (array_key_exists($executing_script, $list_of_pages) && ($GLOBALS["MENUS"][$type][$executing_script]->noHeaderFooterForExceptionHandler==false)) {
         require_once 'header.php';
      }
    }

    print "<p/>";

    if (isset($uiLogoutIFrame)) {
        print $uiLogoutIFrame;
    }

    if (isset($_REQUEST['message'])) {
        $redirectTime = 5000;
        displayError("An error has occurred and you have been logged out:\n" . $_REQUEST['message']);
    } else if ($apiSessionInvalidated) {
        displayInfo('You have been successfully logged out of Workbench and Salesforce.');
    } else {
        displayInfo('You have been successfully logged out of Workbench.');
    }
    print "<script type='text/javascript'>setTimeout(\"location.href = 'login.php';\", $redirectTime);</script>";

    //if 'noHeaderFooterForException' page property is set to false, include footer
    foreach ($GLOBALS["MENUS"] as $type => $list_of_pages) {
        if (array_key_exists($executing_script, $list_of_pages) && ($GLOBALS["MENUS"][$type][$executing_script]->noHeaderFooterForExceptionHandler==false)) {
        require_once 'footer.php';
        }
    }
} else {
    session_unset();
    session_destroy();

    header('Location: login.php');
}
?>
