<?php
require_once 'restclient/RestClient.php';
require_once 'controllers/RestResponseInstrumenter.php';

class RestExplorerController {  
    private $BASE_REST_URL_PREFIX = '/services';
    public $errors; 
    public $url;
    public $requestBody;
    public $requestMethod;
    public $rawResponse;
    public $instResponse;
    public $showResponse;
    public $autoExec;

    public function __construct() {
        $this->requestMethod = 'GET';
        $this->url = isset($_REQUEST['url']) ? $_REQUEST['url'] : $this->BASE_REST_URL_PREFIX . '/data';
    }
    
    public function onPageLoad() {
        $this->errors = null;
        $this->showResponse = false;
        $this->requestMethod = isset($_REQUEST['requestMethod']) ? $_REQUEST['requestMethod'] : $this->requestMethod;
        $this->url = isset($_REQUEST['url']) 
                                ? (get_magic_quotes_gpc() 
                                    ? stripslashes($_REQUEST['url']) 
                                    : $_REQUEST['url']) 
                                : $this->url;
        $this->requestBody = isset($_REQUEST['requestBody']) 
                                ? (get_magic_quotes_gpc() 
                                    ? stripslashes($_REQUEST['requestBody']) 
                                    : $_REQUEST['requestBody']) 
                                : $this->requestBody;
    	$this->autoExec = isset($_REQUEST['autoExec']) ? $_REQUEST['autoExec'] : $this->autoExec;
    	$doExecute = isset($_REQUEST['doExecute']) ? $_REQUEST['doExecute'] : null;
    	
    	if ($doExecute != null || $this->autoExec == '1') {
            $this->execute();
        }    	
    }

    private function execute() {
        try {
            // clear any old values, in case we don't populate them on this request
            $this->rawResponse = null;
            $this->instResponse = null;
            $this->autoExec = null;
            
            // clean up the URL
            $this->url = str_replace(' ', '+', trim($this->url));
            
            // validate URL
            if (preg_match("!" . $this->BASE_REST_URL_PREFIX . "/\w+!", $this->url) == 0) {
                throw new Exception('Invalid REST API Service URI. Must begin with \''
                        . $this->BASE_REST_URL_PREFIX . '\' followed by a service name, such as \'/services/data\'.');
            }
            
            if (in_array($this->requestMethod, RestApiClient::getMethodsWithBodies()) && trim($this->requestBody) == "") {
                throw new Exception("Must include a Request Body.");
            }

            $expectBinary = $this->requestMethod == 'GET' && preg_match("@\w{4}0{3}\w{8}([A-Z]{3})?/(Body|VersionData|ContentData|Document|Binary)$@", $this->url) > 0;
            $this->rawResponse = getRestApiConnection()->send($this->requestMethod, 
                                                              $this->url, "application/json",
                                                              in_array($this->requestMethod, RestApiClient::getMethodsWithBodies()) ? $this->requestBody : null,
                                                              $expectBinary);

            if (stripos($this->rawResponse->header, "HTTP/1.1 404") !== false) {
                $this->showResponse = false;
                throw new Exception("Service not found at: " . $this->url);
            } if (stripos($this->rawResponse->header, "Content-Type: text/html") !== false) {
                $this->showResponse = false;
                throw new Exception("Got HTML at: " . $this->url);
            } else if ($expectBinary) {
                $this->offerBinaryResponseAsDownload();
                $this->rawResponse = null;
                return;
            } else if (stripos($this->rawResponse->header, "Content-Type: application/json") !== false) {                                              
                $insturmenter = new RestResponseInstrumenter($_SERVER['PHP_SELF']);
                $this->instResponse = $insturmenter->instrument($this->rawResponse->body);
                $this->showResponse = true;
            } else {
                $this->showResponse = true;
            }

        } catch (Exception $e) {
            $this->errors = $e->getMessage();
        }
    }
    
    private function offerBinaryResponseAsDownload() {
        $expUrl = explode("/", $this->url);
        
        $binIdPos = count($expUrl) - 2;
        $binId = $expUrl[$binIdPos];
        
        $binSobjectTypePos = count($expUrl) - 3;
        $binSobjectType = $expUrl[$binSobjectTypePos];
        
        global $partnerConnection;
        try {
            // Handle the different fields that support binary data in their own special way.
            
            if (in_arrayi($binSobjectType, array("Document", "Attachment", "StaticResource"))) {
                $binInfo = new SObject($partnerConnection->retrieve("Name, ContentType", $binSobjectType, $binId));    
                $binFilename = $binInfo->fields->Name;
                $binContentType = $binInfo->fields->ContentType;
            } else if ($binSobjectType == "ContentVersion") {
                $binInfo = new SObject($partnerConnection->retrieve("PathOnClient, FileType", $binSobjectType, $binId));    
                $binFilename= basename($binInfo->fields->PathOnClient);
                $binContentType = "application/" . $binInfo->fields->FileType;
            } else if ($binSobjectType == "FeedPost") {
                $binInfo = new SObject($partnerConnection->retrieve("ContentFileName, ContentType", $binSobjectType, $binId));    
                $binFilename= $binInfo->fields->ContentFileName;
                $binContentType = $binInfo->fields->ContentType;
            } else if ($binSobjectType == "MailmergeTemplate") {
                $binInfo = new SObject($partnerConnection->retrieve("Filename", $binSobjectType, $binId));    
                $binFilename= $binInfo->fields->Filename;
                $binContentType = "application/msword";
            } else if ($binSobjectType == "QuoteDocument") {
                $binInfo = new SObject($partnerConnection->retrieve("Name", $binSobjectType, $binId));    
                $binFilename= $binInfo->fields->Name;
            } else {
                throw new Exception('Unknown binary type');
            }
        } catch (Exception $e) {
              // if we don't know this type, just use the id and don't set the content type
              $binFilename = $binId;
        }
        
        header("Content-Disposition: attachment; filename=" . rawurlencode($binFilename));
        if (isset($binContentType)) header("Content-Type: " . $binContentType);
        echo $this->rawResponse->body;
    }
}
?>