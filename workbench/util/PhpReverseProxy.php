<?php
 
class PhpReverseProxy {
    public $port, $host, $forward_path, $is_forward_path_static, $content, $content_type, $user_agent,
    $XFF, $request_method, $cookie, $proxy_settings;

    private $http_code, $resultHeader;

    function __construct() {
        $this->port = "";
        $this->host = "";
        $this->forward_path = "";
        $this->content = "";
        $this->path = "";
        $this->content_type = "";
        $this->user_agent = "";
        $this->http_code = "";
        $this->XFF = "";
        $this->request_method = "GET";
        $this->cookie = "";
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
        $protocol = "http" .
                    ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
                        ? "s"
                        : "");

        $port = ($this->port == "") ? "" : ":" . $this->port;

        return $protocol . "://" . $serverName . $port;
    }

    function preConnect() {
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $this->request_method = $_SERVER['REQUEST_METHOD'];

        global $config;
        $tempCookie = "";
        foreach ($_COOKIE as $cookieName => $cookieValue) {
            if ($cookieName == "PHPSESSID") continue;
            if ($cookieName == "XDEBUG_SESSION") continue;
            if (array_key_exists($cookieName, $config)) continue;
            $tempCookie = $tempCookie . " $cookieName = $cookieValue;";
        }
        $this->cookie = $tempCookie;

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

        if ($this->proxy_settings != null) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy_settings["proxy_host"]);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_settings["proxy_port"]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy_settings["proxy_username"] . ":" . $this->proxy_settings["proxy_password"]);
        }

        $headers = array();
        foreach (self::getAllRequestHeaders() as $key => $value) {
            if (in_array($key, array("Content-Type", "Accept"))) {
                $headers[] = "$key: " . $value;
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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
        $this->resultHeader = substr($output, 0, $info['header_size']);
        $this->content = substr($output, $info['header_size']);
    }

    function output() {
        $headerWhitelist = array("HTTP", "Date", "Content-Type", "Set-Cookie");
        foreach (explode("\r\n",$this->resultHeader) as $h) {
            foreach ($headerWhitelist as $whl) {
                if (stripos($h, $whl) > -1) {
                    if (stripos("Set-Cookie", $whl) > -1) {
                        $h = preg_replace("`path=([^;]*)$this->forward_path`", 
						                  "path=". ((strlen(dirname($_SERVER['PHP_SELF'])) == 1)
										             ? "$1"
											         : (dirname($_SERVER['PHP_SELF'])."$1"))
										   , $h);
                    }

                    header($h, true);
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
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }
}

?>
