<?php
require_once ('soapclient/SforceMetadataClient.php');
require_once ('session.php');
require_once ('shared.php');
if(!apiVersionIsAtLeast(10.0)) {
	show_error("Metadata API not supported prior to version 10.0", true, true);
	exit;
}

if(isset($_POST['retrievalConfirmed'])) {
  	if(!isset($_SESSION[$_POST["retrieveRequestId"]])) {
  		show_error("No retrieve request found. To re-retrieve, create a new retrieve request.", true, true);
  		exit;
  	}
  		
	global $metadataConnection;
	try {
		$retrieveAsyncResults = $metadataConnection->retrieve($_SESSION[$_POST["retrieveRequestId"]]);
		$_SESSION[$_POST["retrieveRequestId"]] = null;
		
		if(!isset($retrieveAsyncResults->id)){
			show_error("Unknown retrieval error.\n" . isset($retrieveAsyncResults->message) ? $retrieveAsyncResults->message : "", true, true);
			exit;
		}

		header("Location: metadataStatus.php?asyncProcessId=" . $retrieveAsyncResults->id);
	} catch (Exception $e) {
		show_error($e->getMessage(), true, true);
		exit;
	}
} 

else if(isset($_POST['stageForRetrieval'])) {
	$validationErrors = validateUploadedFile($_FILES["packageXmlFile"]);
  	if($validationErrors) {
  		show_error($validationErrors, true, true);
  		exit;
  	}
  	
	if(!stristr($_FILES["packageXmlFile"]['type'],'xml') || !stristr($_FILES["packageXmlFile"]['name'],'.xml')) {
		show_error("The file uploaded is not a valid XML file. Please try again.", true, true);
		exit;
	}  	
  	
  	try {
  		$packageXml = new SimpleXMLElement(file_get_contents( $_FILES["packageXmlFile"]["tmp_name"]));
  	} catch (Exception $e) {
		show_error("Error parsing manifest file:\n" . $e->getMessage(), true, true);
		exit;
	}
  	
  	if(!$packageXml) {
  		show_error("Unknown error reading file contents.", true, true);
  		exit;
  	}
  	
  	$unpackaged = new Package();
  	$unpackaged->version = (string) $packageXml->version;
  	$unpackaged->types = array();
  	foreach($packageXml->types as $typeXml) {
  		$type = new PackageTypeMembers();
  		$type->name = (string) $typeXml->name;
  		$type->members = array();
  		foreach($typeXml->members as $memberXml) {
  			$type->members[] = (string) $memberXml;
  		}
  		$unpackaged->types[] = $type;
  	}

  	unset($packageXml);

  	
  	$retrieveRequest = new RetrieveRequest();
  	$retrieveRequest->apiVersion = getApiVersion(); 
  	$retrieveRequest->singlePackage = true;
  	$retrieveRequest->unpackaged = $unpackaged;
  	
  	$retrieveRequestId = "RR-" . time();
  	$_SESSION[$retrieveRequestId] = $retrieveRequest;
  	
  	require_once('header.php');
	show_info("Successfully parsed manifest file and staged retrieval.");
	?>
	<p class='instructions'>Confirm the following retrieve request:</p>
	<? printTree("retrieveRequestTree", processResults($_SESSION[$retrieveRequestId]), true); ?>
	<form id='retrieveForm' name='retrieveForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>'>
		<input type='hidden' name='retrieveRequestId' value='<?php print $retrieveRequestId; ?>' />
		<input type='submit' name='retrievalConfirmed' value='Retrieve' /> 		
	</form>
	<?php	
} 

else {
	require_once('header.php');
	?>
	<p class='instructions'>Choose a manifest file (i.e. 'package.xml') to define an unpackaged retrieve request:</p>
	<form id='retrieveForm' name='retrieveForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>' enctype='multipart/form-data'>
		<input type='hidden' name='MAX_FILE_SIZE' value='<?php print $_SESSION['config']['maxFileSize']; ?>' />
		<p><input type='file' name='packageXmlFile' size='44' /></p>
		<input type='submit' name='stageForRetrieval' value='Stage for Retrieval' /> 
	</form>
	<?php
}

include_once('footer.php');
?>