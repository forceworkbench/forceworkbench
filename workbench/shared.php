<?php
require __DIR__ . '/../vendor/autoload.php';
require_once "util/ErrorLogging.php";
require_once "util/ExpandableTree.php";

function disallowDoctype($xmlString) {
    if (stripos(substr($xmlString, 0, 1000), '!DOCTYPE') !== FALSE) {
        throw new WorkbenchHandledException("XML DOCTYPE declaration not allowed.");
    }
    return $xmlString;
}

function verifyCallingFromCLI() {
    if (php_sapi_name() != 'cli') {
        throw new Exception('Illegal invocation. Should only be called from CLI.');
    }
}

function hasRedis() {
    $redisUrl = WorkbenchConfig::get()->value("redisUrl");
    return !empty($redisUrl) && class_exists("Redis");
}

function redis() {
    if (!isset($GLOBALS['REDIS'])) {
        if (!hasRedis()) {
            throw new Exception("Redis connection requested but not configured or library not found.");
        }

        $redisUrl = WorkbenchConfig::get()->value("redisUrl");
        $r = new Redis();
        $r->connect(parse_url($redisUrl, PHP_URL_HOST), parse_url($redisUrl, PHP_URL_PORT));
        if (!is_array(parse_url($redisUrl, PHP_URL_PASS))) {
            $r->auth(parse_url($redisUrl, PHP_URL_PASS));
        }

        $GLOBALS['REDIS'] = $r;
    }
    return $GLOBALS['REDIS'];
}

function workbenchLog($logLevel, $type, $message = "") {
    if (!WorkbenchConfig::get()->value("enableLogging")) {
        return;
    }

    if (is_array($message)) {
        $as_str = '';
        foreach ($message as $k => $v) {
            $k = preg_replace('/^(measure\.)(.*$)/', '$1' . WorkbenchConfig::get()->value("logPrefix") . '.$2', $k);
            $as_str .= " $k=$v";
        }
        $message = $as_str;
    }

    $sfdcHost = "";
    $orgId = "";
    $userId = "";
    if (WorkbenchContext::isEstablished()) {
        try {
            $sfdcHost = WorkbenchContext::get()->getHost();
            $info = WorkbenchContext::get()->getUserInfo();
            $orgId = $info->organizationId;
            $userId = $info->userId;
        } catch (Exception $e) {
            // ignore and just use defaults
        }
    }

    $pieces = array($type,
                    "at="      . logLevelToStr($logLevel),
                    "method="  . $_SERVER['REQUEST_METHOD'],
                    "path="    . $_SERVER['SCRIPT_NAME'],
                    "origin="  . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']),
                    "v="       . $GLOBALS["WORKBENCH_VERSION"],
                    "sfdc="    . $sfdcHost,
                    "org="     . $orgId,
                    "user="    . $userId
              );

    if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
        $pieces[] = "request_id=" . $_SERVER['HTTP_X_REQUEST_ID'];
    }

    $pieces[] = $message;

    call_user_func('_handle_logs_' . WorkbenchConfig::get()->value("logHandler"), $logLevel, implode(' ', $pieces));
}

function _handle_logs_syslog($logLevel, $msg) {
    openlog(WorkbenchConfig::get()->value("logPrefix"), LOG_ODELAY, WorkbenchConfig::get()->value("syslogFacility"));
    syslog($logLevel, $msg);
    closelog();
}

function _handle_logs_file($logLevel, $msg) {
    $logFile = fopen(WorkbenchConfig::get()->value("logFile"), 'a') or die("can't open log file");
    fwrite($logFile, WorkbenchConfig::get()->value("logPrefix") . " $msg\n");
    fclose($logFile);
}

function _handle_logs_stdout($logLevel, $msg) {
    echo WorkbenchConfig::get()->value("logPrefix") . " $msg\n";
}

function _handle_logs_stderr($logLevel, $msg) {
    file_put_contents("php://stderr", WorkbenchConfig::get()->value("logPrefix") . " $msg\n");
}

function logLevelToStr($logLevel) {
    switch($logLevel) {
        case LOG_EMERG:   return "EMERGENCY";
        case LOG_ALERT:   return "ALERT";
        case LOG_CRIT:    return "CRITICAL";
        case LOG_ERR:     return "ERROR";
        case LOG_WARNING: return "WARNING";
        case LOG_NOTICE:  return "NOTICE";
        case LOG_INFO:    return "INFO";
        case LOG_DEBUG:   return "DEBUG";
        default:          return "";
    }
}

function httpError($code, $reason) {
    header("HTTP/1.1 $code");
    print "<h1>$code</h1>";
    print $reason;
    exit;
}

function getCsrfToken() {
    return md5(WorkbenchConfig::get()->value("csrfSecret") . session_id() . $_SERVER['SCRIPT_NAME']);
}

function skipCsrfValidation() {
    $GLOBALS['SKIP_CSRF_VALIDATION'] = true;
}

function validateCsrfToken($doError = true) {
    if (isset($GLOBALS['SKIP_CSRF_VALIDATION'])) {
        return true;
    }
   if (!isset($_REQUEST['CSRF_TOKEN']) || $_REQUEST['CSRF_TOKEN'] != getCsrfToken()) {

       if ($doError) {
           httpError("403 Forbidden", "Invalid or missing required CSRF token");
       } else {
           return false;
       }
   }
   return true;
}

function getCsrfFormTag() {
    return "\n<input type='hidden' name='CSRF_TOKEN' value='" . getCsrfToken() . "'/>\n";
}

function usingSslFromUserToWorkbench() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
}

function usingSslFromWorkbenchToSfdc() {
    return !WorkbenchContext::isEstablished() || WorkbenchContext::get()->isSecure();
}

function usingSslEndToEnd() {
    return usingSslFromUserToWorkbench() && usingSslFromWorkbenchToSfdc();
}

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

function amt($qty, $word) {
    $amt = $qty . " " . $word;
    if ($qty == 1) {
        return $amt;
    } else {
        return $amt . "s";
    }
}

function getStaticResourceVersionParam() {
    return "?v=" . urlencode($GLOBALS["WORKBENCH_VERSION"]);
}

function getPathToStaticResource($relPath) {
    return "static" . $relPath . getStaticResourceVersionParam();
}

function getPathToStaticResourceAsJsFunction() {
    return "function(relPath) { " .
               "return 'static' + relPath + '" . getStaticResourceVersionParam() . "'" .
            "}";
}

function registerShortcut($key, $jsCommand) {
    addFooterScript("<script type='text/javascript' src='" . getPathToStaticResource('/script/shortcut.js') . "'></script>");
    
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

function isReadOnlyMode() {
    return WorkbenchConfig::get()->value("readOnlyMode");
}

function printAsyncRefreshBlock() {
    if (WorkbenchConfig::get()->value("asyncAutoRefresh")) {
        $lastRefreshNum = (isset($_GET['rn']) && is_numeric($_GET['rn']) && $_GET['rn'] > 0) ? $_GET['rn'] : 1;
        $nextRefreshNum = $lastRefreshNum + 1;
        $newUrl = isset($_GET['rn']) ? str_replace("rn=$lastRefreshNum", "rn=$nextRefreshNum", $_SERVER["REQUEST_URI"]) : ($_SERVER["REQUEST_URI"] . "&rn=1");
        $refreshInterval = ceil(pow($nextRefreshNum, 0.75));
        print "<div style='float:right; color: #888;'>Auto Refreshing " .
                 "<span id='refreshSpinner' style='display:none;'>&nbsp;<img src='" . getPathToStaticResource('/images/wait16trans.gif') . "' align='absmiddle'/></span>" .
                 "<span id='refreshInTimer' style='display:inline;'>in $refreshInterval seconds" .
                 "</span></div>";
        print "<script>setTimeout('document.getElementById(\'refreshInTimer\').style.display=\'none\'; document.getElementById(\'refreshSpinner\').style.display=\'inline\'; window.location.href=" . json_encode($newUrl) . "', $refreshInterval * 1000);</script>";
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

function isKnownAuthenticationError($errorMessage) {
    $allowedErrors = array(
        "INVALID_SESSION_ID",
        "API_CURRENTLY_DISABLED",
        "API_DISABLED_FOR_ORG",
        "REQUEST_LIMIT_EXCEEDED",
        "INVALID_OPERATION_WITH_EXPIRED_PASSWORD",
        "UNSUPPORTED_API_VERSION",
        "Could not connect to host"
    );

    foreach ($allowedErrors as $allowedCode) {
        if (strpos($errorMessage, $allowedCode) !== false) {
            return true;
        }
    }

    return false;
}

function handleAllErrors($errno, $errstr, $errfile, $errline, $errcontext) {
    $errorId = basename($errfile, ".php") . "-$errline-" . time();
    workbenchLog(LOG_CRIT, "F",  "measure.fatal=1 " . $errorId . ":$errstr:" . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));

    switch ($errno) {
        case E_PARSE:
        case E_ERROR:
        case E_USER_ERROR:
        case E_RECOVERABLE_ERROR:
            throw new WorkbenchHandledException("A fatal error occurred. Contact your administrator and provide the following error id:\n [$errorId]", 0);
        default:
            /* Swallow and don't execute PHP internal error handler */
            return true;
    }
}


function handleAllExceptions($e) {
    handleAllExceptionsInternal($e, true);
}

function handleAllExceptionsNoHeaders($e) {
    handleAllExceptionsInternal($e, false);
}

function handleAllExceptionsInternal($e, $showHeadersFooters) {
    $cause = $e;
    $fullMessage = "";
    while ($cause != null) {
        $fullMessage .= $cause->getCode().":".$cause->getMessage()."\n".$e->getTraceAsString()."\n";
        $cause = method_exists($cause,"getPrevious") ? $cause->getPrevious() : null;
    }

    if ($e instanceof WorkbenchAuthenticationException) {
        if (WorkbenchContext::isEstablished()) {
            WorkbenchContext::get()->release();
        }
        if (basename($_SERVER['PHP_SELF']) !== "logout.php") {
            if (!headers_sent()) {
                header("Location: logout.php?invalidateSession=1&message=" . urlencode($e->getMessage()));
            } else {
                $_REQUEST['invalidateSession'] = 1;
                $_REQUEST['message'] = $e->getMessage();
                include_once("logout.php");
            }
        }
        exit;
    }

    print "<p/>";
    if ($e instanceof WorkbenchHandledException) {
        if ($showHeadersFooters) try { include_once 'header.php'; } catch (Exception $e) {}
        displayError($e->getMessage(), false, $showHeadersFooters);
    } else {
        $executing_script = basename($_SERVER['PHP_SELF']);
        if (strpos($e->getMessage(), "INVALID_SESSION_ID") === 0) {
            $invalidSessionException = new WorkbenchAuthenticationException("Your Salesforce session is invalid or has expired. Please login again.");
            foreach ($GLOBALS["MENUS"] as $type => $list_of_pages) {
                if (array_key_exists($executing_script, $list_of_pages) && ($GLOBALS["MENUS"][$type][$executing_script]->noHeaderFooterForExceptionHandler==false)) {
                 handleAllExceptionsNoHeaders($invalidSessionException);
                } else {
                    handleAllExceptions($invalidSessionException);
                }
            }
        }

        if (isKnownAuthenticationError($e->getMessage())) {
            $knownAuthenticationError = new WorkbenchAuthenticationException($e->getMessage());
            foreach ($GLOBALS["MENUS"] as $type => $list_of_pages) {
                if (array_key_exists($executing_script, $list_of_pages) && ($GLOBALS["MENUS"][$type][$executing_script]->noHeaderFooterForExceptionHandler==false)) {
                 handleAllExceptionsNoHeaders($knownAuthenticationError);
                } else {
                    handleAllExceptions($knownAuthenticationError);
                }
            }
        }

        if ($showHeadersFooters)  try { include_once 'header.php'; } catch (Exception $e) {}
        workbenchLog(LOG_ERR, "E", "measure.exception=1 " . $fullMessage);
        displayError("UNKNOWN ERROR: " . $e->getMessage(), false, $showHeadersFooters);
    }
    exit;
}

class WorkbenchHandledException extends Exception {}

class WorkbenchAuthenticationException extends WorkbenchHandledException {}

function unCamelCase($camelCasedString) {
    return ucfirst(preg_replace( '/([a-z0-9])([A-Z])/', "$1 $2", $camelCasedString));
}

function array_unshift_assoc($arr, $key, $val) {
    $arr = array_reverse($arr, true);
    $arr[$key] = $val;
    return array_reverse($arr, true);
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

        if (WorkbenchConfig::get()->overrideable('maxFileSize')) {
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
    return WorkbenchContext::isEstablished() && WorkbenchContext::get()->isLoggedIn();
}

function termsOk() {
    if (!strlen(WorkbenchConfig::get()->value("termsFile"))) {
        return true;
    }

    return WorkbenchContext::isEstablished() && WorkbenchContext::get()->hasAgreedToTerms();
}

function getMyPage() {
    foreach ($GLOBALS["MENUS"] as $pages) {
        foreach ($pages as $href => $page) {
            if (!strcmp($href,basename($_SERVER['PHP_SELF']))) {
                return $page;
            }
        }
    }

    throw new Exception("Invalid page definition.");
}

function getMyTitle() {
    $myPage = getMyPage();
    return $myPage->title;
}

function getTableClass($defaultClass = 'dataTable') {
    return WorkbenchConfig::get()->value("areTablesSortable") ? "sortable" : $defaultClass;
}

function displayError($errors, $showHeader=false, $showFooter=false) {
    if ($showHeader) {
        include_once("header.php");
        print "<p/>";
    }
    print "<div class='displayErrors'>\n";
    print "<img src='" . getPathToStaticResource('/images/error24.png') . "' width='24' height='24' align='middle' border='0' alt='ERROR:' /> <p/>";
    if(!is_array($errors)) $errors = array($errors);

    $errorString = null;
    foreach ($errors as $error) {
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
    print "<img src='" . getPathToStaticResource('/images/warning24.png') . "' width='24' height='24' align='middle' border='0' alt='info:' /> <p/>";
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
    print "<img src='" . getPathToStaticResource('/images/info24.png') . "' width='24' height='24' align='middle' border='0' alt='info:' /> <p/>";
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
	              : ((WorkbenchConfig::get()->value("localeDateTimeFormat") !=  null)
	                  ? WorkbenchConfig::get()->value("localeDateTimeFormat")
	                  : 'Y-m-d\\TH:i:s.000P'));
	                  
    $timezone = WorkbenchConfig::get()->value("convertTimezone");
    
    // Short-circuit if we aren't actually doing anything useful.
    if ($formatOverride == null && $timezone == "" && WorkbenchConfig::get()->value("localeDateTimeFormat") == "") {
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

function printSelectOptions($valuesToLabelsArray,$defaultValue = null) {
    $valueAndLabelMatched = false;
    foreach ($valuesToLabelsArray as $value => $label) {
        print "<option value=\"" . htmlspecialchars($value) . "\"";
        if ($defaultValue == $value) {
            print " selected=\"selected\"";
            $valueAndLabelMatched = true;
        }
        print ">" . htmlspecialchars($label) . "</option>\n";
    }
    return $valueAndLabelMatched;
}

// todo: make these args in the cache?? change to an array instead of 1, 2..dumb
function describeGlobal($filter1=null, $filter2=null) {
    $processedDescribeGlobalResponse = array();

    $describeGlobalResponse = WorkbenchContext::get()->describeGlobal();
    //Print the global object types in a dropdown select box, using the filter set and the API version supports it
    foreach ($describeGlobalResponse->types as $type) {
        if(!isset($describeGlobalResponse->attributeMap) ||
        (($filter1 == null || $describeGlobalResponse->attributeMap["$type"]->$filter1) &&
        ($filter2 == null || $describeGlobalResponse->attributeMap["$type"]->$filter2))) {

            $processedDescribeGlobalResponse[] = $type;
        }
    }

    return $processedDescribeGlobalResponse;
}

function printObjectSelection($defaultObject=false, $nameId='default_object', $width=20, $extras=null, $filter1=null, $filter2=null) {
    // todo: do we really want to set this here? seems like it should be in a request handler or something...
    WorkbenchContext::get()->setDefaultObject($defaultObject);

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


function addLinksToIds($inputStr) {
    $idMatcher = "/\b(\w{5}000\w{10})\b/";
    $uiHref = "href='" . getJumpToSfdcUrlPrefix() . "$1' target='sfdcUi' ";

    $dmlTip = "";

    if (WorkbenchConfig::get()->value("showIdActionsHover")) {
        $tipWidth = 0;
        $dmlTip = "onmouseover=\"Tip('Choose an action:<br/>";
        $dmlActions = array("update", "delete", "undelete", "purge");
        foreach ($dmlActions as $dmlAction) {
            $tipWidth += 55;
            $dmlTip .= "<a href=\'$dmlAction.php?sourceType=singleRecord&id=$1\'>" . ucfirst($dmlAction) . "</a>&nbsp;&nbsp;";
        }
        if (WorkbenchConfig::get()->value('linkIdToUi')) {
            $tipWidth += 125;
            $dmlTip .= "<a " . str_replace("'", "\'", $uiHref) .">View in Salesforce</a>&nbsp;&nbsp;";
        }
        $dmlTip .= "', STICKY, true, WIDTH, $tipWidth)\"";
    }

    return preg_replace($idMatcher,"<a href='retrieve.php?id=$1' $dmlTip>$1</a>", $inputStr);
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

function getProxySettings() {
    if (!WorkbenchConfig::get()->value("proxyEnabled"))  return null;

    $proxySettings = array();
    $proxySettings['proxy_host'] = WorkbenchConfig::get()->value("proxyHost");
    $proxySettings['proxy_port'] = (int)WorkbenchConfig::get()->value("proxyPort"); // Use an integer, not a string
    $proxySettings['proxy_username'] = WorkbenchConfig::get()->value("proxyUsername");
    $proxySettings['proxy_password'] = WorkbenchConfig::get()->value("proxyPassword");

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
function prettyPrintXml($xml, $htmlOutput=FALSE) {
    try {
        libxml_disable_entity_loader(true);
        $xmlObj = new SimpleXMLElement(disallowDoctype($xml));
    } catch (Exception $e) {
        return $xml;
    } finally {
        libxml_disable_entity_loader(false);
    }

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
    return ($htmlOutput) ? '<pre>' . htmlspecialchars($xml) . '</pre>' : $xml;
}

function crypto_serialize($data) {
    return sodium_crypto_box(serialize($data), WorkbenchConfig::get()->value("nonce"), WorkbenchConfig::get()->value("sodiumKey"));
}

function crypto_unserialize($data) {
    $allowlistClasses = [
        ApexExecuteFutureTask::class, 
        QueryFutureTask::class, 
        ConnectionConfiguration::class, 
        RestExplorerFutureTask::class, 
        QueryRequest::class,
        WorkbenchHandledException::class,
        WorkbenchAuthenticationException::class
    ];
    
    $decryptedData = sodium_crypto_box_open($data, WorkbenchConfig::get()->value("nonce"), WorkbenchConfig::get()->value("sodiumKey"));
    
    return unserialize($decryptedData, ['allowed_classes' => $allowlistClasses]);
}

function getComparisonOperators() {
    return array(
            '=' => '=',
            '!=' => '&ne;',
            '<' => '&lt;',
            '<=' => '&le;',
            '>' => '&gt;',
            '>=' => '&ge;',
            'starts' => 'starts with',
            'ends' => 'ends with',
            'contains' => 'contains',
            'IN' => 'in',
            'NOT IN' => 'not in',
            'INCLUDES' => 'includes',
            'EXCLUDES' => 'excludes'
        );
}

// color codes for status cell in the display table for job details
function fillStatusCell($v, $jobId) {
    if(strcmp($v,'Complete')==0) {
         echo "<td style='color:ForestGreen;font-weight:bold' id='".$jobId."_status"."'>".$v."</td></tr>";
    } else if(strcmp($v,'Running')==0) {
         echo "<td style='color:DodgerBlue;font-weight:bold' id='".$jobId."_status"."'>".$v."</td></tr>";
    } else if(strcmp($v,'Canceled')==0) {
         echo "<td style='color:SlateGrey;font-weight:bold' id='".$jobId."_status"."'>".$v."</td></tr>";
    } else if(strcmp($v,'New')==0) {
         echo"<td style='color:MediumBlue;font-weight:bold' id='".$jobId."_status"."'>".$v."</td></tr>";
    } else if(strcmp($v,'Error')==0) {
         echo "<td style='color:Red;font-weight:bold' id='".$jobId."_status"."'>".$v."</td></tr>";
    } else {
        echo "<td id='".$jobId."_status"."'>".$v."</td></tr>";
    }
}
?>
