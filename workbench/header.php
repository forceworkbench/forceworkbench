<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <?php
        if (getenv('GA_TRACKING_ID') !== false) {
            print "<script async src=\"https://www.googletagmanager.com/gtag/js?id=". getenv('GA_TRACKING_ID') . "\"></script>";
            print "<script>";
            print "  window.dataLayer = window.dataLayer || [];";
            print "  function gtag(){dataLayer.push(arguments);}";
            print "  gtag('js', new Date());";
            print "  gtag('config', 'UA-119670592-1');";
            print "</script>";
        }
        ?>
        <?php
        if (getenv('PINGDOM_RUM') !== false) {
            print "<script src=\"" . getenv('PINGDOM_RUM') . "\" async></script>";
        }
        ?>
        <?php
        if (getenv('SENTRY_CLIENT_DSN') !== false) {
            print "<script src=\"https://cdn.ravenjs.com/3.25.2/raven.min.js\" crossorigin=\"anonymous\"></script>";
            print "<script>Raven.config(\"" . getenv('SENTRY_CLIENT_DSN') . "\").install()</script>";
        }
        ?>
        <meta http-equiv="Content-Language" content="UTF-8" />
        <meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />

        <link rel="shortcut icon" href="<?php echo getPathToStaticResource('/images/favicon.ico'); ?>" />

        <link rel="stylesheet" type="text/css" href="<?php echo getPathToStaticResource('/style/master.css'); ?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo getPathToStaticResource('/style/pro_dropdown.css'); ?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo getPathToStaticResource('/style/simpletree.css'); ?>" />

        <?php
        $myPage = getMyPage();
        $title = $myPage->showTitle ? ": " . $myPage->title : "";
        print "<title>Workbench$title</title>";

        print "<script type='text/javascript'>var getPathToStaticResource = " . getPathToStaticResourceAsJsFunction() . ";</script>";
        ?>
        
		<script type="text/javascript" src="<?php echo getPathToStaticResource('/script/pro_dropdown.js'); ?>"></script>
    </head>
<body>

<?php
if (WorkbenchConfig::get()->isConfigured("displayLiveMaintenanceMessage")) {
    print "<div style='background-color: orange; width: 100%; padding: 2px; font-size: 8pt; font-weight: bold;'>" .
              "Workbench is currently undergoing maintenance. The service may be intermittently unavailable during this time.</div><br/>";
}


// if async SOQL UI is not set, do not display it in the menu
if (!WorkbenchConfig::get()->value("allowAsyncSoqlUI"))  {
    $asyncSOQLpage = $GLOBALS["MENUS"]['Queries']['asyncSOQL.php'];
    $asyncSOQLpage->onNavBar = false;
}

// If the API version is not correct, do not display Async SOQL in the menu
if (WorkbenchContext::isEstablished() && !WorkbenchContext::get()->isApiVersionAtLeast(36.0)) {
    $asyncSOQLpage = $GLOBALS["MENUS"]['Queries']['asyncSOQL.php'];
    $asyncSOQLpage->onNavBar = false;
}

//check for latest version
function strip_seps($haystack) {
    foreach (array(' ', '_', '-') as $n) {
        $haystack = str_replace($n, "", $haystack);
    }
    return $haystack;
}

if (WorkbenchConfig::get()->value("checkForLatestVersion") && extension_loaded('curl') && (isset($_GET['autoLogin']) || 'login.php'==basename($_SERVER['PHP_SELF']))) {
    try {
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, 'https://api.github.com/repos/forceworkbench/forceworkbench/tags');
        curl_setopt ($ch, CURLOPT_USERAGENT, getWorkbenchUserAgent());
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $tagsResponse = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($tagsResponse === false || $info['http_code'] != 200) {
            throw new Exception("Could not access GitHub tags");
        }

        $tags = json_decode($tagsResponse);

        $betaTagNames = array();
        $gaTagNames = array();
        foreach ($tags as $tag) {
            if (preg_match('/^[0-9]+.[0-9]+/',$tag->name) === 0) {
                continue;
            } else if (stristr($tag->name, 'beta') ) {
                $betaTagNames[] = $tag->name;
        } else {
                $gaTagNames[] = $tag->name;
            }
        }
        rsort($betaTagNames);
        rsort($gaTagNames);

        $latestBetaVersion = strip_seps($betaTagNames[0]);
        $latestGaVersion = strip_seps($gaTagNames[0]);
        $currentVersion = strip_seps($GLOBALS["WORKBENCH_VERSION"]);

        if (stristr($currentVersion, 'beta') && !stristr($latestBetaVersion, $latestGaVersion)) {
            $latestChannelVersion = $latestBetaVersion;
        } else {
            $latestChannelVersion = $latestGaVersion;
            }

        if ($latestChannelVersion != $currentVersion) {
            print "<div style='background-color: #EAE9E4; width: 100%; padding: 2px;'>" .
                    "<a href='https://github.com/forceworkbench/forceworkbench/tags' target='_blank' " .
                        "style='font-size: 8pt; font-weight: bold; color: #0046ad;'>" .
                        "A newer version of Workbench is available for download</a>" .
                  "</div><br/>";
        }
    } catch (Exception $e) {
        //do nothing
    }
}
?>


<div id='mainBlock'>

<div id='navMenu' style="clear: both;">
    <span class="preload1"></span>
    <span class="preload2"></span>
    <ul id="nav">
    <?php
    foreach ($GLOBALS["MENUS"] as $menu => $pages) {
        if (isReadOnlyMode() && $menu == "Data") { //special-case for Data menu, since all read-only
            continue;
        }
        $menuLabel = ($menu == "WORKBENCH") ? "&nbsp;<img src='" . getPathToStaticResource('/images/workbench-3-cubed-white-small.png') . "'/>" : strtolower($menu);
        print "<li class='top'><a class='top_link'><span class='down'>" . $menuLabel ."</span></a>\n" .
                  "<ul class='sub'>";
        foreach ($pages as $href => $page) {
            if (!$page->onNavBar || (!isLoggedIn() && $page->requiresSfdcSession) || (isLoggedIn() && $page->title == 'Login') || (!$page->isReadOnly && isReadOnlyMode())) {
                continue;
            }
            print "<li><a href='$href' onmouseover=\"Tip('$page->desc')\" target=\"" . $page->window . "\">$page->title</a></li>\n";
        }
        print "</ul></li>";
    
        if(!isLoggedIn() || !termsOk()) break; //only show first "Workbench" menu in these cases
    }
    ?>
    </ul>
</div>

<?php
if (!termsOk() && $myPage->requiresSfdcSession) {
    ?>
    <div style="margin-left: 95px; margin-top: 10px;">
        <form method="POST" action="">
            <input type="checkbox" id="termsAccepted" name="termsAccepted"/>
            <label for="termsAccepted"><a href="terms.php" target="_blank">I agree to the terms of service</a></label>
            <input type="submit" value="Continue" style="margin-left: 10px; "/>
        </form>
    </div>
   <?php
    exit;
}

print "<table width='100%' border='0'><tr>";
if ($myPage->showTitle) {
    print "<td id='pageTitle'>" . $myPage->title . "</td>";
}
if (isLoggedIn() && termsOk()) {
    $userInfo = WorkbenchContext::get()->getUserInfo();
    $infoTips = array("Username: " . $userInfo->userName,
                      "Instance: " . WorkbenchContext::get()->getHost(),
                      "Org Id:&nbsp;&nbsp;" . substr($userInfo->organizationId, 0, 15),
                      "User Id:&nbsp;" . substr($userInfo->userId, 0, 15));

    print "<td id='myUserInfo'><a href='sessionInfo.php' onmouseover=\"Tip('". implode("<br/>", $infoTips) ."')\" >" .
           htmlspecialchars($userInfo->userFullName . " at " . $userInfo->organizationName) . " on API " . WorkbenchContext::get()->getApiVersion() . "</a></td>";
}
print "</tr></table>";

if (isset($errors)) {
    print "<p/>";
    displayError($errors, false, true);
}

?>
