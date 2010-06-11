<?php
require_once ('soapclient/SforceMetadataClient.php');
require_once ('session.php');
require_once ('shared.php');
if(!apiVersionIsAtLeast(10.0)) {
	show_error("Metadata API not supported prior to version 10.0", true, true);
	exit;
}

if(isset($_POST['retrievalConfirmed']) && isset($_POST["retrieveRequestId"])) {
	$retrieveRequestId = htmlentities($_POST["retrieveRequestId"]);
	
  	if(!isset($_SESSION[$retrieveRequestId])) {
  		show_error("No retrieve request found. To re-retrieve, create a new retrieve request.", true, true);
  		exit;
  	}
  		
	global $metadataConnection;
	try {
		$retrieveAsyncResults = $metadataConnection->retrieve($_SESSION[$retrieveRequestId]);
		
		if(!isset($retrieveAsyncResults->id)){
			show_error("Unknown retrieval error.\n" . isset($retrieveAsyncResults->message) ? $retrieveAsyncResults->message : "", true, true);
			exit;
		}

		unset($_SESSION[$retrieveRequestId]);
		header("Location: metadataStatus.php?asyncProcessId=" . $retrieveAsyncResults->id . "&op=R");
	} catch (Exception $e) {
		show_error($e->getMessage(), true, true);
		exit;
	}
} 

else if(isset($_POST['stageForRetrieval'])) {
	
	if(isset($_FILES["packageXmlFile"]["name"]) && $_FILES["packageXmlFile"]["name"] != "" && isset($_POST['packageNames']) && $_POST['packageNames'] != "") {
		show_error("Only specify an unpackaged manifest OR a package name, not both.", true, true);
		exit;		
	}
	
	$retrieveRequest = new RetrieveRequest();
	$retrieveRequest->apiVersion = getApiVersion(); 
	
	if(isset($_FILES["packageXmlFile"]["name"]) && $_FILES["packageXmlFile"]["name"] != "") {	
		$validationErrors = validateUploadedFile($_FILES["packageXmlFile"]);
	  	if($validationErrors) {
	  		show_error($validationErrors, true, true);
	  		exit;
	  	}
	  	
		if((!stristr($_FILES["packageXmlFile"]['type'],'octet-stream') && !stristr($_FILES["packageXmlFile"]['type'],'xml')) || !stristr($_FILES["packageXmlFile"]['name'],'.xml')) {
			show_error("The file uploaded is not a valid XML file. Please try again.", true, true);
			exit;
		}
	
		$retrieveRequest->unpackaged = parseUnpackagedManifest($_FILES["packageXmlFile"]["tmp_name"]);
		$retrieveRequest->singlePackage = true;
		
	} else if(isset($_POST['packageNames']) && $_POST['packageNames'] != "") {
		$explodedPackageNames = explode(",", htmlentities($_POST['packageNames']));
		foreach($explodedPackageNames as $packageKey => $packageValue) {
			$explodedPackageNames[$packageKey] = trim($packageValue);
		}
		$retrieveRequest->packageNames = $explodedPackageNames;
		
		$retrieveRequest->singlePackage = count($retrieveRequest->packageNames) <= 1;
	} else {
		show_error("Unknown error building retrieve request.", true, true);
		exit;		
	}
  	
  	$retrieveRequestId = "RR-" . time();
  	$_SESSION[$retrieveRequestId] = $retrieveRequest;
  	
  	require_once('header.php');
	show_info("Successfully parsed staged retrieve request.");
	?>
	<p class='instructions'>Confirm the following retrieve request:</p>
	<?php printTree("retrieveRequestTree", processResults($_SESSION[$retrieveRequestId]), true); ?>
	<form id='retrieveForm' name='retrieveForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>'>
		<input type='hidden' name='retrieveRequestId' value='<?php print $retrieveRequestId; ?>' />
		<input type='submit' name='retrievalConfirmed' value='Retrieve' /> 		
	</form>
	<?php	
} 

else {
	require_once('header.php');
	?>
	<p class='instructions'>Choose either an unpackaged manifest file (i.e. 'package.xml') or comma-separated list of package names to define a retrieve request:</p>
	<form id='retrieveForm' name='retrieveForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>' enctype='multipart/form-data'>
		<input type='hidden' name='MAX_FILE_SIZE' value='<?php print $_SESSION['config']['maxFileSize']; ?>' />
		<table>
		<tr>
			<td class='dataLabel'>Unpackaged Manifest:</td>
			<td><input id='packageXmlFile' type='file' name='packageXmlFile' size='44' onchange="toggleRequestInputs();" /></td>
			<td><img onmouseover="Tip('XML file defining types (name and members) and version to be retreived. See Salesforce.com Metadata API Developers guide for an example of a package.xml file.')" align='absmiddle' src='images/help16.png'/></td>
		</tr>
		<tr><td style='text-align:center;'><em>-OR-</em></td><td colspan='2'></td></tr>
		<tr>
			<td class='dataLabel'>Package Names:</td>
			<td><input id='packageNames' type='text' name='packageNames' size='44' onkeypress='toggleRequestInputs();'/></td>
			<td><img onmouseover="Tip('Comma separated list of package names to be retrieved.')" align='absmiddle' src='images/help16.png'/></td>
		</tr>
		<tr><td colspan='2'></td></tr>
		<tr>
			<td></td>
			<td colspan='2'><input type='submit' name='stageForRetrieval' value='Upload' /></td>
		</tr>
		</table>
	</form>
	<?php
}

include_once('footer.php');

function parseUnpackagedManifest($xmlFile) {
	libxml_use_internal_errors(true);
	$packageXml = simplexml_load_file($xmlFile);
  	if(!isset($packageXml) || !$packageXml) {
  		show_error(libxml_get_errors(), true, true);
  		libxml_clear_errors();
  		exit;
  	}
  	libxml_use_internal_errors(false);
  	
  	$unpackaged = new Package();
  	
  	if(isset($packageXml->version)) $unpackaged->version = (string) $packageXml->version;
  	
  	if(isset($packageXml->types)) {
	  	$unpackaged->types = array();
	  	foreach($packageXml->types as $typeXml) {
	  		$type = new PackageTypeMembers();
	  		if(isset($typeXml->name)) $type->name = (string) $typeXml->name;
	  		if(isset($typeXml->members)) {
	  			$type->members = array();
	  			foreach($typeXml->members as $memberXml) {
		  			$type->members[] = (string) $memberXml;
		  		}
		  		$unpackaged->types[] = $type;
	  		}
	  	}
  	}

  	unset($packageXml);
  	
  	return $unpackaged; 	
}
?>
<script>
function toggleRequestInputs(){
	var packageXmlFile = document.getElementById('packageXmlFile');
	var packageNames = document.getElementById('packageNames');
	
	if(packageXmlFile.value.length > 0){
		packageNames.disabled = true;
	}

	if(packageNames.value.length > 0){
		packageXmlFile.disabled = true;
	}
}
</script>