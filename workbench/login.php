<?php
require_once "shared.php";
require_once "session.php";
require_once "controllers/LoginController.php";

$c = new LoginController();
if (isset($_POST['uiLogin'])
    || !empty($_REQUEST["pw"])
    || !empty($_REQUEST["sid"])
    || isset($_POST["oauth_Login"])
    || isset($_GET["code"])
    || isset($_POST["signed_request"])
) {
    $c->processRequest();
}

require_once "header.php";
?>

<p>
    <?php if (count($c->getErrors()) > 0) displayError($c->getErrors()) ?>
</p>

<div id="loginBlockContainer">
    <form id="login_form" action="login.php" method="post">
        <?php print getCsrfFormTag(); ?>
        <input type="hidden" id="startUrl" name="startUrl" value="<?php print htmlspecialchars($c->getStartUrl(), ENT_QUOTES); ?>">
        <div id="login_type_selection" style="text-align: right; <?php if ($c->isOAuthRequired()) { print "display:none;"; } ?>">
            <input type="radio" id="loginType_std" name="loginType" value="std"/>
            <label for="loginType_std">Standard</label>

            <input type="radio" id="loginType_adv" name="loginType" value="adv"/>
            <label for="loginType_adv">Advanced</label>

             <?php if ($c->isOAuthEnabled()) { ?>
            <input type="radio" id="loginType_oauth" name="loginType" value="oauth"/>
            <label for="loginType_oauth">OAuth</label>
            <?php } ?>
        </div>

        <div class="loginType_oauth">
            <p>
                <label for="inst">Environment:</label>
                <select id="oauth_env" name="oauth_host" style="width: 200px;">
                    <?php printSelectOptions($c->getOauthHostSelectOptions()); ?>
                </select>
            </p>

            <p>
                <label for="api">API Version:</label>
                <select id="oauth_apiVersion" name="oauth_apiVersion" style="width: 200px;">
                    <?php printSelectOptions($c->getApiVersionSelectOptions(), $c->getApiVersion()); ?>
                </select>
            </p>
        </div>

        <div class="loginType_std loginType_adv">
            <p>
                <label for="un">Username:</label>
                <input type="text" id="un" name="un"size="55" value="<?php print htmlspecialchars($c->getUsername()); ?>"/>
            </p>

            <p>
                <label for="pw">Password:</label>
                <input type="password" id="pw" name="pw" size="55"/>
            </p>

            <div style="margin-left: 95px;">
                <input type="checkbox" id="rememberUser" name="rememberUser" <?php if ($c->isUserRemembered()) print "checked='checked'" ?> />
                <label for="rememberUser">Remember username</label>
                <span id="pwcaps" style="visibility: hidden; color: red; font-weight: bold; margin-left: 65px;">Caps lock is on!</span>
            </div>
        </div>

        <div class="loginType_adv">
            <p>
                <em>- OR -</em>
            </p>

            <p>
                <label for="sid">Session ID:</label>
                <input type="text" id="sid" name="sid" size="55">
            </p>

            <p>&nbsp;</p>

            <p>
                <label for="serverUrl">Server URL:</label>
                <input type="text" name="serverUrl" id="serverUrl" size="55" />
            </p>

            <p>
                <label for="inst">QuickSelect:</label>
                <select id="inst" name="inst">
                    <?php printSelectOptions($c->getSubdomainSelectOptions(), $c->getSubdomain()); ?>
                </select>
                &nbsp;
                <select id="api" name="api">
                    <?php printSelectOptions($c->getApiVersionSelectOptions(), $c->getApiVersion()); ?>
                </select>
            </p>
        </div>

        <div class="loginType_std loginType_oauth loginType_adv">
            <?php if ($c->getTermsFile()) { ?>
            <div style="margin-left: 95px;">
                <input type="checkbox" id="termsAccepted" name="termsAccepted"/>
                <label for="termsAccepted"><a href="terms.php" target="_blank">I agree to the terms of service</a></label>
            </div>
            <?php } ?>

            <p>
                <strong>Workbench is free to use, but is not an official salesforce.com product.</strong> Workbench has not been officially tested or
                documented. salesforce.com support is not available for Workbench. Support requests for Workbench should be directed
                to Stackoverflow at
                <a href="https://salesforce.stackexchange.com/questions/tagged/workbench">https://salesforce.stackexchange.com/questions/tagged/workbench</a>.
                Source code for Workbench  can be found at
                <a href="https://github.com/forceworkbench/forceworkbench">https://github.com/forceworkbench/forceworkbench</a>
                under separate and different license terms.
            </p>

            <p>
                <div style="text-align: right;">
                    <input type="submit" id="loginBtn" name="uiLogin" value="Login">
                </div>
            </p>
        </div>
    </form>
</div>
    
<?php
addFooterScript("<script type='text/javascript' src='" . getPathToStaticResource('/script/login.js') . "'></script>");
addFooterScript("<script type='text/javascript'>wbLoginConfig=" . $c->getJsConfig() ."</script>");
addFooterScript("<script type='text/javascript'>WorkbenchLogin.initializeForm(" . json_encode($c->getLoginType()) .");</script>");
require_once "footer.php";
?>