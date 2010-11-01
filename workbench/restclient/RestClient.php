<?php
class RestApiClient {
    private $baseUrl;
    private $sessionId;
    private $userAgent = "PHP-RestApiClient/20.0";
    private $compressionEnabled = true;
    private $logs;
    private $loggingEnabled = false;

    public function __construct($partnerEndpoint, $sessionId) {
		if (!extension_loaded('curl')) {
			throw new Exception('Missing required cURL extension.');
		}
	
        $this->baseUrl = $this->getBaseUrlFromPartnerEndpoint($partnerEndpoint);
        $this->sessionId = $sessionId;
    }

    public function getUserAgent() {
        return $this->userAgent;
    }

    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
    }

    public function getCompressionEnabled() {
        return $this->compressionEnabled;
    }

    public function setCompressionEnabled($compressionEnabled) {
        $this->compressionEnabled = $compressionEnabled;
    }

    private function getBaseUrlFromPartnerEndpoint($partnerEndpoint) {
        preg_match("!(https?://.*)/services/Soap/u/(\d{1,2}\.\d)/.*!", $partnerEndpoint, $matches);
        
        if ($matches[2] < 20.0) {
            throw new Exception("REST API operations only supported in API 20.0 and higher.");
        }
        
        return $matches[1];
    }

    public function send($method, $url, $contentType, $data) {
        $this->log("INITIALIZING cURL \n" . print_r(curl_version(), true));

        $ch = curl_init();

        $httpHeaders = array(
            "Authorization: OAuth " . $this->sessionId,
            "X-PrettyPrint: true",
            "Accept: application/json",
            "User-Agent: " . $this->userAgent,
            "Expect:"
            );
        
        if (isset($contentType)) {
            $httpHeaders[] = "Content-Type: $contentType; charset=UTF-8";
        }

        switch ($method) {
            case 'HEAD':
                curl_setopt($ch, CURLOPT_NOBODY, 1);
                break;
            case 'GET': 
                // do nothing
                break;
            case 'DELETE': 
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            default:
                throw new Exception($method . ' method not supported.');
        }
        
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                                //TODO: use ca-bundle instead
        
        if ($this->compressionEnabled) {
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");   //TODO: add  outbound compression support
        }

        $this->log("REQUEST \n METHOD: $method \n URL: $url \n HTTP HEADERS: \n" . print_r($httpHeaders, true) . " DATA:\n " . htmlentities($data));

        $chResponse = curl_exec($ch);
        $this->log("RESPONSE \n" . htmlentities($chResponse));
        
        if (curl_error($ch) != null) {
            $this->log("ERROR \n" . htmlentities(curl_error($ch)));
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return new HttpResponse($chResponse);
    }

    //LOGGING FUNCTIONS

    public function isLoggingEnabled() {
        return $this->loggingEnabled;
    }

    public function setLoggingEnabled($loggingEnabled) {
        $this->loggingEnabled = $loggingEnabled;
    }

    protected function log($txt) {
        if ($this->loggingEnabled) {
            $this->logs .= $txt .= "\n\n";
        }
        return $txt;
    }

    public function setExternalLogReference(&$extLogs) {
        $this->logs = &$extLogs;
    }

    public function getLogs() {
        return $this->logs;
    }

    public function clearLogs() {
        $this->logs = null;
    }
}

class HttpResponse {
    public $header;
    public $body;
    
    public function __construct($curlResponse) {
        $exprResponse = explode("\n\r", $curlResponse, 2);
        $this->header = $exprResponse[0];
        $this->body = $exprResponse[1];
    }
}
?>