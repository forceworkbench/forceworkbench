<?php
require_once ('session.php');
require_once ('shared.php');
require_once('header.php');
print "<p/>";
if(!apiVersionIsAtLeast(10.0)) {
	show_error("Metadata API not supported prior to version 10.0", false, true);
	exit;
}

if(isset($_POST['deploymentConfirmed'])) {
  	if(!isset($_SESSION[$_POST["deployFileTmpName"]])) {
  		show_error("No zip file currently staged for deployment. To re-deploy, create a new deploy request.", false, true);
  		exit;
  	}
	
	require_once ('soapclient/SforceMetadataClient.php');
	global $metadataConnection;
	try {
		$deployAsyncResults = $metadataConnection->deploy($_SESSION[$_POST["deployFileTmpName"]], deserializeDeployOptions($_POST));
		$_SESSION[$_POST["deployFileTmpName"]] = null;
		show_info("Successfully uploaded file for deployment to Salesforce.");	
//		print_r($deployAsyncResults);
	} catch (Exception $e) {
		show_error($e->getMessage(), false, true);
	}
} 

else if(isset($_POST['stageForDeployment'])) {
  	$validationErrors = validateZipFile($_FILES["deployFile"]);
  	if($validationErrors) {
  		show_error($validationErrors, false, true);
  		exit;
  	}
  	
  	$deployFileContents = file_get_contents( $_FILES["deployFile"]["tmp_name"]);
  	if(!$deployFileContents) {
  		show_error("Unknown error reading file contents.", false, true);
  		exit;
  	}
  	$_SESSION[$_FILES["deployFile"]["tmp_name"]] = $deployFileContents;
  	
	show_info("Successfully staged " . ceil(($_FILES["deployFile"]["size"] / 1024)) . " KB zip file " . $_FILES["deployFile"]["name"] . " for deployment.");	
	
	?>
	<p class='instructions'>Confirm the following deployment options:</p>
	<form id='deployForm' name='deployForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>'>
		<input type='hidden' name='deployFileTmpName' value='<?php print $_FILES["deployFile"]["tmp_name"]; ?>' />
		<p/>
		<?php printDeployOptions(deserializeDeployOptions($_POST), false); ?>
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

function unCamelCase($camelCasedString) {
    return ucfirst(preg_replace( '/([a-z0-9])([A-Z])/', "$1 $2", $camelCasedString));
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