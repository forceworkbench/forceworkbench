dojo.require("dojox.cometd");

dojo.addOnLoad(function() {
    var cometd = dojox.cometd;
    var isConnected = false;
    var showPolling = false;
    var subscriptions = new Array();


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

        subscriptions = new Array();
        toggleSubUnSubButtons();
    }

    function metaSubscribe(message) {
        if (message.successful === true) {
            postToStream("Subscribed to " + message.subscription, message);
        } else {
            postErrorToStream("Subscription Failure: " + message.error, message);
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
                ppJson = ppJson.replace(/\n/g, "<br/>");
                ppJson = ppJson.replace(/\t/g, "&nbsp;&nbsp;");
                return ppJson;
            } catch (e) {
                window.console.debug("Problem writing to JSON. Falling back to old printer.");
            }
        }

        var result = '';
        if (!prefix) prefix = '';
        for (var key in obj) {
            var objType;
            try {
                // this can fail on XHR and who knows what else. try, log, and keep moving...
                objType = typeof obj[key];
            } catch (e) {
                window.console.debug("Problem finding typeof " + obj + " at key [" + key + "]");
                continue;
            }

            if (objType == 'object') {
                if (maxDepth !== undefined && maxDepth <= 1) {
                    result += (prefix + key + '=object [max depth reached]\n');
                } else {
                    result += printObject(obj[key], (maxDepth) ? maxDepth - 1 : maxDepth, prefix + key + '.');
                }
            } else {
                result += (prefix + key + '=' + obj[key] + '<br/>');
            }
        }
        return result;
    }

    function togglePushTopicDmlContainer_Internal(forceShow) {
        var cont = dojo.byId("pushTopicDmlContainer");
        if (cont.style.display != "block" || forceShow) {
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
            setTimeout(function() {dojo.byId("pollIndicator").style.display = 'none'}, 500);
    }

    function hideMessages() {
        dojo.byId("messages").style.display = "none";
    }

    function copySelectedTopic() {
        var details = dojo.byId('selectedTopic').value;

        if (details === null || details === "") {
            return;
        }

        details = JSON.parse(details);
        copyDetails(details, details.Name === null);
    }

    function copyPartiallySavedTopic() {
        var pst = dojo.byId('partialSavedTopic').innerHTML;
        if (pst === undefined || pst === null || pst === "") {
            return;
        }

        var details = JSON.parse(pst);
        copyDetails(details, true);
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

        dojo.byId('streamBody').innerHTML = '<div class=' + msgClass + '>' +
                                                '<span class=\'heading\'>' + heading + '</span><br/>' +
                                                '<span class=\'body\'>' + printObject(message) + '</span>' +
                                            '</div>' +
                                            dojo.byId('streamBody').innerHTML;
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

    function subscribe() {
        var topic = dojo.byId('selectedTopic').value;
        var topicName = JSON.parse(topic).Name;
        subscriptions[topicName] = cometd.subscribe("/" + topicName, handleSubscription);
        toggleSubUnSubButtons();
    }

    function unsubscribe() {
        var topic = dojo.byId('selectedTopic').value;
        var topicName = JSON.parse(topic).Name;
        cometd.unsubscribe(subscriptions[topicName]);
        subscriptions[topicName] = undefined;
        toggleSubUnSubButtons();
    }

    function disconnect() {
        setStatus("Disconnecting");
        cometd.disconnect(true);
    }

    function toggleShowPolling() {
        showPolling = !showPolling;
        dojo.byId("toggleShowPolling").value = showPolling ? "Hide Polling" : "Show Polling";
    }

    function togglePushTopicDmlContainer() {
        togglePushTopicDmlContainer_Internal(false);
    }

    function toggleSubUnSubButtons() {
        var topic = dojo.byId('selectedTopic').value;

        if (topic === null || topic === "") {
            dojo.byId("pushTopicSubscribeBtn").disabled = true;
            dojo.byId("pushTopicUnsubscribeBtn").disabled = true;
            return;
        }

        var selectedTopicName = JSON.parse(topic).Name;

        if (selectedTopicName === null || selectedTopicName === "") {
            dojo.byId("pushTopicSubscribeBtn").disabled = true;
            dojo.byId("pushTopicUnsubscribeBtn").disabled = true;
            return;
        }

        for (var subName in subscriptions) {
            if (selectedTopicName === subName && subscriptions[subName] !== undefined) {
                console.log("Found subscription to " + subName);
                dojo.byId("pushTopicSubscribeBtn").disabled = true;
                dojo.byId("pushTopicUnsubscribeBtn").disabled = false;
                return;
            }
        }
        dojo.byId("pushTopicSubscribeBtn").disabled = false;
        dojo.byId("pushTopicUnsubscribeBtn").disabled = true;
    }

    function clearStream() {
        dojo.byId('streamBody').innerHTML = "";
    }

    function displayWaitingIndicator() {
        dojo.byId('waitingIndicator').style.display = 'inline';
    }

    // INITIALIZATION

    setStatus("Initializing");
    copyPartiallySavedTopic();
    copySelectedTopic();
    toggleSubUnSubButtons();

    // config CometD -- this gets passed in from the controller via streaming.php
    cometd.configure(wbStreaming.cometdConfig);

    // Add various listeners
    cometd.onListenerException = listenerExceptionHandler;

    dojo.addOnUnload(disconnect);
    dojo.byId("selectedTopic").addEventListener("change", copySelectedTopic, false);
    dojo.byId("selectedTopic").addEventListener("change", hideMessages, false);
    dojo.byId("selectedTopic").addEventListener("change", toggleSubUnSubButtons, false);
    dojo.byId("pushTopicSubscribeBtn").addEventListener("click", hideMessages, false);
    dojo.byId("pushTopicUnsubscribeBtn").addEventListener("click", hideMessages, false);
    dojo.byId('pushTopicSubscribeBtn').addEventListener('click', subscribe, false);
    dojo.byId('pushTopicUnsubscribeBtn').addEventListener('click', unsubscribe, false);
    dojo.byId("pushTopicDetailsBtn").addEventListener("click", togglePushTopicDmlContainer, false);
    dojo.byId("toggleShowPolling").addEventListener("click", toggleShowPolling, false);
    dojo.byId('clearStream').addEventListener('click', clearStream, false);
    dojo.byId('pushTopicSaveBtn').addEventListener('click', displayWaitingIndicator, false);
    dojo.byId('pushTopicDeleteBtn').addEventListener('click', displayWaitingIndicator, false);

    cometd.addListener('/meta/unsuccessful', metaUnsuccessful);
    cometd.addListener('/meta/handshake', metaHandshake);
    cometd.addListener('/meta/connect', metaConnect);
    cometd.addListener('/meta/subscribe', metaSubscribe);
    cometd.addListener('/meta/unsubscribe', metaUnsubscribe);
    cometd.addListener('/meta/*', metaAny);

    setStatus("Initialized");

    if (wbStreaming.handshakeOnLoad) {
        setStatus("Handshaking");
        cometd.handshake();
    }
});
