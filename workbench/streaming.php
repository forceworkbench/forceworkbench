<?php
require_once "session.php";
require_once "shared.php";
require_once "header.php";

addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/dojo/dojo/dojo.js'></script>");
addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/streamingClient.js'></script>");
addFooterScript("<script type='text/javascript'>var config = { contextPath: '" . dirname(parse_url($_SERVER["PHP_SELF"], PHP_URL_PATH)) . "' };</script>");
?>

<p>
<!--    <form>-->
<!--        Topic: <input id="topicName"/>-->
<!--        <input type="button"-->
<!--               value="Subscribe"-->
<!--               onclick="dojox.cometd.subscribe('chromeAccounts', wbUtil.handleSubscription);"/>-->
<!--    </form>-->
</p>

<style>
    #streamBody div {
        padding: 10px;
        background-color: #bbb;
    }
</style>

<div id="streamBody" style="padding-top:10px;"></div>

<?php
include_once "footer.php";
?>