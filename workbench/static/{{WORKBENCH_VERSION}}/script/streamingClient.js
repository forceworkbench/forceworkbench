dojo.require("dojox.cometd");

var topicName = null;

dojo.addOnLoad(function() {
    var cometd = dojox.cometd;
    var _connected = false;
    var subscriptions = new Array();

    function _metaConnect(message) {
        if (cometd.isDisconnected()) {
            _connected = false;
            postToStream("Connection Closed", message);
            return;
        }

        var wasConnected = _connected;
        _connected = message.successful === true;
        if (!wasConnected && _connected) {
            postToStream("Connection Established", message);
        }
        else if (wasConnected && !_connected) {
            postToStream("Connection Broken", message);
        } else {
            postToStream("Poll Completed", message);
        }
    }

    // Function invoked when first contacting the server and
    // when the server has lost the state of this client
    function _metaHandshake(message) {
        if (message.successful === true) {
            postToStream("Handshake Successful", message);
        } else {
            postErrorToStream("Handshake Failure", message);
        }
    }

    function _metaSubscribe(message) {
        if (message.successful != true) {
            postErrorToStream("Subscription Failure: " + message.error, message);
        } else {
            postToStream("Subscribed to " + message.subscription, message);
        }
    }

    function _metaUnsubscribe(message) {
        if (message.successful != true) {
            postErrorToStream("Unsubscription Failure: " + message.error, message);
        } else {
            postToStream("Unsubscribed from " + message.subscription, message);
        }
    }

    function _metaUnsuccessful(message) {
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

    function togglePushTopicDmlContainer(forceShow) {
        var cont = dojo.byId("pushTopicDmlContainer");
        if (cont.style.display != "block" || forceShow) {
            cont.style.display = "block";
        } else {
            cont.style.display = "none";
        }
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
            togglePushTopicDmlContainer(true);
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

    // Disconnect when the page unloads
    dojo.addOnUnload(function() {
        cometd.disconnect(true);
    });

    var cometURL = location.protocol + "//" + location.host + streamingConfig.contextPath + "/cometd";
    cometd.configure({
        url: cometURL,
        logLevel: 'debug'
    });

    copyDetails();

    cometd.onListenerException = function(exception, subscriptionHandle, isListener, message) {
        postErrorToStream("Unknown exception occurred. Removing offending listener or subscription.", [message, exception]);

        if (isListener) {
            this.removeListener(subscriptionHandle);
        } else {
            this.unsubscribe(subscriptionHandle);
        }
    }

    cometd.addListener('/meta/unsuccessful', _metaUnsuccessful);
    cometd.addListener('/meta/handshake', _metaHandshake);
    cometd.addListener('/meta/connect', _metaConnect);
    cometd.addListener('/meta/subscribe', _metaSubscribe);
    cometd.addListener('/meta/unsubscribe', _metaUnsubscribe);
    cometd.handshake();

    // Copy the details of the selected topic into details section
    dojo.byId("selectedTopic").addEventListener("change", copyDetails, false);

    // Hide the error/info messages when user does another action
    dojo.byId("selectedTopic").addEventListener("change", hideMessages, false);
    dojo.byId("pushTopicSubscribeBtn").addEventListener("click", hideMessages, false);

    // Toggle the details section
    dojo.byId("pushTopicDetailsBtn").addEventListener("click", function() {
        togglePushTopicDmlContainer(false);
    }, false);

    // Subscribe to the given topic
    dojo.byId('pushTopicSubscribeBtn').addEventListener('click', function() {
        topicName = JSON.parse(dojo.byId('selectedTopic').value).Name;

        if (topicName === null || topicName === "") {
            alert("Choose a topic to subscribe or create a new one.");
            return;
        }

        subscriptions[topicName] = cometd.subscribe("/" + topicName, handleSubscription);
        
    }, false);

    // Unsubscribe to the given topic
    dojo.byId('pushTopicUnsubscribeBtn').addEventListener('click', function() {
        topicName = JSON.parse(dojo.byId('selectedTopic').value).Name;

        if (topicName === null || topicName === "") {
            alert("Choose a topic to unsubscribe.");
            return;
        }

        if (subscriptions[topicName] === undefined) {
            alert("Cannot unsubscribe without first subscribing.");
            return;
        }

        cometd.unsubscribe(subscriptions[topicName]);
        subscriptions[topicName] = undefined;
    }, false);
});
