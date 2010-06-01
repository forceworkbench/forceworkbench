<?php
require_once ('session.php');
require_once ('shared.php');
require_once ('header.php');
?>	
<script type="text/javascript" src="script/simpletreemenu.js">
/***********************************************
* Simple Tree Menu - Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/
</script>
<link rel="stylesheet" type="text/css" href="style/simpletree.css" />
<?php

$sessionInfo = array();
global $mySforceConnection;
$sessionInfo['Connection'] = array(
	'API Version' => getApiVersion(),
	'Client Id' => isset($_SESSION['tempClientId']) ? $_SESSION['tempClientId'] : $_SESSION['config']['callOptions_client'], 
	'Endpoint' => $mySforceConnection->getLocation(),
	'Session Id' => $mySforceConnection->getSessionId(), 
);

foreach($mySforceConnection->getUserInfo() as $uiKey => $uiValue) {
	if(stripos($uiKey,'org') !== 0) {
		$sessionInfo['User'][$uiKey] = $uiValue;
	} else {
		$sessionInfo['Organization'][$uiKey] = $uiValue;		
	}
}

print "<p/>" .
      "<a href=\"javascript:ddtreemenu.flatten('sessionInfoTree', 'expand')\">Expand All</a> | <a href=\"javascript:ddtreemenu.flatten('sessionInfoTree', 'collapse')\">Collapse All</a>\n" .
      "<ul id='sessionInfoTree' class='treeview'>\n";

function printNode($node) {
	foreach($node as $nodeKey => $nodeValue) {
		if(is_array($nodeValue)){
			print "<li>$nodeKey<ul>\n";
			printNode($nodeValue);
			print "</ul></li>\n";
		} else {
			if(is_bool($nodeValue)) {
				$nodeValue = $nodeValue == 1 ? "true" : "false";
			}
			print "<li>$nodeKey: <span style='font-weight:bold;'>" . addLinksToUiForIds($nodeValue) . "</span></li>\n";
		}
	}
	
}

printNode($sessionInfo);

require_once ('footer.php');
?>
<script type="text/javascript">
ddtreemenu.createTree("sessionInfoTree", true);
//ddtreemenu.flatten('sessionInfoTree', 'expand');
</script>