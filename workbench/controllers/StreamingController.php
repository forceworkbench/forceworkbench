<?php
require_once 'restclient/RestClient.php';

class StreamingController {

    private $pushTopics;

    function __construct() {
        $pushTopicSoql = "SELECT Id, Name, Query, ApiVersion FROM PushTopic";
        // hard coding API version for getting PushTopics because not available in prior versions
        //even their internal queries are available for all versions (i think)
        $url = "/services/data/v22.0/query?" . http_build_query(array("q" => $pushTopicSoql));
        $queryResponse = WorkbenchContext::get()->getRestDataConnection()->send("GET", $url, null, null, false);
        $this->pushTopics = json_decode($queryResponse->body)->records;
    }


    function printPushTopicOptions() {
        print "<option></option>\n";
        $newTemplate = array("Id"=>null, "Name"=>null, "ApiVersion"=>WorkbenchContext::get()->getApiVersion(), "Query"=>null);
        print "<option value='". json_encode($newTemplate) . "'>--Create New--</option>\n";
        foreach($this->pushTopics as $topic) {
            $topic->Query = htmlspecialchars($topic->Query, ENT_QUOTES);
            $topic->ApiVersion = strpos($topic->ApiVersion, ".") === false ? $topic->ApiVersion.".0" : $topic->ApiVersion;
            print "<option value='". json_encode($topic) . "'>" . $topic->Name . "</option>\n";
        }
    }

    function printApiVersionOptions() {
        foreach($GLOBALS['API_VERSIONS'] as $v) {
            print "<option value='$v'>$v</option>\n";
        }
    }
}

?>
