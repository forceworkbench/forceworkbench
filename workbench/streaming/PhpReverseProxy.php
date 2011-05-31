<?php

class PhpReverseProxy{
	public $port,$host,$forward_path,$content,$content_type,$user_agent,
		$XFF,$request_method,$IMS,$cacheTime,$cookie;

    private $http_code,$lastModified,$version,$resultHeader;

    function __construct(){
		$this->version="PHP Reverse Proxy (PRP) 1.0";
		$this->port="";
		$this->host="127.0.0.1";
        $this->forward_path="";
		$this->content="";
		$this->path="";
		$this->content_type="";
		$this->user_agent="";
		$this->http_code="";
		$this->XFF="";
		$this->request_method="GET";
		$this->IMS=false;	// If-Modified-Since
		$this->cacheTime=72000;
		$this->lastModified=gmdate("D, d M Y H:i:s",time()-72000)." GMT";
		$this->cookie="";
	}

	function translateURL($serverName) {
		$this->path = $this->forward_path . str_replace(dirname($_SERVER['PHP_SELF']), "", $_SERVER['REQUEST_URI']);
		if($_SERVER['QUERY_STRING']=="")
			return $this->translateServer($serverName).$this->path;
		else
		return $this->translateServer($serverName).$this->path."?".$_SERVER['QUERY_STRING'];
	}

    function translateServer($serverName) {
		$s = empty($_SERVER["HTTPS"]) ? ''
			: ($_SERVER["HTTPS"] == "on") ? "s"
			: "";
		$protocol = $this->left(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		if($this->port=="") 
			return $protocol."://".$serverName;
		else
			return $protocol."://".$serverName.":".$this->port;
	}

    function left($s1, $s2) {
		return substr($s1, 0, strpos($s1, $s2));
	}

    function preConnect(){
		$this->user_agent = $_SERVER['HTTP_USER_AGENT'];
		$this->request_method = $_SERVER['REQUEST_METHOD'];
		$tempCookie = "";
		foreach ($_COOKIE as $cookieName => $cookieValue) {
            if ($cookieName == "PHPSESSID") continue;
            if ($cookieName == "XDEBUG_SESSION") continue;
			$tempCookie = $tempCookie." $cookieName = $cookieValue;";
		}
		$this->cookie = $tempCookie;
		if(empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$this->XFF = $_SERVER['REMOTE_ADDR'];
		} else {
			$this->XFF = $_SERVER['HTTP_X_FORWARDED_FOR'].", ".$_SERVER['REMOTE_ADDR'];
		}
	}

    function connect(){
		if(empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
			$this->preConnect();
			$ch=curl_init();
			if($this->request_method=="POST"){
				curl_setopt($ch, CURLOPT_POST,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,file_get_contents("php://input"));
			}
			curl_setopt($ch,CURLOPT_URL,$this->translateURL($this->host));

            $header = array();
            foreach (getallheaders() as $key => $value) {
                if (in_array($key, array("Content-Type", "Accept"))) {
                    $headers[] = "$key: $value";
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			if($this->cookie!=""){
				curl_setopt($ch,CURLOPT_COOKIE,$this->cookie);
			}
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true); 
			curl_setopt($ch,CURLOPT_AUTOREFERER,true); 
			curl_setopt($ch,CURLOPT_HEADER,true);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			$output = curl_exec($ch);
			$info	= curl_getinfo( $ch );
            curl_close($ch);
			$this->postConnect($info,$output);
		}else {
			$this->lastModified=$_SERVER['HTTP_IF_MODIFIED_SINCE'];
			$this->IMS=true;
		}
	}

    function postConnect($info,$output){
		$this->content_type = $info["content_type"];
		$this->http_code = $info['http_code'];
		if (!empty($info['last_modified'])){
			$this->lastModified=$info['last_modified'];
		}
		$this->resultHeader = substr($output,0,$info['header_size']);
		$content = substr($output,$info['header_size']);
		if ($this->http_code == '200'){
			$this->content=$content;
		} 
	}

    function output(){
		$currentTimeString=gmdate("D, d M Y H:i:s",time());
		$expiredTime=gmdate("D, d M Y H:i:s",(time()+$this->cacheTime));
		if ($this->IMS){
			header("HTTP/1.1 304 Not Modified");
			header("Date: Wed, $currentTimeString GMT");
			header("Last-Modified: $this->lastModified");
			header("Server: $this->version");
		} else {
			header("HTTP/1.1 200 OK"); //todo: shoulnd't this be set to what the server actually responded???
			header("Date: Wed, $currentTimeString GMT");
			header("Content-Type: ".$this->content_type);
			header("Last-Modified: $this->lastModified");
			header("Cache-Control: max-age=$this->cacheTime");
			header("Expires: $expiredTime GMT");
			header("Server: $this->version");
			preg_match("/Set-Cookie:[^\n]*/i",$this->resultHeader,$result);
			foreach($result as $i=>$value){
				header($result[$i]);
			}
			echo($this->content);
		}
	}
}
?>