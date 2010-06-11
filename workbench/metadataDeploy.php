<?php
require_once ('soapclient/SforceMetadataClient.php');
require_once ('session.php');
require_once ('shared.php');
if(!apiVersionIsAtLeast(10.0)) {
	show_error("Metadata API not supported prior to version 10.0", true, true);
	exit;
}

if(isset($_POST['deploymentConfirmed']) && isset($_POST["deployFileTmpName"])) {
	$deployFileTmpName = htmlentities($_POST["deployFileTmpName"]);
	
  	if(!isset($_SESSION[$deployFileTmpName])) {
  		show_error("No zip file currently staged for deployment. To re-deploy, create a new deploy request.", true, true);
  		exit;
  	}

  	if(!isset($_SESSION[$deployFileTmpName . "_OPTIONS"])) {
  		show_error("Error loading deploy options. To re-deploy, create a new deploy request.", true, true);
  		exit;
  	}
  	
	global $metadataConnection;
	try {
		$deployAsyncResults = $metadataConnection->deploy($_SESSION[$deployFileTmpName], $_SESSION[$deployFileTmpName . "_OPTIONS"]);
		unset($_SESSION[$deployFileTmpName]);
		unset($_SESSION[$deployFileTmpName . "_OPTIONS"]);
		
		if(!isset($deployAsyncResults->id)){
			show_error("Unknown deployment error.\n" . isset($deployAsyncResults->message) ? $deployAsyncResults->message : "", true, true);
			exit;
		}
		
		header("Location: metadataStatus.php?asyncProcessId=" . $deployAsyncResults->id . "&op=D");
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
  	
  	$deployFileTmpName = $_FILES["deployFile"]["tmp_name"];
  	$deployFileContents = file_get_contents($deployFileTmpName);
  	if(!isset($deployFileContents) || !$deployFileContents) {
  		show_error("Unknown error reading file contents.", true, true);
  		exit;
  	}
  	$_SESSION[$deployFileTmpName] = $deployFileContents;
  	$_SESSION[$deployFileTmpName . "_OPTIONS"] = deserializeDeployOptions($_POST);
  	
  	require_once('header.php');
	show_info("Successfully staged " . ceil(($_FILES["deployFile"]["size"] / 1024)) . " KB zip file " . $_FILES["deployFile"]["name"] . " for deployment.", true, false);	
	
	?>
	<p class='instructions'>Confirm the following deployment options:</p>
	<form id='deployForm' name='deployForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>'>
		<input type='hidden' name='deployFileTmpName' value='<?php print $deployFileTmpName; ?>' />
		<p/>
		<?php printDeployOptions($_SESSION[$deployFileTmpName . "_OPTIONS"], false); ?>
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
	<form id='deployForm' name='deployForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>' enctype='multipart/form-data'>
		<input type='file' name='deployFile' size='44' />
		<input type='hidden' name='MAX_FILE_SIZE' value='<?php print $_SESSION['config']['maxFileSize']; ?>' />
		<p/>
		<?php printDeployOptions(new DeployOptions(), true); ?>
		<p/>
		<input type='submit' name='stageForDeployment' value='Upload' /> 
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
			$explodedSubValues = (isset($request[$optionName]) && $request[$optionName] != "") ? explode(",", htmlentities($request[$optionName])) : array();
			foreach($explodedSubValues as $k => $subvalue) {
				$explodedSubValues[$k] = trim($subvalue);
			}
			$deployOptions->$optionName = $explodedSubValues;
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