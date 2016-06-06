<?php
require_once "session.php";
require_once "shared.php";
require_once "controllers/StreamingController.php";

$c = new StreamingController();
if ($c->isAjax()) {
    echo $c->getAjaxResponse();
    exit;
}

// begin normal page refresh
require_once "header.php";
?>

<p class="instructions">Subscribe to a Push Topic to stream query updates:</p>

<div id="messages">
    <?php echo $c->getMessages(); ?>
</div>

<div id="subscriptionTypeSelectors">
    <label><input id="subscriptionTypeSelectPushTopic" type="radio" name="subscriptionType" value="pushTopic" checked/>Push Topics</label>
    <label><input id="subscriptionTypeSelectGenericSubscription" type="radio" name="subscriptionType" value="genericSubscription"/>Generic Subscriptions</label>
</div>

<div id="replayFromContainer" style="display: none;">
    <label>Replay from:  <input id="replayFrom" name="replayFrom" value="-1"></label>
</div>

<div id="pushTopicContainer" style="display: block;">
    <label for="selectedTopic">Push Topic:</label>&nbsp;&nbsp;
    <select id="selectedTopic">
        <?php echo $c->getPushTopicOptions(); ?>
    </select>

    &nbsp;
    
    <input id="pushTopicSubscribeBtn"
           type="button"
           disabled="disabled"
           value="Subscribe"/>

    <input id="pushTopicUnsubscribeBtn"
           type="button"
           disabled="disabled"
           value="Unsubscribe"/>

    <input id="pushTopicDetailsBtn"
           type="button"
           value="Details"/>

    <div id="pushTopicDmlContainer">
        <form id="pushTopicDmlForm" method="" action="">
            <input id="pushTopicDmlForm_Id" name="pushTopicDmlForm_Id" type="hidden">
            <div>
                <label for="pushTopicDmlForm_Name">Name:</label>
                <input id="pushTopicDmlForm_Name" name="pushTopicDmlForm_Name" size="30"/>

                <label for="pushTopicDmlForm_ApiVersion">API Version:</label>
                <select id="pushTopicDmlForm_ApiVersion" name="pushTopicDmlForm_ApiVersion">
                    <?php echo $c->getApiVersionOptions(); ?>
                </select>
            </div>
            <div>
                <label for="pushTopicDmlForm_Query">Query:</label>
                <textarea id="pushTopicDmlForm_Query" name="pushTopicDmlForm_Query" cols="50" rows="3"></textarea>
            </div>
            <div id="pushTopicDmlForm_Btns">
                <input id="pushTopicSaveBtn"
                       name="PUSH_TOPIC_DML_SAVE"
                       type="button"
                       value="Save"/>

                <input id="pushTopicDeleteBtn"
                       name="PUSH_TOPIC_DML_DELETE"
                       type="button"
                       value="Delete"/>

                <span id="waitingIndicator">
                    <img src="<?php echo getPathToStaticResource('/images/wait16trans.gif'); ?>"/>
                    Processing...
                </span>
            </div>
        </form>
    </div>
</div>

<div id="genericSubscriptionContainer" style="display: none;">
    <label>Subscription: <input id="genericSubscription" name="genericSubscription"></label>
    &nbsp;
    <input id="genericSubscribeBtn" type="button" value="Subscribe"/>
    <input id="genericUnsubscribeBtn" type="button" value="Unsubscribe" disabled="disabled"/>
</div>

<div id="streamContainer">
    <div>
      <span id="status"></span>
      <span id="pollIndicator">&bull;</span>
      <span id="streamUtilities">
        <input id="clearStream" type="button" value="Clear"/>
        <input id="toggleShowPolling" type="button" value="Show Polling"/>
      </span>
    </div>
    <div id="streamBody"></div>
</div>

<!-- dummy element for backward comparability -->
<div id='partialSavedTopic' style='display:none;'></div>

<script type="text/javascript">

</script>


<?php
if ($c->isEnabled()) {
    addFooterScript("<script type='text/javascript' src='" . 'static-unversioned/script/dojo/dojo/dojo.js' . "'></script>");
    addFooterScript("<script type='text/javascript' src='" . getPathToStaticResource('/script/CometDReplayExt.js') . "'></script>");
    addFooterScript("<script type='text/javascript' src='" . getPathToStaticResource('/script/streamingClient.js') . "'></script>");
    addFooterScript("<script type='text/javascript'>var wbStreaming = ". $c->getStreamingConfig() . ";</script>");
}
require_once "footer.php";
?>
