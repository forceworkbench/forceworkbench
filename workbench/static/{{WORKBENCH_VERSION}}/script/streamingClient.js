dojo.require("dojox.cometd");

dojo.addOnLoad(function() {
    var cometd = dojox.cometd;
    var isConnected = false;
    var showPolling = false;
    var subscriptions = new Array();

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
        }
        else if (wasConnected && !isConnected) {
            postToStream("Connection Broken", message);
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
    }

    function metaSubscribe(message) {
        if (message.successful != true) {
            postErrorToStream("Subscription Failure: " + message.error, message);
        } else {
            postToStream("Subscribed to " + message.subscription, message);
        }
    }

    function metaUnsubscribe(message) {
        if (message.successful != true) {
            postErrorToStream("Unsubscription Failure: " + message.error, message);
        } else {
            postToStream("Unsubscribed from " + message.subscription, message);
        }
    }

    function metaUnsuccessful(message) {
        postErrorToStream("Unknown Failure", message);
    }


    function handleSubscription(message) {
        postToStream("Message received from: " + message.channel, message);
    }

    function printObject(obj, maxDepth, prefix) {
        var result = '';
        if (!prefix) prefix = '';
        for (var key in obj) {
            if (typeof obj[key] == 'object') {
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

    function hideMessages() {
        dojo.byId("messages").style.display = "none";
    }

    function copyDetails() {
        var details = dojo.byId('selectedTopic').value;

        if (details === null || details === "") {
            return;
        }

        details = JSON.parse(details);

        if (details.Name === null) {
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
        }
    }

    function subscribe() {
        var topic = dojo.byId('selectedTopic').value;

        if (topic === null || topic === "") {
            alert("Choose a topic to subscribe or create a new one.");
            return;
        }

        var topicName = JSON.parse(topic).Name;

        if (topicName === null || topicName === "") {
            alert("Choose a topic to subscribe or create a new one.");
            return;
        }

        subscriptions[topicName] = cometd.subscribe("/" + topicName, handleSubscription);

    }

    function unsubscribe() {
        var topic = dojo.byId('selectedTopic').value;

        if (topic === null || topic === "") {
            alert("Choose a topic to unsubscribe or create a new one.");
            return;
        }

        var topicName = JSON.parse(topic).Name;

        if (topicName === null || topicName === "") {
            alert("Choose a topic to unsubscribe or create a new one.");
            return;
        }

        if (subscriptions[topicName] === undefined) {
            alert("Cannot unsubscribe without first subscribing.");
            return;
        }

        cometd.unsubscribe(subscriptions[topicName]);
        subscriptions[topicName] = undefined;
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


    // INITIALIZATION

    setStatus("Initialing");

    copyDetails();

    var cometURL = location.protocol + "//" + location.host + streamingConfig.contextPath + "/cometd";
    cometd.configure({
         url: cometURL
//        ,logLevel: 'debug'
    });


    // Add various listeners

    cometd.onListenerException = listenerExceptionHandler;

    dojo.addOnUnload(disconnect);
    dojo.byId("selectedTopic").addEventListener("change", copyDetails, false);
    dojo.byId("selectedTopic").addEventListener("change", hideMessages, false);
    dojo.byId("pushTopicSubscribeBtn").addEventListener("click", hideMessages, false);
    dojo.byId("pushTopicDetailsBtn").addEventListener("click", togglePushTopicDmlContainer, false);
    dojo.byId("toggleShowPolling").addEventListener("click", toggleShowPolling, false);
    dojo.byId('pushTopicSubscribeBtn').addEventListener('click', subscribe, false);
    dojo.byId('pushTopicUnsubscribeBtn').addEventListener('click', unsubscribe, false);

    cometd.addListener('/meta/unsuccessful', metaUnsuccessful);
    cometd.addListener('/meta/handshake', metaHandshake);
    cometd.addListener('/meta/connect', metaConnect);
    cometd.addListener('/meta/subscribe', metaSubscribe);
    cometd.addListener('/meta/unsubscribe', metaUnsubscribe);

    cometd.handshake();
    setStatus("Handshaking");
});
