<?php
function toBytes ($size_str) {
    switch (substr ($size_str, -1)) {
        case 'G': case 'g': $size_str *= 1024;
        case 'M': case 'm': $size_str *= 1024;
        case 'K': case 'k': $size_str *= 1024;
    }
    return (int)$size_str;
}

function endsWith($haystack, $needle, $ignoreCase){
    return substr_compare($haystack, $needle, -strlen($needle), strlen($needle), $ignoreCase) === 0;
}

function getStaticResourcesPath() {
    return $GLOBALS["WORKBENCH_STATIC_RESOURCES_PATH"];
}

function registerShortcut($key, $jsCommand) {
    addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/shortcut.js'></script>");
    
    addFooterScript("<script type='text/javascript'>".
                        "shortcut.add(".
                            "'$key',".
                            "function() {\n$jsCommand\n}".
                        ");".
                    "</script>");
}

function addFooterScript($script) {
    $scriptHash = md5($script); //de-duping
    $_REQUEST["footerScripts"][$scriptHash] = $script;
}

function getConfig($configKey) {
    if (!isset($_SESSION["config"][$configKey]) || 
        (isset($GLOBALS["config"][$configKey]["minApiVersion"])) &&
         !apiVersionIsAtLeast($GLOBALS["config"][$configKey]["minApiVersion"])) {
        
        if ($GLOBALS["config"][$configKey]["dataType"] == "boolean") {
            return false;
        } else {
            return null;
        }
    }
    
    return $_SESSION["config"][$configKey];
}

function isReadOnlyMode() {
    return getConfig("readOnlyMode");
}

function printAsyncRefreshBlock() {
    if (getConfig("asyncAutoRefresh")) {
        $lastRefreshNum = (isset($_GET['rn']) && is_numeric($_GET['rn']) && $_GET['rn'] > 0) ? $_GET['rn'] : 1;
        $nextRefreshNum = $lastRefreshNum + 1;
        $newUrl = isset($_GET['rn']) ? str_replace("rn=$lastRefreshNum", "rn=$nextRefreshNum", $_SERVER["REQUEST_URI"]) : ($_SERVER["REQUEST_URI"] . "&rn=1");
        $refreshInterval = ceil(pow($nextRefreshNum, 0.75));
        print "<div style='float:right; color: #888;'>Auto Refreshing " .
                 "<span id='refreshSpinner' style='display:none;'>&nbsp;<img src='" . getStaticResourcesPath() ."/images/wait16trans.gif' align='absmiddle'/></span>" . 
                 "<span id='refreshInTimer' style='display:inline;'>in $refreshInterval seconds" .
                 "</span></div>";
        print "<script>setTimeout('document.getElementById(\'refreshInTimer\').style.display=\'none\'; document.getElementById(\'refreshSpinner\').style.display=\'inline\'; window.location.href=\'$newUrl\'', $refreshInterval * 1000);</script>";
    } else {
        print "<input type='button' onclick='window.location.href=window.location.href;' value='Refresh' style='float:right;'/>";
    }
}


function explodeCommaSeparated($css) {
    $exploded = explode(",", $css);
    foreach ($exploded as $explodedKey => $explodedValue) {
        $exploded[$explodedKey] = trim($explodedValue);
    }
    return $exploded;
}


function handleAllExceptions($e) {
    displayError("UNKNOWN ERROR: " . $e->getMessage(), true, true);
    exit;
}

function processResults($raw, $groupTopLevelScalarsIn = null, $unCamelCaseKeys = false, $parentRawKey = null) {
    $systemFields = array("Id","IsDeleted","CreatedById","CreatedDate","LastModifiedById","LastModifiedDate","SystemModstamp");
    $processed = array();

    foreach (array(true, false) as $scalarProcessing) {
        foreach ($raw as $rawKey => $rawValue) {
            if (is_array($rawValue) || is_object($rawValue)) {
                if ($scalarProcessing) continue;

                $processedSubResults = processResults($rawValue, null, $unCamelCaseKeys, $rawKey);
                $subCount = " (" . count($processedSubResults) . ")";

                if (isset($rawValue->name) && $rawValue->name != "") {
                    $nameKey = $rawValue->name;
                    if (isset($parentRawKey) && $parentRawKey == "fields") {
                        if (in_array($rawValue->name, $systemFields)) {
                            $nameKey = "<span class='highlightSystemField'>$rawValue->name</span>";
                        } else if ($rawValue->custom) {
                            $nameKey = "<span class='highlightCustomField'>$rawValue->name</span>";
                        }
                    }
                    $processed[$nameKey] = $processedSubResults;
                } else if (isset($rawValue->fileName) && $rawValue->fileName != "") {
                    $processed[$rawValue->fileName] = $processedSubResults;
                } else if (isset($rawValue->fullName) && $rawValue->fullName != "") {
                    $processed[$rawValue->fullName] = $processedSubResults;
                } else if (isset($rawValue->label) && $rawValue->label != "") {
                    $processed[$rawValue->label] = $processedSubResults;
                } else if (isset($rawValue->column) && isset($rawValue->line)) {
                    $processed[$rawValue->column . ":" . $rawValue->line] = $processedSubResults;
                    krsort($processed);
                } else if (isset($rawValue->childSObject) && isset($rawValue->field)) {
                    $processed[$rawValue->childSObject . "." . $rawValue->field] = $processedSubResults;
                } else if ($unCamelCaseKeys) {
                    $processed[unCamelCase($rawKey) . $subCount] = $processedSubResults;
                } else {
                    $processed[$rawKey . $subCount] = $processedSubResults;
                }
            } else {
                if ($groupTopLevelScalarsIn != null) {
                    $processed[$groupTopLevelScalarsIn][$rawKey] = $rawValue;
                } else {
                    $processed[$rawKey] = $rawValue;
                }
            }
        }
    }

    return $processed;
}


function unCamelCase($camelCasedString) {
    return ucfirst(preg_replace( '/([a-z0-9])([A-Z])/', "$1 $2", $camelCasedString));
}

function validateUploadedFile($file) {
    if ($file['error'] != 0) {
        $uploadErrorCodes = array(
        1=>"The file uploaded is too large. Please try again. (Error 1)", //as per PHP config
        2=>"The file uploaded is too large. Please try again. (Error 2)", //as per form config
        3=>"The file uploaded was only partially uploaded.  Please try again. (Error 3)",
        4=>"No file was uploaded.  Please try again. (Error 4)",
        6=>"Missing a temporary folder.  Please try again. (Error 6)",
        7=>"Failed to write file to disk.  Please try again. (Error 7)",
        8=>"File upload stopped by extension.  Please try again. (Error 8)"
        );

        if ($_SESSION['config']['maxFileSize']['overrideable']) {
            $uploadErrorCodes[2] = "The file uploaded is too large. Please try again or adjust in Settings. (Error 2)";
        }

        return($uploadErrorCodes[$file['error']]);
    } else if (!is_uploaded_file($file['tmp_name'])) {
        return("The file was not uploaded from your computer. Please try again.");
    } else if ($file['size'] == 0) {
        return("The file uploaded contains no data. Please try again.");
    } else {
        return(0);
    }
}

function isLoggedIn() {
    return isset($_SESSION['sessionId']);
}

function getMyPage() {
    foreach ($GLOBALS["MENUS"] as $pages) {
        foreach ($pages as $href => $page) {
            if (!strcmp($href,basename($_SERVER['PHP_SELF']))) {
                return $page;
            }
        }
    }
}

function getMyTitle() {
    $myPage = getMyPage();
    return $myPage->title;
}

function getTableClass($defaultClass = 'dataTable') {
    return getConfig("areTablesSortable") ? "sortable" : $defaultClass;
}

function apiVersionIsAtLeast($minVersion) {
    return getApiVersion() >= $minVersion;
}

function getApiVersion() {
    preg_match('!services/Soap/\w/(\d{1,2}\.\d)!',$_SESSION['location'],$apiVersionMatches);
    return $apiVersionMatches[1];
}

function clearSessionCache() {
    $_SESSION['myGlobal'] = null;
    $_SESSION['describeSObjects_results'] = null;
}

function displayError($errors, $showHeader=false, $showFooter=false) {
    if ($showHeader) {
        include_once("header.php");
        print "<p/>";
    }
    print "<div class='displayErrors'>\n";
    print "<img src='" . getStaticResourcesPath() ."/images/error24.png' width='24' height='24' align='middle' border='0' alt='ERROR:' /> <p/>";
    if(!is_array($errors)) $errors = array($errors);

    $errorString = null;
    foreach ($errors as $error) {
        if (is_a($error, 'LibXMLError')) {
            $error = "$error->message [Line $error->line : Column: $error->column]";
        }

        $errorString .= "<p>" . htmlspecialchars((string)$error) . "</p>";
        $errorString = str_replace("\n","<br/>",$errorString);
    }

    print $errorString;
    print "</div>\n";
    if ($showFooter) {
        include_once("footer.php");
        exit;
    }
}

function displayWarning($warnings) {
    print "<div class='displayWarning'>\n";
    print "<img src='" . getStaticResourcesPath() ."/images/warning24.png' width='24' height='24' align='middle' border='0' alt='info:' /> <p/>";
    if (is_array($warnings)) {
        $warningString = "";
        foreach ($warnings as $warning) {
            $warningString .= "<p>" . htmlspecialchars($warning) . "</p>";
        }
        print $warningString;
    } else {
        print htmlspecialchars($warnings);
    }
    print "</div>\n";
}

function displayInfo($infos) {
    print "<div class='displayInfo'>\n";
    print "<img src='" . getStaticResourcesPath() ."/images/info24.png' width='24' height='24' align='middle' border='0' alt='info:' /> <p/>";
    if (is_array($infos)) {
        $infoString = "";
        foreach ($infos as $info) {
            $infoString .= "<p>" . htmlspecialchars($info) . "</p>";
        }
        print $infoString;
    } else {
        print htmlspecialchars($infos);
    }
    print "</div>\n";
}

function getWorkbenchUserAgent() {
    return "Workbench/" . str_replace(" ", "_", trim($GLOBALS["WORKBENCH_VERSION"]));
}

/**
 * Finds and replaces standard Salesforce UTC/GMT dateTimes in a string into a configurable timezone and format
 *
 * @param string inputStr       Abitrary string possibly containing a Salesforce date/timestamp to convert
 * @param string defaultFormat  Output format of datetime 
 * @return string Converted date/time in selected format, or normal field
 */
function localizeDateTimes($inputStr, $formatOverride = null) {
    // Grab the format from the override if it exists, if not check
    // for the config option, otherwise default format
    $format = (($formatOverride != null) 
	              ? $formatOverride 
	              : ((getConfig("localeDateTimeFormat") !=  null) 
	                  ? getConfig("localeDateTimeFormat") 
	                  : 'Y-m-d\\TH:i:s.000P'));
	                  
    $timezone = getConfig("convertTimezone");
    
    // Short-circuit if we aren't actually doing anything useful.
    if ($formatOverride == null && $timezone == "" && getConfig("localeDateTimeFormat") == "") {
        return $inputStr;
    } 

    date_default_timezone_set("UTC");
    
    return preg_replace_callback('|\d\d\d\d\-\d\d\-\d\dT\d\d\:\d\d:\d\d\.\d\d\dZ|', 
                                 create_function(
                                    '$matches',
                                       
                                    '$utcDate = new DateTime($matches[0]);' .
                                    'if (\'' . $timezone . '\'!= \'\') { ' . 
                                        '$utcDate->setTimezone(new DateTimeZone(\'' . $timezone . '\'));'.
                                    '}' .
                                    'return $utcDate->format(\'' . $format . '\');'
                                 ),
                                 $inputStr);
}

function printSelectOptions($valuesToLabelsArray,$defaultValue) {
    $valueAndLabelMatched = false;
    foreach ($valuesToLabelsArray as $value => $label) {
        print "<option value=\"" . $value . "\"";
        if ($defaultValue == $value) {
            print " selected=\"selected\"";
            $valueAndLabelMatched = true;
        }
        print ">" . $label . "</option>\n";
    }
    return $valueAndLabelMatched;
}



function describeGlobal($filter1=null, $filter2=null) {
    $processedDescribeGlobalResponse = array();

    if (!isset($_SESSION['myGlobal']) || !getConfig("cacheDescribeGlobal")) {
        try {
            $describeGlobalResponse = WorkbenchContext::get()->getPartnerConnection()->describeGlobal();

            //Change to pre-17.0 format
            if (isset($describeGlobalResponse->sobjects) && !isset($describeGlobalResponse->types)) {
                $describeGlobalResponse->types = array(); //create the array
                foreach ($describeGlobalResponse->sobjects as $sobject) {
                    $describeGlobalResponse->types[] = $sobject->name; //migrate to pre 17.0 format
                    $describeGlobalResponse->attributeMap["$sobject->name"] = $sobject; //recreate into a map for faster lookup later
                }
                unset($describeGlobalResponse->sobjects); //remove from array, since not needed
            }

            $_SESSION['myGlobal'] = $describeGlobalResponse;
        } catch (Exception $e) {
            displayError($e->getMessage(),false,true);
        }
    }

    //Print the global object types in a dropdown select box, using the filter set and the API version supports it
    foreach ($_SESSION['myGlobal']->types as $type) {
        if(!isset($_SESSION['myGlobal']->attributeMap) ||
        (($filter1 == null || $_SESSION['myGlobal']->attributeMap["$type"]->$filter1) &&
        ($filter2 == null || $_SESSION['myGlobal']->attributeMap["$type"]->$filter2))) {

            $processedDescribeGlobalResponse[] = $type;
        }
    }

    return $processedDescribeGlobalResponse;
}

function printObjectSelection($defaultObject=null, $nameId='default_object', $width=20, $extras=null, $filter1=null, $filter2=null) {
    $_SESSION['default_object'] = $defaultObject;
    $describeGlobalResults = describeGlobal($filter1, $filter2);

    print "<select id='$nameId' name='$nameId' style='width: " . $width. "em;' $extras>\n";

    print "<option value=''></option>";

    //Print the global object types in a dropdown select box, using the filter set and the API version supports it
    foreach ($describeGlobalResults as $type) {
        print "    <option value='$type'";
        if ($defaultObject == $type) {
            print " selected='true'";
        }
        print ">$type</option> \n";
    }
    print "</select>\n";
}

function describeSObject($objectTypes) {
    // if a scalar is passed to this function, change it to an array
    if (!is_array($objectTypes)) {
        $objectTypeArray = array($objectTypes);
    } else {
        $objectTypeArray = $objectTypes;
    }

    // find which objects are already in the session cache to only retreive the
    // ones uncached ones. if caching is disabled, just retreive everything and
    // clear the cache.
    $objectTypesToRetreive = array();
    if (getConfig("cacheDescribeSObject")) {
        foreach ($objectTypeArray as $objectType) {
            if (!isset($_SESSION['describeSObjects_results'][$objectType])) {
                $objectTypesToRetreive[] = $objectType;
            }
        }
    } else {
        $objectTypesToRetreive = $objectTypeArray;
        $_SESSION['describeSObjects_results'] = null;
    }


    // retreive uncached object descriptions from the API and return as an array.
    if (count($objectTypesToRetreive) >= 1 && count($objectTypesToRetreive) <= 100) {
        try {
            $describeSObjectsResults = WorkbenchContext::get()->getPartnerConnection()->describeSObjects($objectTypesToRetreive);
        } catch (Exception $e) {
            displayError($e->getMessage(),false,true);
        }

        if ($describeSObjectsResults instanceof stdClass) {
            $describeSObjectsResultsArray = array($describeSObjectsResults->name => $describeSObjectsResults);
        } else if (is_array($objectTypes)) {
            foreach ($describeSObjectsResults as $describeSObjectResultKey => $describeSObjectResultValue) {
                $describeSObjectsResultsArray[$describeSObjectResultValue->name] = $describeSObjectResultValue;
            }
        } else {
            throw new Exception("Unknown Describe SObject results");
        }

    } else if (count($objectTypesToRetreive) > 100) {
        displayError("Too many polymorphic object types: " . count($objectTypesToRetreive),false,true);
    }

    // move the describe results to the session cache and then copy all the requested object descriptions from the cache
    // if caching is disaled, the results will just be returned directly
    if (getConfig("cacheDescribeSObject")) {
        if (isset($describeSObjectsResultsArray)) {
            foreach ($describeSObjectsResultsArray as $describeSObjectResultKey => $describeSObjectResult) {
                $_SESSION['describeSObjects_results'][$describeSObjectResult->name] = $describeSObjectsResultsArray[$describeSObjectResult->name];
            }
        }

        foreach ($objectTypeArray as $objectTypeKey => $objectTypeValue) {
            $describeSObjectsResultsToReturn[$objectTypeValue] = $_SESSION['describeSObjects_results'][$objectTypeValue];
        }
    } else {
        $describeSObjectsResultsToReturn = $describeSObjectsResultsArray;
    }

    // if alphabetize fields is enabled, alphabetize the describe results
    if (getConfig("abcOrder")) {
        foreach ($describeSObjectsResultsToReturn as $describeSObjectResultKey => $describeSObjectResult) {
            $describeSObjectsResultsToReturn[$describeSObjectResultKey] = alphaOrderFields($describeSObjectResult);
        }
    }

    //finally, return the describe results
    if (!is_array($objectTypes)) {
        return $describeSObjectsResultsToReturn[$objectTypes];
    } else {
        return $describeSObjectsResultsToReturn;
    }
}

function printTree($tableId, $nodes, $forceCollapse = false, $additionalMenus = null, $containsIds = false, $containsDates = false) {
    print "<a class=\"pseudoLink\" onclick=\"javascript:ddtreemenu.flatten('$tableId', 'expand'); return false;\">Expand All</a> | " .
          "<a class=\"pseudoLink\" onclick=\"javascript:ddtreemenu.flatten('$tableId', 'collapse'); return false;\">Collapse All</a>\n";

    if (isset($additionalMenus)) {
        print $additionalMenus;
    }

    print "<ul id='$tableId' class='treeview'>";

    printNode($nodes, $containsIds, $containsDates);
    
    print "</ul>\n";
    
    addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/simpletreemenu.js'></script>");
                        
    addFooterScript("<script type='text/javascript'>" . 
                       "ddtreemenu.createTree('$tableId', true);" . 
                        ($forceCollapse ? "ddtreemenu.flatten('$tableId', 'collapse');" : "") .
                    "</script>");
}

function printNode($node, $containsIds, $containsDates) {
    foreach ($node as $nodeKey => $nodeValue) {
        if (is_array($nodeValue) || is_object($nodeValue)) {
            print "<li>$nodeKey<ul style='display:none;'>\n";
            printNode($nodeValue, $containsIds, $containsDates);
            print "</ul></li>\n";
        } else {
            $nodeKey = is_numeric($nodeKey) ? "" : $nodeKey . ": ";

            if (is_bool($nodeValue)) {
                $nodeValue = $nodeValue == 1 ? "<span class='trueColor'>true</span>" : "<span class='falseColor'>false</span>";
            } else {
                $nodeValue = $containsDates ? localizeDateTimes($nodeValue) : $nodeValue;
                $nodeValue = $containsIds ? addLinksToUiForIds($nodeValue) : $nodeValue;
            }
            
            print "<li>$nodeKey<span style='font-weight:bold;'>$nodeValue</span></li>\n";
        }
    }
}

function alphaOrderFields($describeSObjectResult) {
    //move field name out to key name and then ksort based on key for field abc order
    if (isset($describeSObjectResult->fields)) {
        if(!is_array($describeSObjectResult->fields)) $describeSObjectResult->fields = array($describeSObjectResult->fields);
        foreach ($describeSObjectResult->fields as $field) {
            $fieldNames[] = $field->name;
        }

        $describeSObjectResult->fields = array_combine($fieldNames, $describeSObjectResult->fields);
        $describeSObjectResult->fields = natcaseksort($describeSObjectResult->fields);
    }
    return $describeSObjectResult;
}

function natcaseksort($array) {
    // Like ksort but uses natural sort instead
    $keys = array_keys($array);
    natcasesort($keys);

    $newArray = array();
    foreach ($keys as $k) {
        $newArray[$k] = $array[$k];
    }

    return $newArray;
}


function addLinksToUiForIds($inputStr) {
    if (getConfig('linkIdToUi')) {
        return preg_replace("/\b(\w{4}000\w{11})\b/","<a href='" . getJumpToSfdcUrlPrefix() . "$1' target='sfdcUi'>$1</a>", $inputStr);
    } else {
        return $inputStr;
    }
}

function getJumpToSfdcUrlPrefix() {
    return "jumpToSfdc.php?startUrl=";
}

function convertArrayToCsvLine($arr) {
    $line = array();
    foreach ($arr as $v) {
        $line[] = is_array($v) ? convertArrayToCsvLine($v) : '"' . str_replace('"', '""', $v) . '"';
    }
    return implode(",", $line);
}

function convertArrayToCsv($arr) {
    $lines = array();
    foreach ($arr as $v) {
        $lines[] = convertArrayToCsvLine($v);
    }
    return implode("\n", $lines);
}

function getAsyncApiConnection() {
    $asyncConnection = new BulkApiClient($_SESSION['location'], $_SESSION['sessionId']);
    $asyncConnection->setCompressionEnabled(getConfig("enableGzip"));
    $asyncConnection->setUserAgent(getWorkbenchUserAgent());
    $asyncConnection->setExternalLogReference($_SESSION['restDebugLog']); //TODO: maybe replace w/ its own log??
    $asyncConnection->setLoggingEnabled(getConfig("debug") == true);
    $asyncConnection->setProxySettings(getProxySettings());

    return $asyncConnection;
}

function getRestApiConnection() {
    $restConnection = new RestApiClient($_SESSION['location'], $_SESSION['sessionId']);
    $restConnection->setCompressionEnabled(getConfig("enableGzip"));
    $restConnection->setUserAgent(getWorkbenchUserAgent());
    $restConnection->setExternalLogReference($_SESSION['restDebugLog']);
    $restConnection->setLoggingEnabled(getConfig("debug") == true);
    $restConnection->setProxySettings(getProxySettings());

    return $restConnection;
}

function getProxySettings() {
    if (!getConfig("proxyEnabled"))  return null;

    $proxySettings = array();
    $proxySettings['proxy_host'] = getConfig("proxyHost");
    $proxySettings['proxy_port'] = (int)getConfig("proxyPort"); // Use an integer, not a string
    $proxySettings['proxy_username'] = getConfig("proxyUsername");
    $proxySettings['proxy_password'] = getConfig("proxyPassword");

     return $proxySettings;
}

function in_arrayi($needle, $haystack) {
    foreach($haystack as $value) {
        if(strtolower($value) == strtolower($needle)) {
            return true;
        }
    }   
} 

/**
 * Takes xml as a string and returns it nicely indented
 *
 * @param string $xml The xml to beautify
 * @param boolean $htmlOutput If the xml should be formatted for display on an html page
 * @return string The beautified xml
 */
function prettyPrintXml($xml, $htmlOutput=FALSE)
{
    $xmlObj = new SimpleXMLElement($xml);
    $xmlLines = explode("
", str_replace("><", ">
<", $xmlObj->asXML()));
    $indentLevel = 0;

    $newXmlLines = array();
    foreach ($xmlLines as $xmlLine) {
        if (preg_match('#^(<[a-z0-9_:-]+((s+[a-z0-9_:-]+="[^"]+")*)?>.*<s*/s*[^>]+>)|(<[a-z0-9_:-]+((s+[a-z0-9_:-]+="[^"]+")*)?s*/s*>)#i', ltrim($xmlLine))) {
            $newLine = str_pad('', $indentLevel*4) . ltrim($xmlLine);
            $newXmlLines[] = $newLine;
        } else if (preg_match('#^<[a-z0-9_:-]+((s+[a-z0-9_:-]+="[^"]+")*)?>#i', ltrim($xmlLine))) {
            $newLine = str_pad('', $indentLevel*4) . ltrim($xmlLine);
            $indentLevel++;
            $newXmlLines[] = $newLine;
        } else if (preg_match('#<s*/s*[^>/]+>#i', $xmlLine)) {
            $indentLevel--;
            if (trim($newXmlLines[sizeof($newXmlLines)-1]) == trim(str_replace("/", "", $xmlLine))) {
                $newXmlLines[sizeof($newXmlLines)-1] .= $xmlLine;
            } else {
                $newLine = str_pad('', $indentLevel*4) . $xmlLine;
                $newXmlLines[] = $newLine;
            }
        } else {
            $newLine = str_pad('', $indentLevel*4) . $xmlLine;
            $newXmlLines[] = $newLine;
        }
    }

    $xml = join("
", $newXmlLines);
    return ($htmlOutput) ? '<pre>' . htmlentities($xml) . '</pre>' : $xml;
}


function debug($showSuperVars = true, $showSoap = true, $customName = null, $customValue = null) {
    if (getConfig("debug") == true) {

        print "<script>
            function toggleDebugSection(title, sectionId) {
                var section = document.getElementById(sectionId);
                if (section.style.display == 'inline') {
                    section.style.display = 'none';
                    title.childNodes[0].nodeValue = title.childNodes[0].nodeValue.replace('-','+');
                } else {
                    title.childNodes[0].nodeValue = title.childNodes[0].nodeValue.replace('+','-');
                    section.style.display = 'inline';
                }
            }
            </script>";
         
        print "<div style='text-align: left;'>";


        if ($customValue) {
            if ($customName) {
                print "<h1>$customName</h1>\n";
            } else {
                print "<h1>CUSTOM</h1>\n";
            }

            var_dump($customValue);
            print "<hr/>";
        }

        if ($showSuperVars) {
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

// todo: contextify
        if ($showSoap && WorkbenchContext::get()->getPartnerConnection()->getLastRequestHeaders()) {
            try {
                print "<h1 onclick=\"toggleDebugSection(this,'partner_soap_container')\" class=\"debugHeader\">+ PARTNER SOAP MESSAGES</h1>\n";
                print "<div id='partner_soap_container'  class='debugContainer'>";

                print "<strong>LAST REQUEST HEADER</strong>\n";
                print htmlspecialchars(WorkbenchContext::get()->getPartnerConnection()->getLastRequestHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST REQUEST</strong>\n";
                print htmlspecialchars(prettyPrintXml(WorkbenchContext::get()->getPartnerConnection()->getLastRequest()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE HEADER</strong>\n";
                print htmlspecialchars(WorkbenchContext::get()->getPartnerConnection()->getLastResponseHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE</strong>\n";
                print htmlspecialchars(prettyPrintXml(WorkbenchContext::get()->getPartnerConnection()->getLastResponse()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "</div>";
            }
            catch (Exception $e) {
                print "<strong>SOAP Error</strong>\n";
                print_r ($e);
            }
        }

        if ($showSoap && WorkbenchContext::get()->getMetadataConnection()->getLastRequestHeaders()) {
            try {
                print "<h1 onclick=\"toggleDebugSection(this,'metadata_soap_container')\" class=\"debugHeader\">+ METADATA SOAP MESSAGES</h1>\n";
                print "<div id='metadata_soap_container' class='debugContainer'>";

                print "<strong>LAST REQUEST HEADER</strong>\n";
                print htmlspecialchars(WorkbenchContext::get()->getMetadataConnection()->getLastRequestHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST REQUEST</strong>\n";
                print htmlspecialchars(prettyPrintXml(WorkbenchContext::get()->getMetadataConnection()->getLastRequest()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE HEADER</strong>\n";
                print htmlspecialchars(WorkbenchContext::get()->getMetadataConnection()->getLastResponseHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE</strong>\n";
                print htmlspecialchars(prettyPrintXml(WorkbenchContext::get()->getMetadataConnection()->getLastResponse()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "</div>";
            }
            catch (Exception $e) {
                print "<strong>SOAP Error</strong>\n";
                print_r ($e);
            }
        }

        global $apexConnection;
        if ($showSoap && isset($apexConnection) && $apexConnection->getLastRequestHeaders()) {
            try {
                print "<h1 onclick=\"toggleDebugSection(this,'apex_soap_container')\" class=\"debugHeader\">+ APEX SOAP MESSAGES</h1>\n";
                print "<div id='apex_soap_container' class='debugContainer'>";

                print "<strong>LAST REQUEST HEADER</strong>\n";
                print htmlspecialchars($apexConnection->getLastRequestHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST REQUEST</strong>\n";
                print htmlspecialchars(prettyPrintXml($apexConnection->getLastRequest()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE HEADER</strong>\n";
                print htmlspecialchars($apexConnection->getLastResponseHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE</strong>\n";
                print htmlspecialchars(prettyPrintXml($apexConnection->getLastResponse()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "</div>";
            }
            catch (Exception $e) {
                print "<strong>SOAP Error</strong>\n";
                print_r ($e);
            }
        }

        if (isset($_SESSION['restDebugLog']) && $_SESSION['restDebugLog'] != "") {
            print "<h1 onclick=\"toggleDebugSection(this,'rest_debug_container')\" class=\"debugHeader\">+ REST/BULK API LOGS</h1>\n";
            print "<div id='rest_debug_container' class='debugContainer'>";
            print "<pre>" . $_SESSION['restDebugLog'] . "</pre>";
            print "<hr/>";
            print "</div>";

            $_SESSION['restDebugLog'] = null;
        }

        print "</div>";
    }
}
?>
