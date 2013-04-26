function resetUrl(apiVersion) {
    var versionSegment = apiVersion ? ('/v' + apiVersion) : '';
    document.getElementById("urlInput").value = '/services/data' + versionSegment;
};

function upUrl() {
	var urlInput = document.getElementById("urlInput");

	if (urlInput.value.indexOf('/services/') == 0) {
		var delims = ["&","?","/"];
		for (var i in delims) {
			if (urlInput.value.indexOf(delims[i]) > -1) {
				urlInput.value = urlInput.value.substring(0, urlInput.value.lastIndexOf(delims[i]));
				return;
			}
		}
	}

    if (urlInput.value.indexOf('/id/') == 0) {
        resetUrl();
    }
}

function checkEnter(e) { //e is event object passed from function invocation
	 var characterCode; //literal character code will be stored in this variable

	 if (e && e.which) { //if which property of event object is supported (NN4)
		 characterCode = e.which; //character code is contained in NN4's which property
	 } else {
		 characterCode = event.keyCode; //character code is contained in IE's keyCode property
	 }

	 if (characterCode == 13) { //if generated character code is equal to ascii 13 (if enter key)
		 return true;
	 } else {
		 return false;
	 }

 }

function isInt(value){
  if((parseFloat(value) == parseInt(value)) && !isNaN(parseInt(value))){
	  return true;
 } else {
	  return false;
 }
}

function getKeyLabel(key, nodes) {
	if (!isInt(key)) {
		return key;
	}

	// the following is a list of common values
	// in nodes, which can be used as the label
	// for the key label, if one is missing (i.e. isInt)
	var commonLabels = new Array("name",
								 "Name",
								 "relationshipName",
								 "value",
								 "label",
								 "Id",
								 "errorCode");

	for (var i in commonLabels) {
		if (nodes[key][commonLabels[i]]) {
			return nodes[key][commonLabels[i]];
		}
	}

	return "[Item " + (parseInt(key) + 1) + "]";
}

function buildList(nodes, parent) {
	for (var key in nodes) {
		var li = document.createElement("li");

		if (nodes[key] instanceof Object) {
			li.innerHTML = getKeyLabel(key, nodes);
			li.appendChild(buildList(nodes[key], document.createElement("ul")));
		} else {
			li.innerHTML = key + ": ";
			li.innerHTML += "<strong>" + nodes[key] + "</strong>"
		}

		parent.appendChild(li);
	}

	return parent;
}

function displayWaitingNotice() {
	document.getElementById('waitingNotice').style.display = 'inline';
   return true;
}

function bindEvent(el, eventName, eventHandler) {
    if (el.addEventListener){
        el.addEventListener(eventName, eventHandler, false);
    } else if (el.attachEvent){
        el.attachEvent("on"+eventName, eventHandler);
    }
}

function convert(jsonData) {
	var responseListContainer = document.getElementById('responseListContainer');
	responseListContainer.innerHTML = "";
	var responseList= document.createElement('ul');
	responseList.id = 'responseList';
	responseList.className = 'treeview';
	responseListContainer.appendChild(buildList(jsonData, responseList));
	ddtreemenu.createTree('responseList', false);
	ddtreemenu.flatten('responseList', 'contract');

    var links = document.getElementsByTagName("a");
    for (var i in links) {
        if (links[i].className != null && links[i].className.indexOf("RestLinkable") > -1) {
            bindEvent(links[i], "click", displayWaitingNotice);
        }
    }
}

function toggleRequestBodyDisplay(radio, hasBody) {
  if (radio.checked && hasBody) {
	  document.getElementById('requestBodyContainer').style.display = 'inline';
  } else {
	  document.getElementById('requestBodyContainer').style.display = 'none';
  }
}

function toggleRequestHeaders() {
	var container = document.getElementById('requestHeaderContainer');

	if (container.style.display == 'none') {
		container.style.display = 'block';
	} else {
		container.style.display = 'none';
	}
}

function toggleCodeViewPort() {
	var codeViewPort = document.getElementById('codeViewPortContainer');
	var codeViewPortToggler = document.getElementById('codeViewPortToggler');

	if (codeViewPort.style.display == 'none') {
		codeViewPort.style.display = 'block';
		codeViewPortToggler.innerHTML = 'Hide Raw Response';
	} else {
		codeViewPort.style.display = 'none';
		codeViewPortToggler.innerHTML = 'Show Raw Response';
	}
}