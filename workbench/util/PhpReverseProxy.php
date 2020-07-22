<?php
 
class PhpReverseProxy {
    public $host, $port, $forceSSL, $forward_path, $is_forward_path_static, $content, $content_type, $user_agent,
    $XFF, $request_method, $cookie_allowlist, $proxy_settings;

    private $http_code, $resultHeader, $cookie;

    function __construct() {
        $this->headers = array();
        $this->host = "";
        $this->port = "";
        $this->forceSSL = false;
        $this->forward_path = "";
        $this->content = "";
        $this->path = "";
        $this->content_type = "";
        $this->user_agent = "";
        $this->http_code = "";
        $this->XFF = "";
        $this->request_method = "GET";
        $this->cookie = "";
        $this->cookie_allowlist = array();
    }

    function translateURL($serverName) {
        $server = $this->translateServer($serverName);

        if ($this->is_forward_path_static) {
            return $server . $this->forward_path;
        }

        $this->path = $this->forward_path . str_replace(dirname($_SERVER['PHP_SELF']), "", $_SERVER['REQUEST_URI']);
        $queryString = ($_SERVER['QUERY_STRING'] == "")
                ? ""
                : "?" . $_SERVER['QUERY_STRING'];

        return $server . $this->path . $queryString;
    }

    function translateServer($serverName) {
        // use SSL if forced on proxy or original request was using SSL
        $protocol = "http" .
                    ($this->forceSSL || usingSslFromUserToWorkbench()
                        ? "s"
                        : "");

        $port = ($this->port == "") ? "" : ":" . $this->port;

        return $protocol . "://" . $serverName . $port;
    }

    function preConnect() {
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $this->request_method = $_SERVER['REQUEST_METHOD'];

        $allowedCookies = "";
        foreach ($_COOKIE as $cookieName => $cookieValue) {
            if ($cookieName == "PHPSESSID") continue;
            if ($cookieName == "XDEBUG_SESSION") continue;
            if (count($this->cookie_allowlist) > 0 && !in_array($cookieName, $this->cookie_allowlist)) continue;
            $allowedCookies .= trim($cookieName) . "=" . trim($cookieValue) ."; ";
        }
        $this->cookie = $allowedCookies;

        if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->XFF = $_SERVER['REMOTE_ADDR'];
        } else {
            $this->XFF = $_SERVER['HTTP_X_FORWARDED_FOR'] . ", " . $_SERVER['REMOTE_ADDR'];
        }
    }

    function connect() {
        $this->preConnect();
        $ch = curl_init();
        if ($this->request_method == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
        }
        curl_setopt($ch, CURLOPT_URL, $this->translateURL($this->host));

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //TODO: use ca-bundle instead
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);

        if ($this->proxy_settings != null) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy_settings["proxy_host"]);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_settings["proxy_port"]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy_settings["proxy_username"] . ":" . $this->proxy_settings["proxy_password"]);
        }

        foreach (self::getAllRequestHeaders() as $key => $value) {
            if (in_array($key, array("Content-Type", "Accept"))) {
                $this->headers[] = "$key: " . $value;
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        if ($this->cookie != "") {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $this->postConnect($ch, $info, $output);
        curl_close($ch);
    }

    function postConnect($ch, $info, $output) {
        if (curl_error($ch) != null) {
            $this->resultHeader = "HTTP/1.0 502 Bad Gateway";
            $this->content = "Workbench encountered an error proxying the request. Error:\n" . curl_error($ch);
            return;
        }

        $this->content_type = $info["content_type"];
        $this->http_code = $info['http_code'];

        $headerSize = $info['header_size'];
        if ($this->proxy_settings != null) {
            $proxyHeader = "HTTP/1.0 200 Connection established";
            if (strpos($output, $proxyHeader) === 0) {
                $headerSize += strlen($proxyHeader);
            }
        }

        $this->resultHeader = substr($output, 0, $headerSize);
        $this->content = substr($output, $headerSize);
    }

    function output() {
        $headerAllowlist = array("HTTP", "Date", "Content-Type", "Set-Cookie");
        foreach (explode("\r\n",$this->resultHeader) as $h) {
            foreach ($headerAllowlist as $whl) {
                $replaceExistingHeader = true;

                if (stripos($h, $whl) > -1) {
                    if (stripos("Set-Cookie", $whl) > -1) {
                        $replaceExistingHeader = false;

                        // ouch, recursive regex. strip off the end of the fwd path to make a new regex
                        $fwdPathRegEx = preg_replace("`(/\w+)/.*`", "$1(.*)", $this->forward_path);
                        $h = preg_replace("`[Pp]ath=([^;]*)$fwdPathRegEx`",
						                  "Path=". ((strlen(dirname($_SERVER['PHP_SELF'])) == 1)
										             ? "$1"
											         : (dirname($_SERVER['PHP_SELF'])."$1"))
										   , $h);
                    }

                    header($h, $replaceExistingHeader);
                    continue;
                }
            }
        }
        echo $this->content ;
    }

    static function getAllRequestHeaders() {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $out = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                $out[$key] = $value;
            } else if ($key == "CONTENT_TYPE") {
                $out["Content-Type"] = $value;
            } else if ($key == "CONTENT_LENGTH") {
                $out["Content-Length"] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }
}

?>
