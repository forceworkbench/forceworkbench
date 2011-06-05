<?php
require_once "session.php";
require_once "shared.php";
require_once "controllers/StreamingController.php";

$c = new StreamingController();

require_once "header.php";
?>

<div id="messages">
    <?php $c->printMessages(); ?>
</div>

<div id="pushTopicContainer">
    <label for="selectedTopic">Push Topic:</label>
    <select id="selectedTopic">
        <?php $c->printPushTopicOptions(); ?>
    </select>

    &nbsp;
    
    <input id="pushTopicSubscribeBtn"
           type="button"
           value="Subscribe"/>

    <input id="pushTopicUnsubscribeBtn"
           type="button"
           value="Unsubscribe"/>

    <input id="pushTopicDetailsBtn"
           type="button"
           value="Details"/>

    &nbsp;&nbsp;

    <input id="toggleShowPolling"
           type="button"
           value="Show Polling"/>

    <div id="pushTopicDmlContainer">
        <form id="pushTopicDmlForm" method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
            <input id="pushTopicDmlForm_Id" name="pushTopicDmlForm_Id" type="hidden">
            <div>
                <label for="pushTopicDmlForm_Name">Name:</label>
                <input id="pushTopicDmlForm_Name" name="pushTopicDmlForm_Name"/>

                <label for="pushTopicDmlForm_ApiVersion">API Version:</label>
                <select id="pushTopicDmlForm_ApiVersion" name="pushTopicDmlForm_ApiVersion">
                    <?php $c->printApiVersionOptions(); ?>
                </select>
            </div>
            <div>
                <label for="pushTopicDmlForm_Query">Query:</label>
                <textarea id="pushTopicDmlForm_Query" name="pushTopicDmlForm_Query" cols="50" rows="3"></textarea>
            </div>
            <div id="pushTopicDmlForm_Btns">
                <input id="pushTopicSaveBtn"
                       name="PUSH_TOPIC_DML_SAVE"
                       type="submit"
                       value="Save"/>

                <input id="pushTopicDeleteBtn"
                       name="PUSH_TOPIC_DML_DELETE"
                       type="submit"
                       value="Delete"/>

                <span id='loadingMessage'>
                    <img src='<?php print getStaticResourcesPath(); ?>/images/wait16trans.gif' align='absmiddle'/>
                    Loading...
                </span>
            </div>
        </form>
    </div>
</div>

<div id="streamContainer">
    <div><h3>Data Stream</h3><span id="status"></span></div>
    <div id="streamBody"></div>
</div>

<script type="text/javascript">

</script>


<?php
addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/dojo/dojo/dojo.js'></script>");
addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/streamingClient.js'></script>");
addFooterScript("<script type='text/javascript'>var streamingConfig = { contextPath: '" . dirname(parse_url($_SERVER["PHP_SELF"], PHP_URL_PATH)) . "' };</script>");
require_once "footer.php";
?>
