<?php
require_once "PhpReverseProxy.php";

$proxy = new PhpReverseProxy();
$proxy->port = "";
$proxy->host = "cs4.salesforce.com";
$proxy->forward_path = "/cometd";
$proxy->connect();
$proxy->output();

//$ch = curl_init();
//
//$headers = array();
//foreach (getallheaders() as $key => $value) {
//    if ($key == "Host") continue;
//    if ($key == "Cookie") continue;
//    $headers[] = "$key: $value";
//}
////curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//curl_setopt($ch, CURLOPT_COOKIE, "sid=00DP00000005jxb!AQwAQC7pRvVD62pYFhV93coxeTKYbRu9ktsTT28kP8h.vEb79KWsShGaAjN7_Vh96t1oi9Qe0pyuGy2Qs17gn2bAiwQRjHas;");
//curl_setopt($ch, CURLOPT_POST, 1);
////curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
//curl_setopt($ch, CURLOPT_POSTFIELDS, "message=%5B%7B%22version%22%3A%221.0%22%2C%22minimumVersion%22%3A%220.9%22%2C%22channel%22%3A%22%2Fmeta%2Fhandshake%22%2C%22id%22%3A%2224%22%2C%22supportedConnectionTypes%22%3A%5B%22long-polling%22%2C%22long-polling-json-encoded%22%2C%22callback-polling%22%5D%2C%22timestamp%22%3A%22Sat%2C%2028%20May%202011%2005%3A21%3A10%20GMT%22%2C%22ext%22%3A%7B%22ack%22%3Atrue%7D%7D%5D");
//curl_setopt($ch, CURLOPT_URL, "https://cs4.salesforce.com/cometd");
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_HEADER, 1);
//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
////curl_setopt($ch, CURLOPT_VERBOSE, 1);
//
//$rawResponse = curl_exec($ch);
//if (curl_error($ch) != null) {
//    throw new Exception(curl_error($ch));
//}
//curl_close($ch);
//
//$responseHeadersAndBody = explode("\n\r", $rawResponse, 2);
//foreach(explode("\n", $responseHeadersAndBody[0]) as $responseHeader) {
//    header($responseHeader);
//}
//$responseBody = $responseHeadersAndBody[1];
//echo $responseBody;
?>
