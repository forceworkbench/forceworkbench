<?php
require_once 'restclient/RestClient.php';

class StreamingController {

    private $restBaseUrl;
    private $restApi;
    private $selectedTopic;
    private $pushTopics;
    private $infos;
    private $errors;
    private $enabled;
    private $isAjax;

    function __construct() {
        $this->restApi = WorkbenchContext::get()->getRestDataConnection();
        $this->infos = array();
        $this->errors = array();
        $this->enabled = true;
        $this->isAjax = false;
        $this->restBaseUrl = "/services/data/v" . WorkbenchContext::get()->getApiVersion();

        if (get_magic_quotes_gpc()) {
            foreach ($_REQUEST as $fieldName => &$r) {
                if (strpos($fieldName, "pushTopicDmlForm_") > -1) {
                    $r =& stripslashes($r);
                }
            }
        }

        $this->selectedTopic = new PushTopic(
            isset($_REQUEST['pushTopicDmlForm_Id'])         ? $_REQUEST['pushTopicDmlForm_Id']         : null,
            isset($_REQUEST['pushTopicDmlForm_Name'])       ? $_REQUEST['pushTopicDmlForm_Name']       : null,
            isset($_REQUEST['pushTopicDmlForm_ApiVersion']) ? $_REQUEST['pushTopicDmlForm_ApiVersion'] : null,
            isset($_REQUEST['pushTopicDmlForm_Query'])      ? $_REQUEST['pushTopicDmlForm_Query']      : null);

        if (isset($_REQUEST['PUSH_TOPIC_DML'])) {
            $this->isAjax = true;

            if ($_REQUEST['PUSH_TOPIC_DML'] == "SAVE") {
                $this->save();
            } else if ($_REQUEST['PUSH_TOPIC_DML'] == "DELETE") {
                $this->delete();
            }
        }

        $this->refresh();
    }

    private function refresh() {
        $pushTopicSoql = "SELECT Id, Name, Query, ApiVersion FROM PushTopic";
        $url = $this->restBaseUrl . "/query?" . http_build_query(array("q" => $pushTopicSoql));

        try {
            $queryResponse = $this->restApi->send("GET", $url, null, null, false);

            if (strpos($queryResponse->header, "200 OK") === false) {
                $this->errors[] = "Could not load Push Topics. Ensure the both the REST and Streaming APIs are enabled for this organization.";
                $this->enabled = false;
                return;
            }

            $this->pushTopics = json_decode($queryResponse->body)->records;
        } catch (Exception $e) {
            $this->errors[] = "Unknown Error Fetching Push Topics:\n" . $e->getMessage();
        }
    }

    private function save() {
        if ($this->selectedTopic->Id != null && $this->selectedTopic->Id != "undefined") {
            $this->dml("PATCH", "Updated", "Updating", "/" . $this->selectedTopic->Id, $this->selectedTopic->toJson(false));
        } else {
            $this->dml("POST", "Created", "Creating", "", $this->selectedTopic->toJson(false));
        }
    }

    private function delete() {
        $this->dml("DELETE", "Deleted", "Deleting", "/".$this->selectedTopic->Id, null);
    }

    private function dml($method, $opPastLabel, $opProgLabel, $urlTail, $data) {
        $headers = array("Content-Type: application/json");
        $url = $this->restBaseUrl . "/sobjects/PushTopic" . $urlTail;

        try {
            $response = $this->restApi->send($method, $url, $headers, $data, false);
        } catch (Exception $e) {
            $this->errors[] = "Unknown Error $opProgLabel Push Topic\n:" . $e->getMessage();
            return;
        }

        if (strpos($response->header, "201 Created") > 0 || strpos($response->header, "204 No Content") > 0) {
            $this->infos[] = "Push Topic $opPastLabel Successfully";
        } else {
            $body = json_decode($response->body);
            $this->errors[] = "Error $opProgLabel Push Topic:\n" . $body[0]->message;
        }
    }

    function getMessages() {
        ob_start();
        if (count($this->errors) > 0) displayError($this->errors);
        if (count($this->infos) > 0)  displayInfo($this->infos);
        return ob_get_clean();
    }

    function getPushTopicOptions() {
        $options = "";

        $options .= "<option></option>\n";

        if (!$this->isEnabled()) {
            return $options;
        }

        $selected = count($this->pushTopics) == 0 ? "selected='selected'" : "";
        $options .= "<option value='". PushTopic::template()->toJson() . "' $selected>--Create New--</option>\n";
        foreach($this->pushTopics as $topic) {
            $topic->Name = htmlspecialchars($topic->Name, ENT_QUOTES);
            $topic->Query = htmlspecialchars($topic->Query, ENT_QUOTES);
            $topic->ApiVersion = strpos($topic->ApiVersion, ".") === false ? $topic->ApiVersion.".0" : $topic->ApiVersion;
            $selected = $topic->Name == $this->selectedTopic->Name ? "selected='selected'" : "";
            $options .= "<option value='". json_encode($topic) . "' $selected>" . $topic->Name . "</option>\n";
        }

        return $options;
    }

    function getApiVersionOptions() {
        $options = "";
        foreach($GLOBALS['API_VERSIONS'] as $v) {
            $options .= "<option value='$v'>$v</option>\n";
        }
        return $options;
    }

    function getStreamingConfig() {
        $streamingConfig["handshakeOnLoad"] = true; // TODO: make this configurable
        $streamingConfig["csrfToken"] = getCsrfToken();

        // configs in "$streamingConfig["cometdConfig"]" are loaded into CometD in JS and need to match their format
        $streamingConfig["cometdConfig"]["logLevel"] = "info";
        $streamingConfig["cometdConfig"]["appendMessageTypeToURL"] = false;
        $streamingConfig["cometdConfig"]["advice"]["timeout"] = (int) WorkbenchConfig::get()->valueOrElse("streamingAdviceTimeout", 25000);
        $streamingConfig["cometdConfig"]["advice"]["interval"] = 0;
        $streamingConfig["cometdConfig"]["advice"]["reconnect"] = "retry";
        $streamingConfig["cometdConfig"]["url"] =
            "http" . (usingSslFromUserToWorkbench() ? "s" : "") ."://" .
            $_SERVER['HTTP_HOST'] .
            str_replace('\\', '/', dirname(htmlspecialchars($_SERVER['PHP_SELF']))) .
			(strlen(dirname(htmlspecialchars($_SERVER['PHP_SELF']))) == 1 ? "" : "/") .
            "cometdProxy.php";

        return json_encode($streamingConfig);
    }

    function isEnabled() {
        return $this->enabled;
    }

    function isAjax() {
        return $this->isAjax;
    }

    function getAjaxResponse() {
        $ajaxResponse['failed'] = count($this->errors) > 0;
        $ajaxResponse['messages'] = $this->getMessages();
        $ajaxResponse['pushTopicOptions'] = $this->getPushTopicOptions();

        return json_encode($ajaxResponse);
    }
}

class PushTopic {
    public $Id, $Name, $ApiVersion, $Query;

    function __construct($id, $name, $apiVersion, $query) {
        $this->Id = $id;
        $this->Name = $name;
        $this->ApiVersion = $apiVersion;
        $this->Query = $query;
    }

    static function template() {
        return new PushTopic(null, null, WorkbenchContext::get()->getApiVersion(), null);
    }

    static function fromJson($jsonStr) {
        $json = json_decode($jsonStr);

        return new PushTopic($json->Id, $json->Name, $json->ApiVersion, $json->Query);
    }

    function toJson($includeId = true) {
        $clone = $this;

        if (!$includeId) {
            unset($clone->Id);
        }

        return json_encode($clone);
    }
}

?>
