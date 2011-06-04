dojo.require("dojox.cometd");

var topicName = null;

dojo.addOnLoad(function()
{
    var cometd = dojox.cometd;

    function _connectionEstablished()
    {
        dojo.byId('streamBody').innerHTML += '<div>CometD Connection Established</div>';
    }

    function _connectionBroken()
    {
        dojo.byId('streamBody').innerHTML += '<div>CometD Connection Broken</div>';
    }

    function _connectionClosed()
    {
        dojo.byId('streamBody').innerHTML += '<div>CometD Connection Closed</div>';
    }

    function _subscribed(subscription)
    {
        dojo.byId('streamBody').innerHTML += '<div>Subscribed to ' + subscription + '</div>';
    }

    function _error(messageStr)
    {
        dojo.byId('streamBody').innerHTML += '<div style=\'color:red;\'>' + messageStr + '</div>';
    }

    // Function that manages the connection status with the Bayeux server
    var _connected = false;
    function _metaConnect(message)
    {
        if (cometd.isDisconnected())
        {
            _connected = false;
            _connectionClosed();
            return;
        }

        var wasConnected = _connected;
        _connected = message.successful === true;
        if (!wasConnected && _connected)
        {
            _connectionEstablished();
        }
        else if (wasConnected && !_connected)
        {
            _connectionBroken();
        }
    }

    // Function invoked when first contacting the server and
    // when the server has lost the state of this client
    function _metaHandshake(handshake)
    {
        if (handshake.successful === true)
        {
//            cometd.subscribe(topicName, handleSubscription);
        }
    }

    function _metaSubscribe(message) {
        if (message.successful != true) {
            _error("Failure to subscribe to " + message.subscription + ":<br/>" + message.error);
            return;
        }

        _subscribed(message.subscription);
    }

    function handleSubscription(message) {
        dojo.byId('streamBody').innerHTML += '<div><em>Received from server:</em><br/>' + wbUtil.printObject(message) + '</div>';
    }

    // Disconnect when the page unloads
    dojo.addOnUnload(function()
    {
        cometd.disconnect(true);
    });

    var cometURL = location.protocol + "//" + location.host + streamingConfig.contextPath + "/cometd";
    cometd.configure({
        url: cometURL,
        logLevel: 'debug'
    });

    cometd.addListener('/meta/handshake', _metaHandshake);
    cometd.addListener('/meta/connect', _metaConnect);
    cometd.addListener('/meta/subscribe', _metaSubscribe);
    cometd.handshake();

    dojo.byId('subBtn').addEventListener('click', function() {
        topicName = dojo.byId('topicName').value;
        cometd.subscribe(topicName, handleSubscription);
    }, false);
});

var wbUtil = {


    printObject: function (obj, maxDepth, prefix) {
       var result = '';
       if (!prefix) prefix='';
       for(var key in obj){
           if (typeof obj[key] == 'object'){
               if (maxDepth !== undefined && maxDepth <= 1){
                   result += (prefix + key + '=object [max depth reached]\n');
               } else {
                   result += wbUtil.printObject(obj[key], (maxDepth) ? maxDepth - 1: maxDepth, prefix + key + '.');
               }
           } else {
               result += (prefix + key + '=' + obj[key] + '<br/>');
           }
       }
       return result;
    }
};
