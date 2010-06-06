<?php

function isLoggedIn() {
	return isset($_SESSION['sessionId']);
}

function getMyPage(){
	foreach($GLOBALS["MENUS"] as $pages) {
		foreach($pages as $href => $page) {
			if (!strcmp($href,basename($_SERVER['PHP_SELF']))){
				return $page;
			}
		}
	}
}

function getMyTitle(){
	$myPage = getMyPage();
	return $myPage->title;
}

function getTableClass($defaultClass = 'data_table'){
	return $_SESSION['config']['areTablesSortable'] ? "sortable" : $defaultClass;
}

function apiVersionIsAtLeast($minVersion){
	return getApiVersion() >= $minVersion;
}

function getApiVersion(){
	preg_match('!/(\d{1,2}\.\d)!',$_SESSION['location'],$apiVersionMatches);
	return $apiVersionMatches[1];
}

function clearSessionCache(){
	$_SESSION['myGlobal'] = null;
	$_SESSION['describeSObjects_results'] = null;
}

function show_error($errors, $showHeader=false, $showFooter=false){
	if($showHeader) {
		include_once("header.php");
		print "<p/>";
	}
	print "<div class='show_errors'>\n";
	print "<img src='images/error24.png' width='24' height='24' align='middle' border='0' alt='ERROR:' /> <p/>";
	if(is_array($errors)){
		$errorString = null;
		foreach($errors as $error){
			$errorString .= "<p>" . htmlspecialchars($error) . "</p>";
			$errorString = str_replace("\n","<br/>",$errorString);
		}
	} else {
		$errorString = htmlspecialchars($errors);
		$errorString = str_replace("\n","<br/>",$errorString);
	}
	print $errorString;
	print "</div>\n";
	if($showFooter) {
		include_once("footer.php");
		exit;
	}
}

function show_info($infos){
	print "<div class='show_info'>\n";
	print "<img src='images/info24.png' width='24' height='24' align='middle' border='0' alt='info:' /> <p/>";
	if(is_array($infos)){
		foreach($infos as $info){
			$infoString .= "<p>" . htmlspecialchars($info) . "</p>";
		}
		print $infoString;
	} else {
		print htmlspecialchars($infos);
	}
	print "</div>\n";
}

function getWorkbenchUserAgent(){
	return "Workbench/" . str_replace(" ", "_", trim($GLOBALS["WORKBENCH_VERSION"]));
}

function printSelectOptions($valuesToLabelsArray,$defaultValue){
	$valueAndLabelMatched = false;
	foreach($valuesToLabelsArray as $value => $label){
		print "<option value=\"" . $value . "\"";
		if($defaultValue == $value){
			print " selected=\"selected\"";
			$valueAndLabelMatched = true;
		}
		print ">" . $label . "</option>\n";
	}
	return $valueAndLabelMatched;
}

function describeGlobal($filter1=null, $filter2=null){
	$processedDescribeGlobalResponse = array();
	
	if (!isset($_SESSION['myGlobal']) || !$_SESSION['config']['cacheDescribeGlobal']){
		try{
			global $mySforceConnection;
			$describeGlobalResponse = $mySforceConnection->describeGlobal();
			
			//Change to pre-17.0 format
			if(isset($describeGlobalResponse->sobjects) && !isset($describeGlobalResponse->types)){
				$describeGlobalResponse->types = array(); //create the array
				foreach($describeGlobalResponse->sobjects as $sobject){
					$describeGlobalResponse->types[] = $sobject->name; //migrate to pre 17.0 format
					$describeGlobalResponse->attributeMap["$sobject->name"] = $sobject; //recreate into a map for faster lookup later
				}
				unset($describeGlobalResponse->sobjects); //remove from array, since not needed
			}	
			
			$_SESSION['myGlobal'] = $describeGlobalResponse;
		} catch (Exception $e) {
			show_error($e->getMessage(),false,true);
	    }
	}

	//Print the global object types in a dropdown select box, using the filter set and the API version supports it
	foreach($_SESSION['myGlobal']->types as $type){
		if(!isset($_SESSION['myGlobal']->attributeMap) || 
			(($filter1 == null || $_SESSION['myGlobal']->attributeMap["$type"]->$filter1) && 
			($filter2 == null || $_SESSION['myGlobal']->attributeMap["$type"]->$filter2))){	
			
			$processedDescribeGlobalResponse[] = $type;
		}	
	}
	
	return $processedDescribeGlobalResponse;
}

function myGlobalSelect($default_object=null, $nameId='default_object', $width=20, $extras=null, $filter1=null, $filter2=null){
	$_SESSION['default_object'] = $default_object;
	
	print "<select id='$nameId' name='$nameId' style='width: " . $width. "em;' $extras>\n";	
	
	print "<option value=''></option>";

	//Print the global object types in a dropdown select box, using the filter set and the API version supports it
	foreach(describeGlobal($filter1, $filter2) as $type){
		print "	<option value='$type'";
		if ($default_object == $type){
			print " selected='true'";
		}
		print ">$type</option> \n";
	}
	print "</select>\n";
}

function describeSObject($objectTypes){
	// if a scalar is passed to this function, change it to an array
	if (!is_array($objectTypes)){
		$objectTypeArray = array($objectTypes);
	} else {
		$objectTypeArray = $objectTypes;
	}

	// find which objects are already in the session cache to only retreive the
	// ones uncached ones. if caching is disabled, just retreive everything and
	// clear the cache.
	$objectTypesToRetreive = array();
	if($_SESSION['config']['cacheDescribeSObject']){
		foreach($objectTypeArray as $objectType){
			if(!isset($_SESSION['describeSObjects_results'][$objectType])){
				$objectTypesToRetreive[] = $objectType;
			}
		}
	} else {
		$objectTypesToRetreive = $objectTypeArray;
		$_SESSION['describeSObjects_results'] = null;
	}


	// retreive uncached object descriptions from the API and return as an array. 
	if (count($objectTypesToRetreive) >= 1 && count($objectTypesToRetreive) <= 100){
		try{
			global $mySforceConnection;
			$describeSObjects_results = $mySforceConnection->describeSObjects($objectTypesToRetreive);
		} catch (Exception $e) {
			show_error($e->getMessage(),false,true);
		}

		if (!is_array($objectTypes)){
			$describeSObjects_results_array = array($describeSObjects_results->name => $describeSObjects_results);
		} else {
			foreach ($describeSObjects_results as $describeSObject_resultKey => $describeSObject_resultValue){
				$describeSObjects_results_array[$describeSObject_resultValue->name] = $describeSObject_resultValue;
			}
		}

	} else if(count($objectTypesToRetreive) > 100) {
		show_error("Too many polymorphic object types: " . count($objectTypesToRetreive),false,true);
	}

	// move the describe results to the session cache and then copy all the requested object descriptions from the cache
	// if caching is disaled, the results will just be returned directly 
	if($_SESSION['config']['cacheDescribeSObject']){
		if(isset($describeSObjects_results_array)){
			foreach ($describeSObjects_results_array as $describeSObject_resultKey => $describeSObject_result){
				$_SESSION['describeSObjects_results'][$describeSObject_result->name] = $describeSObjects_results_array[$describeSObject_result->name];
			}
		}

		foreach($objectTypeArray as $objectTypeKey => $objectTypeValue){
			$describeSObjects_results_ToReturn[$objectTypeValue] = $_SESSION['describeSObjects_results'][$objectTypeValue];
		}
	} else {
		$describeSObjects_results_ToReturn = $describeSObjects_results_array;
	}

	// if alphabetize fields is enabled, alphabetize the describe results
	if($_SESSION['config']['abcOrder']){
		foreach ($describeSObjects_results_ToReturn as $describeSObject_resultKey => $describeSObject_result){
			$describeSObjects_results_ToReturn[$describeSObject_resultKey] = alphaOrderFields($describeSObject_result);
		}
	}

	//finally, return the describe results
	if (!is_array($objectTypes)){
		return $describeSObjects_results_ToReturn[$objectTypes];
	} else {
		return $describeSObjects_results_ToReturn;
	}
}

function alphaOrderFields($describeSObject_result){
    //move field name out to key name and then ksort based on key for field abc order
    if(isset($describeSObject_result->fields)){
        if(!is_array($describeSObject_result->fields)) $describeSObject_result->fields = array($describeSObject_result->fields);
    	foreach($describeSObject_result->fields as $field){
            $fieldNames[] = $field->name;
        }
    
        $describeSObject_result->fields = array_combine($fieldNames, $describeSObject_result->fields);
        $describeSObject_result->fields = natcaseksort($describeSObject_result->fields);
    }
    return $describeSObject_result;
}

function natcaseksort($array) {
  // Like ksort but uses natural sort instead
  $keys = array_keys($array);
  natcasesort($keys);

  foreach ($keys as $k)
    $new_array[$k] = $array[$k];

  return $new_array;
}


function addLinksToUiForIds($inputStr){
	if(isset($_SESSION['config']['linkIdToUi']) && $_SESSION['config']['linkIdToUi'] == true){
		preg_match("@(https?://.*)/services@", $_SESSION['location'], $instUIDomain);
		return preg_replace("/\b(\w{4}000\w{11})\b/","<a href='$instUIDomain[1]/secur/frontdoor.jsp?sid=". $_SESSION['sessionId'] . "&retURL=%2F$1' target='sfdcUi'>$1</a>",$inputStr);					
	} else {
		return $inputStr;					
	}
}

function addLinksToUi($startUrl){
	preg_match("@(https?://.*)/services@", $_SESSION['location'], $instUIDomain);
	return "$instUIDomain[1]/secur/frontdoor.jsp?sid=". $_SESSION['sessionId'] . "&retURL=%2F$startUrl";					
}


function arr_to_csv_line($arr) {
	$line = array();
	foreach ($arr as $v) {
		$line[] = is_array($v) ? arr_to_csv_line($v) : '"' . str_replace('"', '""', $v) . '"';
	}
	return implode(",", $line);
}

function arr_to_csv($arr) {
	$lines = array();
	foreach ($arr as $v) {
		$lines[] = arr_to_csv_line($v);
	}
	return implode("\n", $lines);
}

function simpleFormattedTime($timestamp){
	$dateTime = new DateTime($timestamp);
	return date("h:i:s A",$dateTime->format("U"));
}

function getAsyncApiConnection(){
	$asyncConnection = new BulkApiClient($_SESSION['location'], $_SESSION['sessionId']);
	$asyncConnection->setCompressionEnabled($_SESSION['config']['enableGzip']);
	$asyncConnection->setUserAgent(getWorkbenchUserAgent());
	$asyncConnection->setExternalLogReference($_SESSION['restDebugLog']);
	$asyncConnection->setLoggingEnabled(isset($_SESSION['config']['debug']) && $_SESSION['config']['debug'] == true);
	
	return $asyncConnection;
}


/**
* Takes xml as a string and returns it nicely indented
*
* @param string $xml The xml to beautify
* @param boolean $html_output If the xml should be formatted for display on an html page
* @return string The beautified xml
*/
function xml_pretty_printer($xml, $html_output=FALSE)
{
	$xml_obj = new SimpleXMLElement($xml);
	$xml_lines = explode("
", str_replace("><", ">
<", $xml_obj->asXML()));
	$indent_level = 0;
	
	$new_xml_lines = array();
	foreach ($xml_lines as $xml_line) {
		if (preg_match('#^(<[a-z0-9_:-]+((s+[a-z0-9_:-]+="[^"]+")*)?>.*<s*/s*[^>]+>)|(<[a-z0-9_:-]+((s+[a-z0-9_:-]+="[^"]+")*)?s*/s*>)#i', ltrim($xml_line))) {
			$new_line = str_pad('', $indent_level*4) . ltrim($xml_line);
			$new_xml_lines[] = $new_line;
		} elseif (preg_match('#^<[a-z0-9_:-]+((s+[a-z0-9_:-]+="[^"]+")*)?>#i', ltrim($xml_line))) {
			$new_line = str_pad('', $indent_level*4) . ltrim($xml_line);
			$indent_level++;
			$new_xml_lines[] = $new_line;
		} elseif (preg_match('#<s*/s*[^>/]+>#i', $xml_line)) {
			$indent_level--;
			if (trim($new_xml_lines[sizeof($new_xml_lines)-1]) == trim(str_replace("/", "", $xml_line))) {
				$new_xml_lines[sizeof($new_xml_lines)-1] .= $xml_line;
			} else {
				$new_line = str_pad('', $indent_level*4) . $xml_line;
				$new_xml_lines[] = $new_line;
			}
		} else {
			$new_line = str_pad('', $indent_level*4) . $xml_line;
			$new_xml_lines[] = $new_line;
		}
	}
	
	$xml = join("
", $new_xml_lines);
	return ($html_output) ? '<pre>' . htmlentities($xml) . '</pre>' : $xml;
}


function debug($showSuperVars = true, $showSoap = true, $customName = null, $customValue = null){
	if(isset($_SESSION['config']['debug']) && $_SESSION['config']['debug'] == true){

		print "<script>
			function toggleDebugSection(title, sectionId){
				var section = document.getElementById(sectionId);
				if(section.style.display == 'inline'){
					section.style.display = 'none';
					title.childNodes[0].nodeValue = title.childNodes[0].nodeValue.replace('-','+');
				} else {
					title.childNodes[0].nodeValue = title.childNodes[0].nodeValue.replace('+','-');
					section.style.display = 'inline';
				}
			}
			</script>";
 	
		print "<div style='text-align: left;'>";
		

		if($customValue){
			if($customName){
				print "<h1>$customName</h1>\n";
			} else {
				print "<h1>CUSTOM</h1>\n";
			}

			 var_dump($customValue);
			print "<hr/>";
		}

		if($showSuperVars){
			print "<h1 onclick=\"toggleDebugSection(this,'container_globals')\" class=\"debugHeader\">+ SUPERGLOBAL VARIABLES</h1>\n";
			print "<div id='container_globals' class='debugContainer'>";
			
				
				print "<strong onclick=\"toggleDebugSection(this,'container_globals_cookie')\" class=\"debugHeader\">+ COOKIE SUPERGLOBAL VARIABLE</strong>\n";
				print "<div id='container_globals_cookie' class='debugContainer'>";
				var_dump ($_COOKIE);
				print "<hr/>";
				print "</div>";
				
				print "<strong onclick=\"toggleDebugSection(this,'container_globals_session')\" class=\"debugHeader\">+ SESSION SUPERGLOBAL VARIABLE</strong>\n";
				print "<div id='container_globals_session' class='debugContainer'>";
				var_dump ($_SESSION);
				print "<hr/>";
				print "</div>";

				print "<strong onclick=\"toggleDebugSection(this,'container_globals_post')\" class=\"debugHeader\">+ POST SUPERGLOBAL VARIABLE</strong>\n";
				print "<div id='container_globals_post' class='debugContainer'>";
				var_dump ($_POST);
				print "<hr/>";
				print "</div>";
				
				print "<strong onclick=\"toggleDebugSection(this,'container_globals_get')\" class=\"debugHeader\">+ GET SUPERGLOBAL VARIABLE</strong>\n";
				print "<div id='container_globals_get' class='debugContainer'>";
				var_dump ($_GET);
				print "<hr/>";
				print "</div>";
				
				print "<strong onclick=\"toggleDebugSection(this,'container_globals_files')\" class=\"debugHeader\">+ FILES SUPERGLOBAL VARIABLE</strong>\n";
				print "<div id='container_globals_files' class='debugContainer'>";
				var_dump ($_FILES);
				print "<hr/>";
				print "</div>";
				
				print "<strong onclick=\"toggleDebugSection(this,'container_globals_env')\" class=\"debugHeader\">+ ENVIRONMENT SUPERGLOBAL VARIABLE</strong>\n";
				print "<div id='container_globals_env' class='debugContainer'>";
				var_dump ($_ENV);
				print "<hr/>";
				print "</div>";

			print "</div>";
		}


		global $mySforceConnection;
		if($showSoap && isset($mySforceConnection) && $mySforceConnection->getLastRequestHeaders()){
			try{
				print "<h1 onclick=\"toggleDebugSection(this,'partner_soap_container')\" class=\"debugHeader\">+ PARTNER SOAP MESSAGES</h1>\n";
				print "<div id='partner_soap_container'  class='debugContainer'>";

					print "<strong>LAST REQUEST HEADER</strong>\n";
					print htmlspecialchars($mySforceConnection->getLastRequestHeaders(),ENT_QUOTES,'UTF-8');
					print "<hr/>";
	
					print "<strong>LAST REQUEST</strong>\n";
					print htmlspecialchars(xml_pretty_printer($mySforceConnection->getLastRequest()),ENT_QUOTES,'UTF-8');
					print "<hr/>";
	
					print "<strong>LAST RESPONSE HEADER</strong>\n";
					print htmlspecialchars($mySforceConnection->getLastResponseHeaders(),ENT_QUOTES,'UTF-8');
					print "<hr/>";
	
					print "<strong>LAST RESPONSE</strong>\n";
					print htmlspecialchars(xml_pretty_printer($mySforceConnection->getLastResponse()),ENT_QUOTES,'UTF-8');
					print "<hr/>";
				
				print "</div>";
			}
			catch (Exception $e) {
				print "<strong>SOAP Error</strong>\n";
				print_r ($e);
			}
		}
		
		global $apexBinding;
		if($showSoap && isset($apexBinding) && $apexBinding->getLastRequestHeaders()){
			try{
				print "<h1 onclick=\"toggleDebugSection(this,'apex_soap_container')\" class=\"debugHeader\">+ APEX SOAP MESSAGES</h1>\n";
				print "<div id='apex_soap_container' class='debugContainer'>";

					print "<strong>LAST REQUEST HEADER</strong>\n";
					print htmlspecialchars($apexBinding->getLastRequestHeaders(),ENT_QUOTES,'UTF-8');
					print "<hr/>";
	
					print "<strong>LAST REQUEST</strong>\n";
					print htmlspecialchars(xml_pretty_printer($apexBinding->getLastRequest()),ENT_QUOTES,'UTF-8');
					print "<hr/>";
	
					print "<strong>LAST RESPONSE HEADER</strong>\n";
					print htmlspecialchars($apexBinding->getLastResponseHeaders(),ENT_QUOTES,'UTF-8');
					print "<hr/>";
	
					print "<strong>LAST RESPONSE</strong>\n";
					print htmlspecialchars(xml_pretty_printer($apexBinding->getLastResponse()),ENT_QUOTES,'UTF-8');
					print "<hr/>";
					
				print "</div>";
			}
			catch (Exception $e) {
				print "<strong>SOAP Error</strong>\n";
				print_r ($e);
			}
		}

		if(isset($_SESSION['restDebugLog']) && $_SESSION['restDebugLog'] != ""){
			print "<h1 onclick=\"toggleDebugSection(this,'rest_debug_container')\" class=\"debugHeader\">+ REST/BULK API LOGS</h1>\n";
			print "<div id='rest_debug_container' class='debugContainer'>";
				print "<pre>" . addLinksToUiForIds($_SESSION['restDebugLog']) . "</pre>";
				print "<hr/>";
			print "</div>";
			
			$_SESSION['restDebugLog'] = null;
		}
		
		print "</div>";
	}
}
?>