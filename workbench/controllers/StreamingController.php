<?php
require_once 'restclient/RestClient.php';

class StreamingController {

    private $pushTopics;

    const restBaseUrl = "/services/data/v22.0";

    function __construct() {
        if (isset($_REQUEST['PUSH_TOPIC_DML_DELETE'])) {
            $this->deletePushTopic($_REQUEST['pushTopicDmlForm_Id']);
        }

        $this->refreshPushTopics();
    }

    private function refreshPushTopics() {
        $pushTopicSoql = "SELECT Id, Name, Query, ApiVersion FROM PushTopic";
        // hard coding API version for getting PushTopics because not available in prior versions
        //even their internal queries are available for all versions (i think)
        $url = self::restBaseUrl . "/query?" . http_build_query(array("q" => $pushTopicSoql));
        $queryResponse = WorkbenchContext::get()->getRestDataConnection()->send("GET", $url, null, null, false);
        $this->pushTopics = json_decode($queryResponse->body)->records;
    }

    function deletePushTopic($id) {
        $url = self::restBaseUrl . "/sobjects/PushTopic/" . $id;
        $response = WorkbenchContext::get()->getRestDataConnection()->send("DELETE", $url, null, null, false);
        if (strpos($response->header, "204 No Content") === false) {
            throw new Exception("Failed to delete: $id");
        }
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
