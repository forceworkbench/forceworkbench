<?php
require_once "session.php";
require_once "shared.php";
require_once "header.php";

addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/dojo/dojo/dojo.js'></script>");
addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/streamingClient.js'></script>");
addFooterScript("<script type='text/javascript'>var streamingConfig = { contextPath: '" . dirname(parse_url($_SERVER["PHP_SELF"], PHP_URL_PATH)) . "' };</script>");
?>

<div id="pushTopicContainer">
    <label for="selectedTopic">Push Topic:</label>
    <select id="selectedTopic">
        <option value='{"id":null, "name":null, "apiVersion":null, "query":null}'>-- Create New --</option>
        <option value='{"id":"0IFP00000004C9SOAU", "name":"/accounts", "apiVersion": "22.0", "query":"SELECT Id FROM Accounts WHERE ..."}'>/accounts</option>
        <option value='{"id":"0IFP00000004C9NOAU", "name":"/chromeAccounts", "apiVersion": "22.0", "query":"SELECT Id FROM Accounts WHERE Name = Chrome"}'>/chromeAccounts</option>
    </select>

    <input id="pushTopicSubscribeBtn"
           type="button"
           value="Subscribe"/>

    <input id="pushTopicDetailsBtn"
           type="button"
           value="Details"/>

    <div id="pushTopicDmlContainer">
        <form id="pushTopicDmlForm" action="TODO">
            <input id="pushTopicDmlForm_Id" type="hidden">
            <div>
                <label for="pushTopicDmlForm_Name">Name:</label>
                <input id="pushTopicDmlForm_Name"/>

                <label for="pushTopicDmlForm_ApiVersion">API Version:</label>
                <select id="pushTopicDmlForm_ApiVersion">
                    <option value="22.0">22.0</option>
                    <option value="21.0">21.0</option>
                </select>
            </div>
            <div>
                <label for="pushTopicDmlForm_Query">Query:</label>
                <textarea id="pushTopicDmlForm_Query" cols="50" rows="3"></textarea>
            </div>
            <div id="pushTopicDmlForm_Btns">
                <input id="pushTopicSaveBtn"
                       type="button"
                       value="Save"/>

                <input id="pushTopicDeleteBtn"
                       type="button"
                       value="Delete"/>

                <span id='loadingMessage'>
                    <img src='<?php print getStaticResourcesPath(); ?>/images/wait16trans.gif' align='absmiddle'/>
                    Loading...
                </span>
            </div>
        </form>
    </div>
</div>

<div id="streamBody"></div>

<script type="text/javascript">

</script>


<?php
include_once "footer.php";
?>
