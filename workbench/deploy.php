<?php
require_once ('soapclient/SforceMetadataClient.php');
require_once ('session.php');
require_once ('shared.php');
if(!apiVersionIsAtLeast(10.0)) {
	show_error("Metadata API not supported prior to version 10.0", true, true);
	exit;
}

if(isset($_POST['deploymentConfirmed'])) {
  	if(!isset($_SESSION[$_POST["deployFileTmpName"]])) {
  		show_error("No zip file currently staged for deployment. To re-deploy, create a new deploy request.", true, true);
  		exit;
  	}

  	if(!isset($_SESSION[$_POST["deployFileTmpName"] . "_OPTIONS"])) {
  		show_error("Error loading deploy options. To re-deploy, create a new deploy request.", true, true);
  		exit;
  	}
  	
	global $metadataConnection;
	try {
		$deployAsyncResults = $metadataConnection->deploy($_SESSION[$_POST["deployFileTmpName"]], $_SESSION[$_POST["deployFileTmpName"] . "_OPTIONS"]);
		$_SESSION[$_POST["deployFileTmpName"]] = null;
		$_SESSION[$_POST["deployFileTmpName"] . "_OPTIONS"] = null;
		
		if(!isset($deployAsyncResults->id)){
			show_error("Unknown deployment error.\n" . isset($deployAsyncResults->message) ? $deployAsyncResults->message : "", true, true);
			exit;
		}
		
		header("Location: metadataStatus.php?asyncProcessId=" . $deployAsyncResults->id);
	} catch (Exception $e) {
		show_error($e->getMessage(), true, true);
		exit;
	}
} 

else if(isset($_POST['stageForDeployment'])) {
  	$validationErrors = validateZipFile($_FILES["deployFile"]);
  	if($validationErrors) {
  		show_error($validationErrors, true, true);
  		exit;
  	}
  	
  	$deployFileContents = file_get_contents( $_FILES["deployFile"]["tmp_name"]);
  	if(!$deployFileContents) {
  		show_error("Unknown error reading file contents.", true, true);
  		exit;
  	}
  	$_SESSION[$_FILES["deployFile"]["tmp_name"]] = $deployFileContents;
  	$_SESSION[$_FILES["deployFile"]["tmp_name"] . "_OPTIONS"] = deserializeDeployOptions($_POST);
  	
  	require_once('header.php');
	show_info("Successfully staged " . ceil(($_FILES["deployFile"]["size"] / 1024)) . " KB zip file " . $_FILES["deployFile"]["name"] . " for deployment.", true, false);	
	
	?>
	<p class='instructions'>Confirm the following deployment options:</p>
	<form id='deployForm' name='deployForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>'>
		<input type='hidden' name='deployFileTmpName' value='<?php print $_FILES["deployFile"]["tmp_name"]; ?>' />
		<p/>
		<?php printDeployOptions($_SESSION[$_FILES["deployFile"]["tmp_name"] . "_OPTIONS"], false); ?>
		<p/>
		<?php
		if(!isset($_POST['checkOnly'])) {
			show_warnings("Warning, this deployment will make permanent changes to this organization's metadata and cannot be rolled back. " .
						  "Use the 'Check Only' option to validate this deployment without making changes.");
			print "<p/>";
		}
		?>
		<input type='submit' name='deploymentConfirmed' value='Deploy' /> 		
	</form>
	<?php	
} 

else {
	require_once('header.php');
	?>
	<p class='instructions'>Choose a file to deploy and select options:</p>
	<form id='deployForm' name='deployForm' method='POST' action='<?php print $_SERVER['PHP_SELF'] ?>' enctype='multipart/form-data'>
		<input type='file' name='deployFile' size='44' />
		<p/>
		<?php printDeployOptions(new DeployOptions(), true); ?>
		<p/>
		<input type='submit' name='stageForDeployment' value='Stage for Deployment' /> 
	</form>
	<?php
}

include_once('footer.php');
exit;




function deserializeDeployOptions($request) {	
	$deployOptions = new DeployOptions();
	
	foreach($deployOptions as $optionName => $optionValue) {
		if(is_bool($optionValue)) {
			$deployOptions->$optionName = isset($request[$optionName]);
		} else if(is_array($optionValue)) {
			$deployOptions->$optionName = isset($request[$optionName]) ? explode(",", $request[$optionName]) : array();
		}
	}	

	return $deployOptions;
}

function printDeployOptions($deployOptions, $editable) {
	print "<table>\n";
	foreach($deployOptions as $optionName => $optionValue) {
		print "<tr><td style='text-align: right; padding-right: 2em; padding-bottom: 0.5em;'>" . 
		      "<label for='$optionName'>" . unCamelCase($optionName) . "</label></td><td>";
		if(is_bool($optionValue)) {
			print "<input id='$optionName' type='checkbox' name='$optionName' " . (isset($optionValue) && $optionValue ? "checked='checked'" : "")  . " " . ($editable ? "" : "disabled='disabled'")  . "/>";
		} else if(is_array($optionValue)) {
			print "<input id='$optionName' type='text' name='$optionName' value='" . implode(",", $optionValue) . "'" . " " . ($editable ? "" : "disabled='disabled'")  . "/>";
		}
		print "</td></tr>\n";
	}
	print "</table>\n";
}

function validateZipFile($file){
	$validationResult = validateUploadedFile($file);

	if(!isset($file["tmp_name"]) || $file["tmp_name"] == "") {
		return("No file uploaded for deployment.");
	}
	
	if(!stristr($file['type'],'zip') || !stristr($file['name'],'.zip')) {
		return("The file uploaded is not a valid ZIP file. Please try again.");
	}
	
	return $validationResult;
}


?>