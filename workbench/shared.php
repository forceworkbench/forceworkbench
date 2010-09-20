<?php
function registerShortcut($key, $jsCommand) {
    addFooterScript("<script type='text/javascript' src='script/shortcut.js'></script>");
    
    addFooterScript("<script type='text/javascript'>".
                        "shortcut.add(".
                            "'$key',".
                            "function() {\n$jsCommand\n}".
                        ");".
                    "</script>");
}

function addFooterScript($script) {
    $scriptHash = hash('md5', $script); //de-duping
    $_REQUEST["footerScripts"][$scriptHash] = $script;
}

function getConfig($configKey) {
    if (!isset($_SESSION["config"][$configKey])) {
        global $config;
        if ($config[$configKey]->dataType == "boolean") {
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
                 "<span id='refreshSpinner' style='display:none;'>&nbsp;<img src='images/wait16trans.gif' align='absmiddle'/></span>" . 
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

function processResults($raw) {
    $processed = array();

    foreach (array(true, false) as $scalarProcessing) {
        foreach ($raw as $rawKey => $rawValue) {
            if (is_array($rawValue) || is_object($rawValue)) {
                if($scalarProcessing) continue;

                if (isset($rawValue->name) && $rawValue->name != "") {
                    $processed[$rawValue->name] = processResults($rawValue);
                } else if (isset($rawValue->fullName) && $rawValue->fullName != "") {
                    $processed[$rawValue->fullName] = processResults($rawValue);
                } else if (isset($rawValue->fileName) && $rawValue->fileName != "") {
                    $processed[$rawValue->fileName] = processResults($rawValue);
                } else if (isset($rawValue->column) && isset($rawValue->line)) {
                    $processed[$rawValue->column . ":" . $rawValue->line] = processResults($rawValue);
                    krsort($processed);
                } else {
                    $processed[$rawKey] = processResults($rawValue);
                }
            } else {
                $processed[$rawKey] = $rawValue;
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
    } elseif (!is_uploaded_file($file['tmp_name'])) {
        return("The file was not uploaded from your computer. Please try again.");
    } elseif ($file['size'] == 0) {
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
    return $_SESSION['config']['areTablesSortable'] ? "sortable" : $defaultClass;
}

function apiVersionIsAtLeast($minVersion) {
    return getApiVersion() >= $minVersion;
}

function getApiVersion() {
    preg_match('!/(\d{1,2}\.\d)!',$_SESSION['location'],$apiVersionMatches);
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
    print "<img src='images/error24.png' width='24' height='24' align='middle' border='0' alt='ERROR:' /> <p/>";
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
    print "<img src='images/warning24.png' width='24' height='24' align='middle' border='0' alt='info:' /> <p/>";
    if (is_array($warnings)) {
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
    print "<img src='images/info24.png' width='24' height='24' align='middle' border='0' alt='info:' /> <p/>";
    if (is_array($infos)) {
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
 * Converts standard Salesforce UTC/GMT time into a configurable timezone
 *
 * @param string $sfdcDate  The Salesforce date/timestamp to convert
 * @param string $timezone  The PHP timezone setting, ie: America/Chicago
 * @param string $format  The format to use when converting the date/time
 * @return string Converted date/time in selected format, or normal field
 */
function convertDateTimezone($inputStr, $format = 'Y-m-d\\TH:i:s.000P') {
    if (getConfig("convertTimezone") != '' && preg_match('|\d\d\d\d\-\d\d\-\d\dT\d\d\:\d\d:\d\d\.\d\d\dZ|', $inputStr)) {
        $timezone = getConfig("convertTimezone");

        $utcDate = new DateTime($inputStr);
        $utcDate->setTimezone(new DateTimeZone($timezone));

        return $utcDate->format($format);
    } else {
        return $inputStr;
    }

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

    if (!isset($_SESSION['myGlobal']) || !$_SESSION['config']['cacheDescribeGlobal']) {
        try {
            global $partnerConnection;
            $describeGlobalResponse = $partnerConnection->describeGlobal();

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

    print "<select id='$nameId' name='$nameId' style='width: " . $width. "em;' $extras>\n";

    print "<option value=''></option>";

    //Print the global object types in a dropdown select box, using the filter set and the API version supports it
    foreach (describeGlobal($filter1, $filter2) as $type) {
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
    if ($_SESSION['config']['cacheDescribeSObject']) {
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
            global $partnerConnection;
            $describeSObjectsResults = $partnerConnection->describeSObjects($objectTypesToRetreive);
        } catch (Exception $e) {
            displayError($e->getMessage(),false,true);
        }

        if (!is_array($objectTypes)) {
            $describeSObjectsResultsArray = array($describeSObjectsResults->name => $describeSObjectsResults);
        } else {
            foreach ($describeSObjectsResults as $describeSObjectResultKey => $describeSObjectResultValue) {
                $describeSObjectsResultsArray[$describeSObjectResultValue->name] = $describeSObjectResultValue;
            }
        }

    } else if (count($objectTypesToRetreive) > 100) {
        displayError("Too many polymorphic object types: " . count($objectTypesToRetreive),false,true);
    }

    // move the describe results to the session cache and then copy all the requested object descriptions from the cache
    // if caching is disaled, the results will just be returned directly
    if ($_SESSION['config']['cacheDescribeSObject']) {
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
    if ($_SESSION['config']['abcOrder']) {
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

function printTree($tableId, $nodes, $forceCollapse = false, $additionalMenus = null) {
    print "<a class=\"pseudoLink\" onclick=\"javascript:ddtreemenu.flatten('$tableId', 'expand'); return false;\">Expand All</a> | " .
          "<a class=\"pseudoLink\" onclick=\"javascript:ddtreemenu.flatten('$tableId', 'collapse'); return false;\">Collapse All</a>\n";

    if (isset($additionalMenus)) {
        print $additionalMenus;
    }

    print "<ul id='$tableId' class='treeview'>";

    printNode($nodes);
    
    print "</ul>\n";
    
    addFooterScript("<script type='text/javascript' src='script/simpletreemenu.js'></script>");
                        
    addFooterScript("<script type='text/javascript'>" . 
                       "ddtreemenu.createTree('$tableId', true);" . 
                        ($forceCollapse ? "ddtreemenu.flatten('$tableId', 'collapse');" : "") .
                    "</script>");
}

function printNode($node) {
    foreach ($node as $nodeKey => $nodeValue) {
        if (is_array($nodeValue) || is_object($nodeValue)) {
            print "<li>$nodeKey<ul style='display:none;'>\n";
            printNode($nodeValue);
            print "</ul></li>\n";
        } else {
            if (is_bool($nodeValue)) {
                $nodeValue = $nodeValue == 1 ? "<span class='trueColor'>true</span>" : "<span class='falseColor'>false</span>";
            }
            print "<li>". (!is_numeric($nodeKey) ? $nodeKey . ": " : "") . "<span style='font-weight:bold;'>" . addLinksToUiForIds($nodeValue) . "</span></li>\n";
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
    if (isset($_SESSION['config']['linkIdToUi']) && $_SESSION['config']['linkIdToUi'] == true) {
        preg_match("@(https?://.*)/services@", $_SESSION['location'], $instUIDomain);
        return preg_replace("/\b(\w{4}000\w{11})\b/","<a href='$instUIDomain[1]/secur/frontdoor.jsp?sid=". $_SESSION['sessionId'] . "&retURL=%2F$1' target='sfdcUi'>$1</a>",$inputStr);
    } else {
        return $inputStr;
    }
}

function addLinksToUi($startUrl) {
    preg_match("@(https?://.*)/services@", $_SESSION['location'], $instUIDomain);
    return "$instUIDomain[1]/secur/frontdoor.jsp?sid=". $_SESSION['sessionId'] . "&retURL=%2F$startUrl";
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

function simpleFormattedTime($timestamp) {
    $dateTime = new DateTime($timestamp);
    return date("h:i:s A",$dateTime->format("U"));
}

function getAsyncApiConnection() {
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
        } elseif (preg_match('#^<[a-z0-9_:-]+((s+[a-z0-9_:-]+="[^"]+")*)?>#i', ltrim($xmlLine))) {
            $newLine = str_pad('', $indentLevel*4) . ltrim($xmlLine);
            $indentLevel++;
            $newXmlLines[] = $newLine;
        } elseif (preg_match('#<s*/s*[^>/]+>#i', $xmlLine)) {
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
    if (isset($_SESSION['config']['debug']) && $_SESSION['config']['debug'] == true) {

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


        global $partnerConnection;
        if ($showSoap && isset($partnerConnection) && $partnerConnection->getLastRequestHeaders()) {
            try {
                print "<h1 onclick=\"toggleDebugSection(this,'partner_soap_container')\" class=\"debugHeader\">+ PARTNER SOAP MESSAGES</h1>\n";
                print "<div id='partner_soap_container'  class='debugContainer'>";

                print "<strong>LAST REQUEST HEADER</strong>\n";
                print htmlspecialchars($partnerConnection->getLastRequestHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST REQUEST</strong>\n";
                print htmlspecialchars(prettyPrintXml($partnerConnection->getLastRequest()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE HEADER</strong>\n";
                print htmlspecialchars($partnerConnection->getLastResponseHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE</strong>\n";
                print htmlspecialchars(prettyPrintXml($partnerConnection->getLastResponse()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "</div>";
            }
            catch (Exception $e) {
                print "<strong>SOAP Error</strong>\n";
                print_r ($e);
            }
        }

        global $metadataConnection;
        if ($showSoap && isset($metadataConnection) && $metadataConnection->getLastRequestHeaders()) {
            try {
                print "<h1 onclick=\"toggleDebugSection(this,'metadata_soap_container')\" class=\"debugHeader\">+ METADATA SOAP MESSAGES</h1>\n";
                print "<div id='metadata_soap_container' class='debugContainer'>";

                print "<strong>LAST REQUEST HEADER</strong>\n";
                print htmlspecialchars($metadataConnection->getLastRequestHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST REQUEST</strong>\n";
                print htmlspecialchars(prettyPrintXml($metadataConnection->getLastRequest()),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE HEADER</strong>\n";
                print htmlspecialchars($metadataConnection->getLastResponseHeaders(),ENT_QUOTES,'UTF-8');
                print "<hr/>";

                print "<strong>LAST RESPONSE</strong>\n";
                print htmlspecialchars(prettyPrintXml($metadataConnection->getLastResponse()),ENT_QUOTES,'UTF-8');
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
            print "<pre>" . addLinksToUiForIds($_SESSION['restDebugLog']) . "</pre>";
            print "<hr/>";
            print "</div>";

            $_SESSION['restDebugLog'] = null;
        }

        print "</div>";
    }
}
?>