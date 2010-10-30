<?php
require_once 'restclient/RestClient.php';

class RestExplorerController {  
    private $BASE_REST_URL_PREFIX = '/services/data';
    public $errors; 
    public $url;
    public $requestBody;
    public $requestMethod;
    public $rawResponseHeaders;
    public $rawResponse;
    public $response;
    public $autoExec;
    
    private $client;
    private $insturmenter;
    
    public function __construct() {
        $this->requestMethod = 'GET';
//TODO        insturmenter = new RestResponseInstrumenter(ApexPages.currentPage().getUrl());
        $this->url = isset($_REQUEST['url']) ? $_REQUEST['url'] : $this->BASE_REST_URL_PREFIX;
    }
    
    public function onPageLoad() {
        $this->requestMethod = isset($_REQUEST['requestMethod']) ? $_REQUEST['requestMethod'] : $this->requestMethod;
        $this->url = isset($_REQUEST['url']) ? $_REQUEST['url'] : $this->url;
        $this->requestBody = isset($_REQUEST['requestBody']) ? $_REQUEST['requestBody'] : $this->requestBody;
    	$this->autoExec = isset($_REQUEST['autoExec']) ? $_REQUEST['autoExec'] : $this->autoExec;
    	$doExecute = isset($_REQUEST['doExecute']) ? $_REQUEST['doExecute'] : null;
    	
    	if ($doExecute != null || $this->autoExec == '1') {
            $this->execute();
        }    	
    }

    private function execute() {
        try {
            // clear any old values, in case we don't populate them on this request
            $this->rawResponseHeaders = null;
            $this->rawResponse = null;
            $this->response = null;
            $this->autoExec = null;
            
            // clean up the URL
            $this->url = str_replace(' ', '+', trim($this->url));
            
            // validate URL
            if (strpos($this->url, $this->BASE_REST_URL_PREFIX) != 0) {
                throw new Exception('Invalid REST API Service URI. Must begin with \'' + $this->BASE_REST_URL_PREFIX + '\'.');
            }
            
            //TODO: remove mocking!
            $this->rawResponseHeaders = "some headers\n";
            $this->rawResponse = "[{\"label\":\"Winter '11\",\"version\":\"20.0\",\"url\":\"/services/data/v20.0\"}]";
            $this->response = $this->rawResponse;
            
            // send the resonse    
// TODO!
//            HttpResponse httpResponse = client.send(requestMethod,
//                                        url, 
//                                        RequestMethods.POST.name().equals(requestMethod) 
//                                            ? requestBody 
//                                            : null
//                                       );
//            
//            // process the headers
//            $this->rawResponseHeaders = '';                 
//            for ($headerKey : $httpResponse.getHeaderKeys()) {
//                if (headerKey == null) continue;
//                rawResponseHeaders += headerKey + ': ' + httpResponse.getHeader(headerKey) + '\n';
//            }
//            
//            // process the body
//            $this->rawResponse = httpResponse.getBody();
//            $this->response = insturmenter.instrument($this->rawResponse);
        } catch (Exception $e) {
            $this->errors = $e.getMessage();
        }
    }
}
?>