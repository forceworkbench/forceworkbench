<?php
require_once ('session.php');
require_once ('shared.php');
require_once('header.php');
print "<p/>";
if(!apiVersionIsAtLeast(10.0)) {
	show_error("Metadata API not supported prior to version 10.0", false, true);
	exit;
}

require_once ('soapclient/SforceMetadataClient.php');
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

global $metadataConnection;
try {
	$describeMetadataResult = $metadataConnection->describeMetadata(getApiVersion());
} catch (Exception $e) {
	show_errors($e->getMessage(), false, true);
}

$metadataComponentsSelectOptions[""] = "";
foreach($describeMetadataResult as $resultsKey => $resultsValue) {
	if($resultsKey == 'metadataObjects'){
		foreach($resultsValue as $metadataResultsKey => $metadataResultsValue) {
			$metadataComponentMap[$metadataResultsValue->xmlName] = $metadataResultsValue;
			$metadataComponentsSelectOptions[$metadataResultsValue->xmlName]= $metadataResultsValue->xmlName;
			
			if(isset($metadataResultsValue->childXmlNames)) {
				if(!is_array($metadataResultsValue->childXmlNames)) {
					$metadataResultsValue->childXmlNames = array($metadataResultsValue->childXmlNames);
				}
				
				foreach ($metadataResultsValue->childXmlNames as $childName) {
					$metadataComponentsSelectOptions[$childName]= $childName;
					
					$childComponent = new stdClass();
					$childComponent->xmlName = $childName;
					$childComponent->inFolder = false;
					$metadataComponentMap[$childName] = $childComponent;
				}
			}
		}
	}
}

$metadataComponentsSelectOptions = natcaseksort($metadataComponentsSelectOptions);

?>

<form id="metadataComponentSelectionForm" name="metadataComponentSelectionForm" method="GET" action="<?php print $_SERVER['PHP_SELF'] ?>">
<select id="metadataComponentSelector" name="metadataComponentSelector" onChange="document.metadataComponentSelectionForm.submit();">
<?php printSelectOptions($metadataComponentsSelectOptions, isset($_REQUEST['metadataComponentSelector']) ? $_REQUEST['metadataComponentSelector'] : null); ?>
</select>
</form>
<p/>

<?php
if(isset($_REQUEST['metadataComponentSelector'])) {
	if(!isset($metadataComponentMap[$_REQUEST['metadataComponentSelector']])) {
		show_error("Invalid metadata component type: " . $_REQUEST['metadataComponentSelector'], false, true);
		exit;
	}
	$component = $metadataComponentMap[$_REQUEST['metadataComponentSelector']];
	
	$listedMetadata = listMetadata($component);
	
	if(count($listedMetadata) == 0) {
		show_info("This metadata type contains no components.", false, true);
		exit;
	}

	print "<a href=\"javascript:ddtreemenu.flatten('listMetadataTree', 'expand')\">Expand All</a> | <a href=\"javascript:ddtreemenu.flatten('listMetadataTree', 'collapse')\">Collapse All</a>\n" .
	      "<ul id='listMetadataTree' class='treeview'>\n";
	printNode($listedMetadata);
}

require_once('footer.php');
?>
<script type="text/javascript">
ddtreemenu.createTree("listMetadataTree", true);
</script>

<?php
function listMetadata($component) {
	global $metadataConnection;
	global $partnerConnection;
	
	try {
		if(!$component->inFolder) {
			return processListMetadataResult($metadataConnection->listMetadata($component->xmlName, null, getApiVersion()));
		}
		
		$folderQueryResult = $partnerConnection->query("SELECT DeveloperName FROM Folder WHERE Type = '" . $component->xmlName . "' AND DeveloperName != null AND NamespacePrefix = null");
		
		if($folderQueryResult->size == 0) {
			return array();
		}
		
		foreach($folderQueryResult->records as $folderRecord) {
			$folder = new SObject($folderRecord);
			$folderName = $folder->fields->DeveloperName;
			
			$listMetadataResult["$folderName"] = processListMetadataResult($metadataConnection->listMetadata($component->xmlName, $folder->fields->DeveloperName, getApiVersion()));
		}
		
		return $listMetadataResult;
	} catch (Exception $e) {
		show_error($e->getMessage(), false, true);
	}	
}

function processListMetadataResult($response) {
	if(!is_array($response)) {
		$response = array($response);
	}
		
	$processedResponse = array();
	foreach($response as $responseKey => $responseValue) {
		$fullName = ($responseValue != null) ? strrchr($responseValue->fullName, "/") ? substr(strrchr($responseValue->fullName, "/"), 1) : $responseValue->fullName : "";
		if(strpos($responseValue->fullName, ".")) {
			$parentName = substr($responseValue->fullName, 0, strpos($responseValue->fullName, "."));
			$childName = substr($responseValue->fullName, strpos($responseValue->fullName, ".") + 1);
			$processedResponse[$parentName][$childName] = $responseValue;
		} else {
			$processedResponse[$fullName] = $responseValue;
		}
	}
	$processedResponse = natcaseksort($processedResponse);
	
	return $processedResponse;
}
?>