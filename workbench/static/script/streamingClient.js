dojo.require("dojox.cometd");

dojo.addOnLoad(function() {
    var cometd = dojox.cometd;
    var isConnected = false;
    var showPolling = false;
    var subscriptions = [];

    function metaAny(message) {
        flashPollIndicator();
    }

    function metaConnect(message) {
        if (cometd.isDisconnected()) {
            isConnected = false;
            postToStream("Disconnected", message);
            return;
        }

        var wasConnected = isConnected;
        isConnected = message.successful === true;
        if (!wasConnected && isConnected) {
            postToStream("Connection Established", message);
        } else if (wasConnected && !isConnected) {
            postErrorToStream("Connection Broken", message);
        } else if (showPolling) {
            postToStream("Poll Completed", message);
        }

        setStatus(isConnected ? "Connected" : "Disconnected");
    }

    // Function invoked when first contacting the server and
    // when the server has lost the state of this client
    function metaHandshake(message) {
        if (message.successful === true) {
            postToStream("Handshake Successful", message);
            setStatus("Handshake Successful");
        } else {
            postErrorToStream("Handshake Failure", message);
            setStatus("Handshake Failure");
        }

        subscriptions = [];
        toggleSubUnSubButtons();
    }

    function metaSubscribe(message) {
        if (message.successful === true) {
            postToStream("Subscribed to " + message.subscription, message);
        } else {
            postErrorToStream("Subscription Failure: " + message.error, message);
            unsubscribe(message.subscription);
        }
    }

    function metaUnsubscribe(message) {
        if (message.successful === true) {
            postToStream("Unsubscribed from " + message.subscription, message);
        } else {
            postErrorToStream("Unsubscription Failure: " + message.error, message);
        }
    }

    function metaUnsuccessful(message) {
//        postErrorToStream("Unknown Failure", message);
    }


    function handleSubscription(message) {
        flashPollIndicator();
        postToStream("Message received from: " + message.channel, message);
    }

    function printObject(obj, maxDepth, prefix) {
        if (maxDepth === undefined && prefix === undefined) {
            try {
                var ppJson = dojo.toJson(obj, true);
                ppJson = ppJson.replace(/&/g, "&amp;");
                ppJson = ppJson.replace(/</g, "&lt;");
                ppJson = ppJson.replace(/>/g, "&gt;");
                ppJson = ppJson.replace(/"/g, "&quot;");
                ppJson = ppJson.replace(/\b(\w{4}0{3}\w{11})\b/g, "<a href='retrieve.php?id=$1' target='retrieves'>$1</a>");
                ppJson = ppJson.replace(/\n/g, "<br/>");
                ppJson = ppJson.replace(/\t/g, "&nbsp;&nbsp;");
                return ppJson;
            } catch (e1) {
                window.console.debug("Problem writing to JSON. Falling back to old printer.");
            }
        }

        var result = "";
        var key;

        if (!prefix) {
            prefix = "";
        }

        for (key in obj) {
            if (obj.hasOwnProperty(key)) {
                var objType;
                try {
                    // this can fail on XHR and who knows what else. try, log, and keep moving...
                    objType = typeof obj[key];
                } catch (e2) {
                    window.console.debug("Problem finding typeof " + obj + " at key [" + key + "]");
                    continue;
                }

                if (objType === "object") {
                    if (maxDepth !== undefined && maxDepth <= 1) {
                        result += (prefix + key + "=object [max depth reached]\n");
                    } else {
                        result += printObject(obj[key], (maxDepth) ? maxDepth - 1 : maxDepth, prefix + key + ".");
                    }
                } else {
                    result += (prefix + key + "=" + obj[key] + "<br/>");
                }
            }
        }
        return result;
    }

    function setCometdUrl(url) {
        cometd.configure({
            url: url
        });
    }

    function selectSubscriptionType(subType) {
        var pushTopicContainer = dojo.byId("pushTopicContainer");
        var genericSubscriptionContainer = dojo.byId("genericSubscriptionContainer");

        if (subType == "pushTopic") {
            pushTopicContainer.style.display = "block";
            genericSubscriptionContainer.style.display = "none";
        } else if (subType == "genericSubscription") {
            genericSubscriptionContainer.style.display = "block";
            pushTopicContainer.style.display = "none";
        } else {
          alert("Unknown subscription subType!")
        }
    }

    function togglePushTopicDmlContainer_Internal(forceShow) {
        var cont = dojo.byId("pushTopicDmlContainer");
        if (cont.style.display !== "block" || forceShow) {
            cont.style.display = "block";
        } else {
            cont.style.display = "none";
        }
    }

    function setStatus(status) {
        dojo.byId("streamContainer").style.display = "block";
        dojo.byId("status").innerHTML = status;
    }

    function flashPollIndicator() {
            dojo.byId("pollIndicator").style.display = "inline";
            setTimeout(function() { dojo.byId("pollIndicator").style.display = "none"; }, 500);
    }

    function hideMessages() {
        dojo.byId("messages").style.display = "none";
    }

    function copySelectedTopic() {
        var details = dojo.byId("selectedTopic").value;

        if (details === null || details === "") {
            return;
        }

        details = JSON.parse(details);
        copyDetails(details, details.Name === null);
    }

    function copyDetails(details, displayDetails) {
        if (displayDetails) {
            togglePushTopicDmlContainer_Internal(true);
        }

        dojo.byId("pushTopicDmlForm_Id").value = details.Id;
        dojo.byId("pushTopicDmlForm_Name").value = details.Name;
        dojo.byId("pushTopicDmlForm_ApiVersion").value = details.ApiVersion;
        dojo.byId("pushTopicDmlForm_Query").value = details.Query;
    }


    function postToStream(heading, message, msgClass) {
        dojo.byId("streamContainer").style.display = "block";

        if (msgClass === undefined) {
            msgClass = "std";
        }

        if (message.id !== undefined) {
            heading = message.id + ". " + heading;
        }

        dojo.byId("streamBody").innerHTML = "<div class=" + msgClass + ">" +
                                                "<span class=\"heading\">" + heading + "</span><br/>" +
                                                "<span class=\"body\">" + printObject(message) + "</span>" +
                                            "</div>" +
                                            dojo.byId("streamBody").innerHTML;
    }

    function postErrorToStream(heading, message) {
        postToStream(heading, message, "error");
    }


    function listenerExceptionHandler(exception, subscriptionHandle, isListener, message) {
        postErrorToStream("Unknown exception occurred. Removing offending listener or subscription.", [message, exception]);

        if (isListener) {
            this.removeListener(subscriptionHandle);
        } else {
            this.unsubscribe(subscriptionHandle);
            toggleSubUnSubButtons();
        }
    }

    function handshake() {
        setStatus("Handshaking");

        if (isUsingStreamingV2()) {
            if(cometd.getExtension('replayFrom')) {
                cometd.unregisterExtension('replayFrom');
            }
            var replayFrom = dojo.byId("replayFrom").value;
            cometd.registerExtension('replayFrom', new CometDReplayExt());
        } else {
            cometd.unregisterExtension('replayFrom');
        }

        cometd.handshake();
    }

    function subcribePushTopic() {
        var topic = dojo.byId("selectedTopic").value;
        var topicName = JSON.parse(topic).Name;
        var subscription = "/topic/" + topicName;
        subscribe(subscription);
    }

    function subscribeGeneric() {
        var genericSubscription = dojo.byId("genericSubscription").value;
        subscribe(genericSubscription);
    }
    
    function subscribe(subscription) {
        if (isUsingStreamingV2()) {
            var replayFrom = dojo.byId("replayFrom").value;
            cometd.getExtension('replayFrom').setChannel(subscription);
            cometd.getExtension('replayFrom').setReplay(replayFrom);
        } else {
            if (null != cometd.getExtension('replayFrom')) {
                cometd.unregisterExtension('replayFrom');
            }
        }

        subscriptions[subscription] = cometd.subscribe(subscription, handleSubscription);
        toggleSubUnSubButtons();
    }

    function unsubscribePushTopic() {
        var topic = dojo.byId("selectedTopic").value;
        var topicName = JSON.parse(topic).Name;
        var subscription = "/topic/" + topicName;
        unsubscribe(subscription);
    }

    function unsubscribeGeneric() {
        var genericSubsription = dojo.byId("genericSubscription").value;
        unsubscribe(genericSubsription);
    }

    function unsubscribe(subscription) {
        cometd.unsubscribe(subscriptions[subscription]);
        subscriptions[subscription] = undefined;
        toggleSubUnSubButtons();
    }

    function disconnect() {
        setStatus("Disconnecting");
        cometd.disconnect(true);
        setStatus("Disconnected");
        isConnected = false;
    }

    function toggleShowPolling() {
        showPolling = !showPolling;
        dojo.byId("toggleShowPolling").value = showPolling ? "Hide Polling" : "Show Polling";
    }

    function togglePushTopicDmlContainer() {
        togglePushTopicDmlContainer_Internal(false);
    }

    function toggleSubUnSubButtons() {
        var topic;
        var subscribeBtnId;
        var unSubscribeBtnId;
        var isPushTopic;
        if (dojo.byId("subscriptionTypeSelectPushTopic").checked) {
            topic = dojo.byId("selectedTopic").value;
            subscribeBtnId = "pushTopicSubscribeBtn";
            unSubscribeBtnId = "pushTopicUnsubscribeBtn";
            isPushTopic = true;
        } else if (dojo.byId("subscriptionTypeSelectGenericSubscription").checked) {
            topic = dojo.byId("genericSubscription").value;
            subscribeBtnId = "genericSubscribeBtn";
            unSubscribeBtnId = "genericUnsubscribeBtn";
            isPushTopic = false;
        } else {
            alert("Unknown streaming type. Please select Push Topic or Generic.");
            return;
        }
        toggleSubUnSubButtons_Helper(topic, subscribeBtnId, unSubscribeBtnId, isPushTopic);
    }

    function toggleSubUnSubButtons_Helper(topic, subscribeBtnId, unSubscribeBtnId, isPushTopic) {
        var subName;

        if (isPushTopic && (topic === null || topic === "")) {
            dojo.byId(subscribeBtnId).disabled = true;
            dojo.byId(unSubscribeBtnId).disabled = true;
            return;
        }

        var selectedTopicName; 
        if (isPushTopic) {
            selectedTopicName = JSON.parse(topic).Name;
            if (selectedTopicName === null || selectedTopicName === "") {
                dojo.byId(subscribeBtnId).disabled = true;
                dojo.byId(unSubscribeBtnId).disabled = true;
                return;
            } else {
                selectedTopicName = "/topic/" + selectedTopicName;
            }        
        } else {
            selectedTopicName = topic;
        }

        for (subName in subscriptions) {
            if (selectedTopicName === subName && subscriptions[subName] !== undefined) {
                dojo.byId(subscribeBtnId).disabled = true;
                dojo.byId(unSubscribeBtnId).disabled = false;
                return;
            }
        }
        dojo.byId(subscribeBtnId).disabled = false;
        dojo.byId(unSubscribeBtnId).disabled = true;
    }

    function clearStream() {
        dojo.byId("streamBody").innerHTML = "";
    }

    function toggleReplayOptions() {
        if (isUsingStreamingV2()) {
            dojo.byId("replayFromContainer").style.display = "block";
        }
    }

    function isUsingStreamingV2() {
        return wbStreaming.streamingV2Enabled && wbStreaming.apiVersionInt >= 37;
    }

    function postTopicDml(dmlAction) {
        dojo.byId("pushTopicSaveBtn").disabled = true;
        dojo.byId("pushTopicDeleteBtn").disabled = true;
        dojo.byId("waitingIndicator").style.display = "inline";

        var unsaved = {
            Id: dojo.byId("pushTopicDmlForm_Id").value,
            Name: dojo.byId("pushTopicDmlForm_Name").value,
            ApiVersion: dojo.byId("pushTopicDmlForm_ApiVersion").value,
            Query: dojo.byId("pushTopicDmlForm_Query").value
        };

        dojo.xhrPost({
            form: "pushTopicDmlForm",
            handleAs: "json",
            content: {"PUSH_TOPIC_DML":dmlAction, "CSRF_TOKEN":wbStreaming.csrfToken},
            load: function(content) {
                dojo.byId("pushTopicSaveBtn").disabled = false;
                dojo.byId("pushTopicDeleteBtn").disabled = false;
                dojo.byId("waitingIndicator").style.display = "none";
                dojo.byId("messages").innerHTML = content.messages;
                dojo.byId("messages").style.display = "block";
                dojo.byId("selectedTopic").innerHTML = content.pushTopicOptions;

                copySelectedTopic();
                if (content.failed) {
                    copyDetails(unsaved, true);
                }
                toggleSubUnSubButtons();
            },
            error: function (error) {
                dojo.byId("pushTopicSaveBtn").disabled = false;
                dojo.byId("pushTopicDeleteBtn").disabled = false;
                dojo.byId("waitingIndicator").style.display = "none";
                dojo.byId("messages").innerHTML = "<div style='color:red;' class='displayErrors'>" + error + "</div>";
                dojo.byId("messages").style.display = "block";
            }
        });
    }
	
    function bindEvent(el, eventName, eventHandler) {
        if (el.addEventListener){
            el.addEventListener(eventName, eventHandler, false);
        } else if (el.attachEvent){
            el.attachEvent("on"+eventName, eventHandler);
        }
    } 

    // INITIALIZATION

    setStatus("Initializing");
    copySelectedTopic();
    toggleSubUnSubButtons();
    toggleReplayOptions();

    // config CometD -- this gets passed in from the controller via streaming.php
    cometd.configure(wbStreaming.cometdConfig);

    // Add various listeners
    cometd.onListenerException = listenerExceptionHandler;

    dojo.addOnUnload(disconnect);
    bindEvent(dojo.byId("subscriptionTypeSelectPushTopic"), "click", function() { selectSubscriptionType("pushTopic") });
    bindEvent(dojo.byId("subscriptionTypeSelectGenericSubscription"), "click", function() { selectSubscriptionType("genericSubscription") });
    bindEvent(dojo.byId("selectedTopic"), "change", copySelectedTopic);
    bindEvent(dojo.byId("selectedTopic"), "change", hideMessages);
    bindEvent(dojo.byId("selectedTopic"), "change", toggleSubUnSubButtons);
    bindEvent(dojo.byId("pushTopicSubscribeBtn"), "click", hideMessages);
    bindEvent(dojo.byId("pushTopicUnsubscribeBtn"), "click", hideMessages);
    bindEvent(dojo.byId("genericSubscribeBtn"), "click", subscribeGeneric);
    bindEvent(dojo.byId("genericUnsubscribeBtn"), "click", unsubscribeGeneric);
    bindEvent(dojo.byId("pushTopicSubscribeBtn"), "click", subcribePushTopic);
    bindEvent(dojo.byId("pushTopicUnsubscribeBtn"), "click", unsubscribePushTopic);
    bindEvent(dojo.byId("pushTopicDetailsBtn"), "click", togglePushTopicDmlContainer);
    bindEvent(dojo.byId("toggleShowPolling"), "click", toggleShowPolling);
    bindEvent(dojo.byId("clearStream"), "click", clearStream);
    bindEvent(dojo.byId("pushTopicSaveBtn"), "click", function() { postTopicDml("SAVE"); });
    bindEvent(dojo.byId("pushTopicDeleteBtn"), "click", function() { postTopicDml("DELETE"); });

    cometd.addListener("/meta/unsuccessful", metaUnsuccessful);
    cometd.addListener("/meta/handshake", metaHandshake);
    cometd.addListener("/meta/connect", metaConnect);
    cometd.addListener("/meta/subscribe", metaSubscribe);
    cometd.addListener("/meta/unsubscribe", metaUnsubscribe);
    cometd.addListener("/meta/*", metaAny);

    setStatus("Initialized");

    if (wbStreaming.handshakeOnLoad) {
        handshake();
    }
});
