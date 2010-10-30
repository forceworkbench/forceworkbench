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
			li.innerHTML = "<strong>" + getKeyLabel(key, nodes) + "</strong>";
			li.appendChild(buildList(nodes[key], document.createElement("ul")));
		} else {
			li.innerHTML = "<strong>" + key + ": </strong>";
			li.innerHTML += nodes[key];
		}
	
		parent.appendChild(li);
	}
	
	return parent;
}

function displayWaitingNotice() {
	document.getElementById('waitingNotice').style.display = 'inline';
   return true;
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
	       links[i].addEventListener("click", displayWaitingNotice, false);
	   }
   }
}

function toggleRequestBodyDisplay(radio) {
  if (radio.checked && (radio.value == 'POST' || radio.value == 'PATCH')) {
	  document.getElementById('requestBodyContainer').style.display = 'inline';
  } else {
	  document.getElementById('requestBodyContainer').style.display = 'none';
  }
}
