<?php
require_once "shared.php";
require_once "session.php";
require_once "controllers/LoginController.php";

$c = new LoginController();
if (isset($_POST['uiLogin']) || !empty($_REQUEST["sid"]) || isset($_POST["oauth_Login"]) || isset($_GET["code"])) {
    $c->processRequest();
}

require_once "header.php";
?>

<p>
    <?php if (count($c->getErrors()) > 0) displayError($c->getErrors()) ?>
</p>

<div id="loginBlockContainer">
    <div id="loginBlock">
        <form id="login_form" action="login.php" method="post" style="display: <?php print $c->isOAuthRequired() ? "none" : "block"; ?>">
            <?php print getCsrfFormTag(); ?>
                <div id="login_type_selection" style="text-align: right;">
                <input type="radio" id="loginType_std" name="loginType" value="std"/>
                <label for="loginType_std">Standard</label>

                <input type="radio" id="loginType_adv" name="loginType" value="adv"/>
                <label for="loginType_adv">Advanced</label>
            </div>

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

            <div id="advContainer" style="display: none;">
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

            <p style="display: <?php print getConfig("displayJumpTo") ? "block" : "none"; ?>">
                <label for="startUrl">Jump to:</label>
                <select id="startUrl" name="startUrl" style="width: 18em;">
                    <?php printSelectOptions($c->getStartUrlSelectOptions(), $c->getStartUrl()); ?>
                </select>
            </p>

            <p>
                <div  style="text-align: right;">
                    <input type="submit" name="uiLogin" value="Login">
                </div>
            </p>
        </form>

        <form id="oauth_login_form" action="login.php" method="post" style="display: <?php print $c->isOAuthRequired() ? "block" : "none"; ?>">
            <?php print getCsrfFormTag(); ?>
            <p>
                <label for="inst">Environment:</label>
                <select id="oauth_env" name="oauth_host" style="width: 200px;">
                    <?php printSelectOptions($c->getOauthHostSelectOptions()); ?>
                </select>
            </p>

            <p>
                <label for="api">API Version:</label>
                <select id="oauth_api" name="api" style="width: 200px;">
                    <?php printSelectOptions($c->getApiVersionSelectOptions(), $c->getApiVersion()); ?>
                </select>
            </p>

            <p>
            <div  style="text-align: right;">
                <input type="submit" name="oauth_Login" value="Login with Salesforce">
            </div>
            </p>
        </form>
    </div>
</div>
    
<?php
addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/login.js'></script>");
addFooterScript("<script type='text/javascript'>wbLoginConfig=" . $c->getJsConfig() ."</script>");
addFooterScript("<script type='text/javascript'>WorkbenchLogin.initializeForm('" . htmlspecialchars($c->getLoginType()) ."');</script>");
require_once "footer.php";
?>