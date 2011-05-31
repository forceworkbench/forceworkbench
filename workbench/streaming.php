<?php
require_once "session.php";
require_once "shared.php";
require_once "header.php";

addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/dojo/dojo/dojo.js'></script>");
addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/streamingClient.js'></script>");
addFooterScript("<script type='text/javascript'>var config = { contextPath: '" . dirname(parse_url($_SERVER["PHP_SELF"], PHP_URL_PATH)) . "' };</script>");
?>

<div id="streamBody" style="padding-top:10px;"></div>

<?php
include_once "footer.php";
?>