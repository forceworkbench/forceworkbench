<?php
require_once 'restclient/RestClient.php';
require_once 'controllers/RestResponseInstrumenter.php';

class RestExplorerController {  
    private $BASE_REST_URL_PREFIX = '/services';
    private $DEFAULT_REQUEST_HEADERS = "Content-Type: application/json; charset=UTF-8\nAccept: application/json";

    public $pageSelf;
    public $errors;
    public $url;
    public $requestMethod;
    public $requestBody;
    public $rawResponse;
    public $instResponse;
    public $showResponse;
    public $autoExec;
    public $doExecute;

    public function __construct() {
        $this->requestMethod = 'GET';
        $this->url = isset($_REQUEST['url']) ? $_REQUEST['url'] : $this->BASE_REST_URL_PREFIX . '/data/v' . WorkbenchContext::get()->getApiVersion();
        $this->requestHeaders = $this->DEFAULT_REQUEST_HEADERS;
    }
    
    public function onPageLoad() {
        $this->pageSelf = $_SERVER['PHP_SELF'];
        $this->errors = null;
        $this->autoExec = null;
        $this->doExecute = null;
        $this->showResponse = false;
        $this->requestMethod = isset($_REQUEST['requestMethod']) ? $_REQUEST['requestMethod'] : $this->requestMethod;

        $this->url = isset($_REQUEST['url'])
                                ? (get_magic_quotes_gpc() 
                                    ? stripslashes($_REQUEST['url']) 
                                    : $_REQUEST['url']) 
                                : $this->url;

        $this->requestHeaders = isset($_REQUEST['requestHeaders'])
                                ? (get_magic_quotes_gpc() 
                                    ? stripslashes($_REQUEST['requestHeaders'])
                                    : $_REQUEST['requestHeaders'])
                                : $this->requestHeaders;

        $this->requestBody = isset($_REQUEST['requestBody'])
                                ? (get_magic_quotes_gpc()
                                    ? stripslashes($_REQUEST['requestBody'])
                                    : $_REQUEST['requestBody'])
                                : $this->requestBody;

        $this->autoExec = $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_REQUEST['autoExec']) ? $_REQUEST['autoExec'] : $this->autoExec;

        $this->doExecute = isset($_REQUEST['doExecute']) ? $_REQUEST['doExecute'] : null;

        if ($this->doExecute || $this->autoExec == '1') {
            $this->preExecute();
        }
    }

    /**
     * Called synchronously before possibly submitting to async framework
     */
    public function preExecute() {
        if ($this->requestMethod !== 'GET') {
            validateCsrfToken();
        }

        // clean up the URL
        $this->url = str_replace(' ', '+', trim($this->url));

        if (in_array($this->requestMethod, RestApiClient::getMethodsWithBodies()) && trim($this->requestBody) == "") {
            throw new WorkbenchHandledException("Must include a Request Body.");
        }
    }

    /**
     * Could be called by sync or by async framework
     *
     */
    public function execute() {
        try {
            // clear any old values, in case we don't populate them on this request
            $this->rawResponse = null;
            $this->instResponse = null;
            $this->autoExec = null;

            $expectBinary = $this->prepareBinaryResponseAsDownload();
            $this->rawResponse =  WorkbenchContext::get()->getRestDataConnection()->send(
                                  $this->requestMethod,
                                  $this->url,
                                  explode("\n", $this->requestHeaders),
                                  in_array($this->requestMethod,
                                  RestApiClient::getMethodsWithBodies()) ? $this->requestBody : null,
                                  $expectBinary);

            if (stripos($this->rawResponse->header, "HTTP/1.1 404") !== false) {
                $this->showResponse = false;
                throw new WorkbenchHandledException("Service not found at: " . $this->url);
            } if (stripos($this->rawResponse->header, "Content-Type: text/html") !== false) {
                $this->showResponse = false;
                throw new WorkbenchHandledException("Got HTML at: " . $this->url);
            } else if ($expectBinary) {
                return;
            } else if (stripos($this->rawResponse->header, "Content-Type: application/json") !== false) {
                $insturmenter = new RestResponseInstrumenter(htmlspecialchars($this->pageSelf));
                $this->instResponse = $insturmenter->instrumentJson($this->rawResponse->body);
                $this->showResponse = true;
            } else {
                $this->showResponse = true;
            }

        } catch (Exception $e) {
            $this->errors = $e->getMessage();
        }
    }


    /**
     * @return bool true if binary is expected
     */
    private function prepareBinaryResponseAsDownload() {
        if ($this->requestMethod != 'GET'
            || preg_match("@\w{4}0{3}\w{8}([A-Z]{3})?/(Body|VersionData|ContentData|Document|Binary)$@", $this->url) == 0) {
            return false;
        }

        $expUrl = explode("/", $this->url);
        
        $binIdPos = count($expUrl) - 2;
        $binId = $expUrl[$binIdPos];
        
        $binSobjectTypePos = count($expUrl) - 3;
        $binSobjectType = $expUrl[$binSobjectTypePos];
        
        // Handle the different fields that support binary data in their own special way.
        if (in_arrayi($binSobjectType, array("Document", "Attachment", "StaticResource"))) {
            $binInfo = new SObject(WorkbenchContext::get()->getPartnerConnection()->retrieve("Name, ContentType, BodyLength", $binSobjectType, $binId));
            $binFilename = $binInfo->fields->Name;
            $binContentType = $binInfo->fields->ContentType;
            $binContentLength = $binInfo->fields->BodyLength;
        } else if ($binSobjectType == "ContentVersion") {
            $binInfo = new SObject(WorkbenchContext::get()->getPartnerConnection()->retrieve("PathOnClient, FileType, ContentSize", $binSobjectType, $binId));
            $binFilename= basename($binInfo->fields->PathOnClient);
            $binContentType = "application/" . $binInfo->fields->FileType;
            $binContentLength = $binInfo->fields->ContentSize;
        } else if (stripos($this->url, "ContentData")) {
            $binInfo = new SObject(WorkbenchContext::get()->getPartnerConnection()->retrieve("ContentFileName, ContentType, ContentSize", $binSobjectType, $binId));
            $binFilename= $binInfo->fields->ContentFileName;
            $binContentType = $binInfo->fields->ContentType;
            $binContentLength = $binInfo->fields->ContentSize;
        } else if ($binSobjectType == "MailmergeTemplate") {
            $binInfo = new SObject(WorkbenchContext::get()->getPartnerConnection()->retrieve("Filename, BodyLength", $binSobjectType, $binId));
            $binFilename= $binInfo->fields->Filename;
            $binContentType = "application/msword";
            $binContentLength = $binInfo->fields->BodyLength;
        } else if ($binSobjectType == "QuoteDocument") {
            $binInfo = new SObject(WorkbenchContext::get()->getPartnerConnection()->retrieve("Name", $binSobjectType, $binId));
            $binFilename= $binInfo->fields->Name;
        } else {
            return false;
        }

        header("Content-Disposition: attachment; filename=" . rawurlencode($binFilename));
        if (isset($binContentType)) header("Content-Type: " . $binContentType);
        if (isset($binContentLength)) header("Content-Length: " . $binContentLength);
        return true;
    }

    public function getDefaultRequestHeaders() {
        return $this->DEFAULT_REQUEST_HEADERS;
    }
}
?>
