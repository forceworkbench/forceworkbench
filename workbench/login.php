<?php
require_once 'session.php';
require_once 'shared.php';

//main login

/*
 * For auto-login by GET params, allow users to either provide un/pw or sid, and optionally serverUrl and/or api version.
 * If the serverUrl is provided, it will be used alone, but if either
 */
if ((isset($_GET['un']) && isset($_GET['pw'])) || isset($_GET['sid'])) {

    $un       = isset($_GET['un'])       ? $_GET['un']       : null;
    $pw       = isset($_GET['pw'])       ? $_GET['pw']       : null;
    $sid      = isset($_GET['sid'])      ? $_GET['sid']      : null;
    $startUrl = isset($_GET['startUrl']) ? $_GET['startUrl'] : "select.php";
    //error handling for these (so users can't set all three
    //is already done in the processLogin() function
    //as it applies to both ui and auto-login

    //make sure the user isn't setting invalid combinations of query params
    if (isset($_GET['serverUrl']) && isset($_GET['inst']) && isset($_GET['api'])) {

        //display UI login page with error.
        displayLogin("Invalid auto-login parameters. Must set either serverUrl OR inst and/or api.");

    } else if (isset($_GET['serverUrl']) && !(isset($_GET['inst'])  || isset($_GET['api'])) ) {

        $serverUrl = $_GET['serverUrl'];

    } else {

        $serverUrl = getDefaultServerUrl();

    }

    $_REQUEST['autoLogin'] = 1;
    processLogin($un, $pw, $serverUrl, $sid, $startUrl);
}

if (isset($_POST['login_type'])) {
    if ($_POST['login_type']=='std') {
        processLogin($_POST['usernameStd'], $_POST['passwordStd'], null, null, $_POST['actionJumpStd']);
    } else if ($_POST['login_type']=='adv') {
        processLogin(
        isset($_POST['usernameAdv']) ? $_POST['usernameAdv'] : null,
        isset($_POST['passwordAdv']) ? $_POST['passwordAdv'] : null,
        $_POST['serverUrl'],
        isset($_POST['sessionId']) ? $_POST['sessionId'] : null,
        $_POST['actionJumpAdv']
        );
    }
} else {
    displayLogin(null);
}

function displayLogin($errors) {
    require_once 'header.php';

    //Displays errors if there are any
    if (isset($errors)) {
        displayError($errors, false, true);
    }

    $isRemembered = "";
    if (isset($_COOKIE['user'])) {
        $user = $_COOKIE['user'];
        $isRemembered = "checked='checked'";
        $jsFocus = 'password';
    } else if (isset($_POST['user'])) {
        $user = $_POST['user'];
        $jsFocus = 'user';
    } else {
        $user = null;
        $jsFocus = 'user';
    }


    //Display main login form body

    //move PHP session vars to simple vars for use in JS
    $useHTTPS = getConfig("useHTTPS");
    $defaultApiVersion = getConfig("defaultApiVersion");
    $defaultInstance = getConfig("defaultInstance");
    $defaultServerUrl = getDefaultServerUrl();

    print "<script type='text/javascript' language='JavaScript'>\n";

    print "var instNumDomainMap = [];\n";
    if (getConfig("fuzzyServerUrlLookup")) {
        foreach ($GLOBALS['config']['defaultInstance']['valuesToLabels'] as $subdomain => $instInfo) {
            if (isset($instInfo[1]) && $instInfo[1] != "") {
                print "\t" . "instNumDomainMap['$instInfo[1]'] = '$subdomain';" . "\n";
            }
        }
    }
    print "\n";

    print <<<LOGIN_FORM

function fuzzyServerUrlSelect() {
    var sid = document.getElementById('sessionId').value
    var sidIndex = sid.indexOf('00D');
        
    if (sidIndex > -1) {
        var instNum = sid.substring(sidIndex + 3, sidIndex + 4);
        var instVal = instNumDomainMap[instNum];
        if (instVal != null) {
            document.getElementById('inst').value = instVal;
            buildLocation();
        }
    }
}

function toggleUsernamePasswordSessionDisabled() {
    if (document.getElementById('sessionId').value) {
        document.getElementById('usernameAdv').disabled = true;
        document.getElementById('passwordAdv').disabled = true;
    } else {
        document.getElementById('usernameAdv').disabled = false;
        document.getElementById('passwordAdv').disabled = false;
    }

    if (document.getElementById('usernameAdv').value || document.getElementById('passwordAdv').value) {
        document.getElementById('sessionId').disabled = true;
    } else {
        document.getElementById('sessionId').disabled = false;
    }

}


function toggleLoginFormToAdv() {
    document.getElementById('login_std').style.display='none';
    document.getElementById('login_adv').style.display='inline';
    
    if (document.getElementById('usernameAdv').value == null || document.getElementById('usernameAdv').value == "") {
        document.getElementById('usernameAdv').focus();
    } else {
        document.getElementById('passwordAdv').focus();
    }
}

function toggleLoginFormToStd() {
    document.getElementById('login_std').style.display='inline';
    document.getElementById('login_adv').style.display='none';
    
    if (document.getElementById('username').value == null || document.getElementById('username').value == "") {
        document.getElementById('username').focus();
    } else {
        document.getElementById('password').focus();
    }
}

function buildLocation() {
    var inst = document.getElementById('inst').value;
    var endp = document.getElementById('endp').value;
    document.getElementById('serverUrl').value = 'http' + ($useHTTPS && (inst.search(/localhost/i) == -1) ? 's' : '') + '://' + inst + '.salesforce.com/services/Soap/u/' + endp;
}

function giveUserFocus() {
    if (document.getElementById('login_become_adv').checked) {
        document.getElementById('usernameAdv').focus();
    } else {
        document.getElementById('username').focus();
    }
}

function givePassFocus() {
    if (document.getElementById('login_become_adv').checked) {
        document.getElementById('passwordAdv').focus();
    } else {
        document.getElementById('password').focus();
    }
}

function checkCaps( pwcapsDivId, e ) {
    var key = 0;
    var shifted = false;

    // IE
    if ( document.all ) {
        key = e.keyCode;
    // Everything else
    } else {
        key = e.which;
    }

    shifted = e.shiftKey;

    var pwcaps = document.getElementById(pwcapsDivId);

    var upper = (key >= 65 && key <= 90);
    var lower = (key >= 97 && key <= 122);
    
    if ( (upper && !shifted) || (lower && shifted) ) {
        pwcaps.style.visibility='visible';
    } else if ( (lower && !shifted) || (upper && shifted) ) {
        pwcaps.style.visibility='hidden';
        
    }
}

</script>

<div id='intro_text'>
    &nbsp;<!--<p>Use the standard login to login with your salesforce.com username and password to your default instance or use the advanced
    login for other login options. Go to Settings for more login configurations.</p>-->
</div>

<div id='loginBlockContainer'>
<div id='loginBlock'>
    <form id='login_form' action='$_SERVER[PHP_SELF]' method='post'>
        <div id='login_become_select' style='text-align: right;'>
            <input type='radio' id='login_become_std' name='login_type' value='std' onClick='toggleLoginFormToStd();' checked='true' /><label for='login_become_std'>Standard</label>
            <input type='radio' id='login_become_adv' name='login_type' value='adv' onClick='toggleLoginFormToAdv();' /><label for='login_become_adv'>Advanced</label>
        </div>

        <div id='login_std'>
            <p><strong>Username: </strong><input type='text' name='usernameStd' id='username' size='55' value='$user' /></p>
            <p><strong>Password: </strong><input type='password' name='passwordStd'  id='password' size='55' onkeypress="checkCaps('pwcapsStd',event);" /></p>
LOGIN_FORM;

    //std jumpTo
    if (getConfig("displayJumpTo")) {
        print "<p><strong>Jump to: </strong>" .
          "<select name='actionJumpStd' style='width: 18em;'>" .     
              "<option value='select.php'></option>";
    
        foreach ($GLOBALS["MENUS"] as $menu => $pages) {
            foreach ($pages as $href => $page) {
                if ($page->onMenuSelect) print "<option value='" . $href . "'>" . $page->title . "</option>";
            }
        }
        print "</select></p>";
    } else {
        print "<input name='actionJumpStd' type='hidden' value='select.php'/>";
    }

    print <<<LOGIN_FORM_PART_2
            <p  style='text-align: right;'><span id='pwcapsStd' style='visibility: hidden; color: red; font-weight: bold; margin-right: 80px;'>Caps lock is on!</span><label><input type='checkbox' name='rememberUser' $isRemembered />Remember username</label></p>
        </div>

        <div id='login_adv' style='display: none;'>
            <p><strong>Username: </strong><input type='text' name='usernameAdv' id='usernameAdv' size='55' value='$user' onkeyup='toggleUsernamePasswordSessionDisabled();' onchange='toggleUsernamePasswordSessionDisabled();' /></p>
            <p><strong>Password: </strong><input type='password' name='passwordAdv' id='passwordAdv' size='55' onkeyup='toggleUsernamePasswordSessionDisabled();' onchange='toggleUsernamePasswordSessionDisabled();'  onkeypress="checkCaps('pwcapsAdv',event);"/></p>
            <p><em>- OR -</em><span id='pwcapsAdv' style='visibility: hidden; color: red; font-weight: bold; margin-left: 65px;'>Caps lock is on!</span></p>
            <p><strong>Session ID: </strong><input type='text' name='sessionId' id='sessionId' size='55' onkeyup='toggleUsernamePasswordSessionDisabled(); fuzzyServerUrlSelect();' onchange="toggleUsernamePasswordSessionDisabled(); fuzzyServerUrlSelect();"/></p>
            <p>&nbsp;</p>
            <p><strong>Server URL: </strong><input type='text' name='serverUrl' id='serverUrl' size='55' value='$defaultServerUrl' /></p>
            <p><strong>QuickSelect: </strong>
LOGIN_FORM_PART_2;

    //instance
    print "<select name='inst' id='inst' onChange='buildLocation();' onkeyup='buildLocation();'>";
    $instanceNames = array();
    foreach ($GLOBALS['config']['defaultInstance']['valuesToLabels'] as $subdomain => $instInfo) {
        $instanceNames[$subdomain] = $instInfo[0];
    }
    printSelectOptions($instanceNames,getConfig("defaultInstance"));
    print "</select>&nbsp;";

    //endpoint
    print "<select name='endp' id='endp' onChange='buildLocation();' onkeyup='buildLocation();'>";
    printSelectOptions($GLOBALS['config']['defaultApiVersion']['valuesToLabels'],getConfig("defaultApiVersion"));
    print "</select></p>";

    //advanced jumpTo
    if (getConfig("displayJumpTo")) {
        print "<p><strong>Jump to: </strong>" .
          "<select name='actionJumpAdv' style='width: 18em;'>" .     
          "<option value='select.php'></option>";
        foreach ($GLOBALS["MENUS"] as $menu => $pages) {
            foreach ($pages as $href => $page) {
                if ($page->onMenuSelect) print "<option value='" . $href . "'>" . $page->title . "</option>";
            }
        }
        print "</select></p>";
    } else {
        print "<input name='actionJumpAdv' type='hidden' value='select.php'/>";
    }

    //submit button
    print "</div><div id='login_submit' style='text-align: right;'>" .
            "<input type='submit' name='loginClick' value='Login'>" . 
        "</div>" . 

    "</form>" . 
"</div></div>";


    //if 'adv' is added to the login url and is not 0, default to advanced login
    if ((isset($_GET['adv']) && $_GET['adv'] != 0) ||
    (getConfig("defaultLoginType")=='Advanced')) {
        print "<script>
                document.getElementById('login_become_adv').checked=true; 
                toggleLoginFormToAdv(); 
            </script>";

    }

    print "<script>";
    if ($jsFocus == 'password') {
        print "givePassFocus();";
    } else if ($jsFocus == 'user') {
        print "giveUserFocus();";
    }
    print "</script>";

    include_once 'footer.php';



} //end display_form()


function processLogin($username, $password, $serverUrl, $sessionId, $actionJump) {
    $username = htmlspecialchars(trim($username));
    $password = htmlspecialchars(trim($password));
    $serverUrl = htmlspecialchars(trim($serverUrl));
    $sessionId = htmlspecialchars(trim($sessionId));
    $actionJump = htmlspecialchars(trim($actionJump));

    if (isset($_POST['rememberUser']) && $_POST['rememberUser'] !== 'on') setcookie('user',NULL,time()-3600);

    if ($username && $password && $sessionId) {
        displayLogin('Provide only username and password OR session id, but not all three.');
        exit;
    }

    //build server URL if not already; moved from logic below
    if (!isset($serverUrl) || $serverUrl == '') {
        $serverUrl = getDefaultServerUrl();
    }

    //block connections to localhost
    if (stripos($serverUrl,'localhost')) {
        if (isset($GLOBALS['internal']['localhostLoginError'])) {
            displayLogin($GLOBALS['internal']['localhostLoginError'],false,true);
        } else {
            displayLogin("Must not connect to 'localhost'",false,true);
        }
        exit;
    }

//        TODO: add support for mock clients with WbCtx
//        $partnerConnection = (getConfig('mockClients') ? new SforceMockPartnerClient() : new SforcePartnerClient());


    if (WorkbenchContext::isEstablished()) {
        WorkbenchContext::get()->release();
    }

    // TODO: clean up this hackiness due to in-progress context refactoring...
    $savedConfig = $_SESSION['config'];
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['config'] = $savedConfig;

    $overriddenClientId = isset($_GET["clientId"]) ? $_GET["clientId"] : null;
    if ($username && $password && !$sessionId) {
        $orgId = isset($_GET["orgId"]) ? $_GET["orgId"] : getConfig("loginScopeHeader_organizationId");
        $portalId = isset($_GET["portalId"]) ? $_GET["clientId"] : getConfig("loginScopeHeader_portalId");

        WorkbenchContext::establish(ConnectionConfiguration::fromUrl($serverUrl, null, $overriddenClientId));
        try {
            WorkbenchContext::get()->login($username, $password, $orgId, $portalId);
        } catch (Exception $e) {
            WorkbenchContext::get()->release();
            displayError($e->getMessage(), true, true);
        }
    } else if ($sessionId && $serverUrl && !($username && $password)) {
        if (stristr($serverUrl,'login') || stristr($serverUrl,'www') || stristr($serverUrl,'test') || stristr($serverUrl,'prerellogin')) {
            displayLogin('Must not connect to login server (www, login, test, or prerellogin) if providing a session id. ' .
                         'Choose your specific Salesforce instance on the QuickSelect menu when using a session id; ' .
                         'otherwise, provide a username and password and choose the appropriate a login server.');
            exit;
        }

        WorkbenchContext::establish(ConnectionConfiguration::fromUrl($serverUrl, $sessionId, $overriddenClientId));
        WorkbenchContext::get()->setIsUiSessionLikelySet(true);
    } else {
        WorkbenchContext::get()->release();
        throw new Exception('Invalid login parameters.');
    }

    // todo: put in WbCtx?
    if (stripos(WorkbenchContext::get()->getHost(),'localhost')) {
        if (isset($GLOBALS['internal']['localhostLoginRedirectError'])) {
            displayLogin($GLOBALS['internal']['localhostLoginRedirectError'],false,true);
        } else {
            displayLogin("Must not connect to 'localhost'",false,true);
        }
        exit;
    }

    if (isset($_POST['rememberUser']) && $_POST['rememberUser'] == 'on') {
        setcookie('user',$username,time()+60*60*24*7,'','','',TRUE);
    } else {
        setcookie('user',NULL,time()-3600);
    }

    if (isset($_REQUEST['autoLogin'])) {
        $actionJump .= (strpos($actionJump, "?") > -1 ? "&" :  "?") . "autoLogin=1";
        if (isset($_REQUEST['skipVC'])) $actionJump .= "&skipVC=1";
        if (isset($_GET['clientId'])) $_SESSION['tempClientId'] = $_GET['clientId'];
    }

    header("Location: $actionJump");
}

function getDefaultServerUrl() {
    $serverUrl = '';

    if (isset($_GET['serverUrlPrefix'])) {
        $serverUrl .= $_GET['serverUrlPrefix'];
    } else {
        if (getConfig("useHTTPS") && !stristr(getConfig("defaultInstance"),'localhost')) {
            $serverUrl .= "https://";
        } else {
            $serverUrl .= "http://";
        }

        if (isset($_GET['inst'])) {
            $serverUrl .= $_GET['inst'];
        } else {
            $serverUrl .= getConfig("defaultInstance");
        }

        $serverUrl .= ".salesforce.com";

        if (isset($_GET['port'])) {
            $serverUrl .= ":" . $_GET['port'];
        }
    }

    $serverUrl .= "/services/Soap/u/";

    if (isset($_GET['api'])) {
        $serverUrl .= $_GET['api'];
    } else {
        $serverUrl .= getConfig("defaultApiVersion");
    }

    return $serverUrl;
}
?>

