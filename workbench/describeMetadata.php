<?php
require_once ('session.php');
require_once ('shared.php');
require_once('header.php');
?>

<p/>
<p class='instructions'>Below are descriptions of the metadata types in this organization:</p>
<a href="javascript:ddtreemenu.flatten('describeMetadataTree', 'expand')">Expand All</a> | <a href="javascript:ddtreemenu.flatten('describeMetadataTree', 'collapse')">Collapse All</a>
<ul id='describeMetadataTree' class='treeview'>

<?php
if(!apiVersionIsAtLeast(10.0)) {
	show_error("Metadata API not supported prior to version 10.0", false, true);
	exit;
}

require_once ('soapclient/SforceMetadataClient.php');
global $metadataConnection;
try {
	$describeMetadataResult = $metadataConnection->describeMetadata(getApiVersion());
} catch (Exception $e) {
	show_error($e->getMessage(), false, true);
}

foreach($describeMetadataResult as $resultsKey => $resultsValue) {
	if($resultsKey == 'metadataObjects'){
		foreach($resultsValue as $metadataResultsKey => $metadataResultsValue) {			
			if(isset($metadataResultsValue->childXmlNames)) {
				if(!is_array($metadataResultsValue->childXmlNames)) {
					$metadataResultsValue->childXmlNames = array($metadataResultsValue->childXmlNames);
				}
				
				$processedChildNames = array();
				foreach ($metadataResultsValue->childXmlNames as $childName) {
					$processedChildNames[] = "$childName <a href='listMetadata.php?type=$childName' class='miniLink'>[LIST]</a>";
				}
				$metadataResultsValue->childXmlNames = $processedChildNames;
			}
			
			$typeName = $metadataResultsValue->xmlName . " <a href='listMetadata.php?type=$metadataResultsValue->xmlName' class='miniLink'>[LIST]</a>";
			
			$processedMetadataDescribe[$typeName] = $metadataResultsValue;
		}
	}
}

$processedMetadataDescribe = natcaseksort($processedMetadataDescribe);
printNode($processedMetadataDescribe);
require_once('footer.php');
?>
<script type="text/javascript">
var treeid = "describeMetadataTree";
ddtreemenu.createTree("describeMetadataTree", true);

<?php if(isset($_REQUEST['type'])) { ?>
ddtreemenu.flatten(treeid, 'collapse');
var ultags=document.getElementById(treeid).getElementsByTagName("ul");
for (var i=0; i<ultags.length; i++){
	if(ultags[i].parentNode.innerHTML.indexOf('<?php print $_REQUEST['type'] ?> ') > -1) {
		ddtreemenu.expandSubTree(treeid, ultags[i]);
	}
}
<?php } ?>

</script>