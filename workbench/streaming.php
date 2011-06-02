<?php
require_once "session.php";
require_once "shared.php";
require_once "header.php";

addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/dojo/dojo/dojo.js'></script>");
//addFooterScript("<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/dojo/1.6/dojo/dojo.xd.js' djConfig='parseOnLoad: true'></script>")
addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/streamingClient.js'></script>");
addFooterScript("<script type='text/javascript'>var cometdConfig = { contextPath: '" . dirname(parse_url($_SERVER["PHP_SELF"], PHP_URL_PATH)) . "' };</script>");
?>

<p>
    <form>
        Topic: <input id="topicName"/>
        <input id="subBtn"
               type="button"
               value="Subscribe"
               />
    </form>
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