<?php
require_once 'JobInfo.php';
require_once 'BatchInfo.php';

/**
 * PHP BULK API CLIENT 21.0
 * @author Ryan Brainard
 *
 * BulkApiClient.php
 * Main client for interacting with the REST-based Force.com Bulk API 21.0
 * to asynchronously insert, update, and upsert data to Salesforce.
 * Requires PHP cURL library to be installed.
 *
 *
 * This client is NOT a product of or supported by salesforce.com, inc.
 * For support from the Open Source community, please visit the resources below:
 *
 * * Main Project Site
 *   http://code.google.com/p/forceworkbench
 *
 * * Feedback & Discussion
 *   http://groups.google.com/group/forceworkbench
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF
 * THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

class BulkApiClient {
    private $endpoint;
    private $sessionId;
    private $userAgent = "PHP-BulkApiClient/21.0";
    private $compressionEnabled = true;
    private $logs;
    private $loggingEnabled = false;

    /**
     * Create a new Bulk API Client from an existing Partner API enpoint and session id
     *
     * @param  $partnerEndpoint endpoint from Partner API
     * @param  $sessionId active Salesforce session id
     */
    public function __construct($partnerEndpoint, $sessionId) {
		if (!extension_loaded('curl')) {
			throw new Exception('Missing required cURL extension.');
		}
	
        $this->endpoint = $this->convertEndpointFromPartner($partnerEndpoint);
        $this->sessionId = $sessionId;
    }

    /**
     * @return string the user agent for this client
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    /**
     * @param $userAgent customized user agent for this client
     */
    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
    }

    /**
     * @return bool true if GZIP compression is enabled
     */
    public function isCompressionEnabled() {
        return $this->compressionEnabled;
    }

    /**
     * @param  $compressionEnabled true to enable compression
     */
    public function setCompressionEnabled($compressionEnabled) {
        $this->compressionEnabled = $compressionEnabled;
    }

    private function convertEndpointFromPartner($partnerEndpoint) {

        if (!$this->apiVersionIsAtLeast($partnerEndpoint, 16.0)) {
            throw new Exception("Bulk API operations only supported in API 16.0 and higher.");
        }

        $count = 1;
        $endpoint = str_replace("Soap/u", "async", $partnerEndpoint, $count);

        //strip off org id hint from end, if present.
        if (strpos($endpoint, "00D")) {
            $endpoint = substr($endpoint, 0, strripos($endpoint, "/"));
        }

        return $endpoint;
    }

    private function apiVersionIsAtLeast($endpoint, $minVersion) {
        preg_match('!/(\d{1,2}\.\d)!',$endpoint,$apiVersionMatches);
        return $apiVersionMatches[1] >= $minVersion;
    }

    /**
     * Creates a new job from a given in-memory JobInfo object and returns
     * a new JobInfo object populated with additional information
     * from the Bulk API
     *
     * @param JobInfo $job
     * @return JobInfo
     */
    public function createJob(JobInfo $job) {
        $this->validateJob($job);
        return new JobInfo($this->post($this->endpoint . "/job", "application/xml", $job->asXml()));
    }

    /**
     * Updates job from a given in-memory JobInfo object and returns
     * a new JobInfo object populated with additional information
     * from the Bulk API
     *
     * @param JobInfo $job
     * @return JobInfo updated
     */
    public function updateJob(JobInfo $job) {
        $this->validateJob($job);
        return new JobInfo($this->post($this->endpoint . "/job/" . $job->getId(), "application/xml", $job->asXml()));
    }

    private function validateJob(JobInfo $job) {
        if ($job->getContentType() == "CSV" && !$this->apiVersionIsAtLeast($this->endpoint, 17.0)) {
            throw new Exception("Content Type 'CSV' only supported in API 17.0 and higher.");
        }

        if ($job->getOpertion() == "delete" && !$this->apiVersionIsAtLeast($this->endpoint, 18.0)) {
            throw new Exception("Bulk API 'Delete' operation only supported in API 18.0 and higher.");
        }

        if ($job->getOpertion() == "hardDelete" && !$this->apiVersionIsAtLeast($this->endpoint, 19.0)) {
            throw new Exception("Bulk API 'Hard Delete' operation only supported in API 19.0 and higher.");
        }

        if (stristr($job->getContentType(), "ZIP_") && !$this->apiVersionIsAtLeast($this->endpoint, 20.0)) {
            throw new Exception("Zipped Content Types only supported in API 20.0 and higher.");
        }
    }

    /**
     * Convenience function for changing the state of a job identified by a given jobId
     *
     * @param  $jobId
     * @param  $state
     * @return JobInfo
     */
    public function updateJobState($jobId, $state) {
        $job = new JobInfo();
        $job->setId($jobId);
        $job->setState($state);
        return $this->updateJob($job);
    }

    /**
     * Query for the JobInfo of a given jobId
     *
     * @param  $jobId
     * @return JobInfo
     */
    public function getJobInfo($jobId) {
        return new JobInfo($this->get($this->endpoint . "/job/" . $jobId));
    }

    /**
     * Create a new Batch with the given data and associate with the given job
     *
     * @param JobInfo $job
     * @param  $data
     * @return BatchInfo
     */
    public function createBatch(JobInfo $job, $data) {
        if ($job->getContentType() == "CSV") {
            $contentType = "text/csv";
        } else if ($job->getContentType() == "XML") {
            $contentType = "application/xml";
        } else if ($job->getContentType() == "ZIP_CSV") {
            $contentType = "zip/csv";
        } else if ($job->getContentType() == "ZIP_XML") {
            $contentType = "zip/xml";
        } else {
            throw new Exception("Invalid content type specified for batch");
        }

        return new BatchInfo($this->post($this->endpoint . "/job/" . $job->getId() . "/batch", $contentType, $data));
    }

    /**
     * Retrieves the BatchInfo for a given jobId and batchId
     *
     * @param  $jobId
     * @param  $batchId
     * @return BatchInfo
     */
    public function getBatchInfo($jobId, $batchId) {
        return new BatchInfo($this->get($this->endpoint . "/job/" . $jobId . "/batch/" . $batchId));
    }

    /**
     * Finds all the BatchInfos associated with a given jobId
     *
     * @param  $jobId
     * @return array of BatchInfos
     */
    public function getBatchInfos($jobId) {
        $batchInfos = array();

        $batchInfoList = new SimpleXMLElement($this->get($this->endpoint . "/job/" . $jobId . "/batch"));
        foreach ($batchInfoList as $batchInfoListItem) {
            $batchInfos["$batchInfoListItem->id"] = new BatchInfo($batchInfoListItem->asXml());
        }

        return $batchInfos;
    }

    /**
     * Returns a copy of the sent request for a given jobId and batchId
     * Results can optionally be returned to a file handle if $toFile is set.
     *
     * @param  $jobId
     * @param  $batchId
     * @return mixed
     */
    public function getBatchRequest($jobId, $batchId, $toFile = null) {
        if (!$this->apiVersionIsAtLeast($this->endpoint, 19.0)) {
            throw new Exception("Gettiing a batch request is only supported in API 19.0 and higher.");
        }

        return $this->get($this->endpoint . "/job/" . $jobId . "/batch/" . $batchId . "/request", $toFile);
    }

    /**
     * Returns either the actual result (DML) or result-list (queries) for a given batch.
     * Results can optionally be returned to a file handle if $toFile is set.
     *
     * @param  $jobId
     * @param  $batchId
     * @return mixed
     */
    public function getBatchResults($jobId, $batchId, $toFile = null) {
        return $this->get($this->endpoint . "/job/" . $jobId . "/batch/" . $batchId . "/result", $toFile);
    }

    /**
     * Returns an array of resultIds for a given batch wih a result-list.
     * Currently, only applies to Query operations.
     *
     * @param  $jobId
     * @param  $batchId
     * @return array
     */
    public function getBatchResultList($jobId, $batchId, $toFile = null) {
        $resultListRaw = $this->getBatchResults($jobId, $batchId, $toFile);
        $resultListXml = new SimpleXMLElement($resultListRaw);
        $resultListArray = array();

        if (!isset($resultListXml->result)) {
            throw new Exception("No result-list found in the results for Batch " . $batchId);
        }

        foreach ($resultListXml->result as $resultId) {
            $resultListArray[] = (String) $resultId;
        }

        return $resultListArray;
    }

    /**
     * Returns an individual result for a given resultId in a batch wih a result-list.
     * Results can optionally be returned to a file handle if $toFile is set.
     * Currently, only applies to Query operations.
     *
     * @param  $jobId
     * @param  $batchId
     * @param  $resultId
     * @param  $toFile
     * @return mixed
     */
    public function getBatchResult($jobId, $batchId, $resultId, $toFile = null) {
        return $this->get($this->endpoint . "/job/" . $jobId . "/batch/" . $batchId . "/result/" . $resultId, $toFile);
    }

    private function http($isPost, $url, $contentType, $data, $toFile) {
        $this->log("INITIALIZING cURL \n" . print_r(curl_version(), true));

        $ch = curl_init();

        $httpHeaders = array(
            "X-SFDC-Session: " . $this->sessionId,
            "Accept: application/xml",
            "User-Agent: " . $this->userAgent,
            "Expect:"
        );
        if (isset($contentType)) {
            $httpHeaders[] = "Content-Type: $contentType; charset=UTF-8";
        }

        if($isPost) curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        if($isPost) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                                //TODO: use ca-bundle instead
        if($this->compressionEnabled) curl_setopt($ch, CURLOPT_ENCODING, "gzip");   //TODO: add  outbound compression support
        if (isset($toFile)) curl_setopt($ch, CURLOPT_FILE, $toFile);

        $this->log("REQUEST \n POST: $isPost \n URL: $url \n HTTP HEADERS: \n" . print_r($httpHeaders, true) . " DATA:\n " . htmlentities($data));

        $chResponse = curl_exec($ch);

        $this->log("RESPONSE \n" . isset($toFile) ? "Sent to file: " . $toFile : htmlentities($chResponse));

        if (curl_error($ch) != null) {
            $this->log("ERROR \n" . htmlentities(curl_error($ch)));
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return $chResponse;
    }

    private function get($url, $toFile = null) {
        return $this->http(false, $url, null, null, $toFile);
    }

    private function post($url, $contentType, $data) {
        return $this->http(true, $url, $contentType, $data, null);
    }


    //LOGGING FUNCTIONS

    /**
     * @return bool true if logging is enabled
     */
    public function isLoggingEnabled() {
        return $this->loggingEnabled;
    }

    /**
     * @param  $loggingEnabled enables logging if true
     */
    public function setLoggingEnabled($loggingEnabled) {
        $this->loggingEnabled = $loggingEnabled;
    }

    /**
     * @param  $txt text to log
     * @return pass through the input
     */
    protected function log($txt) {
        if ($this->loggingEnabled) {
            $this->logs .= $txt .= "\n\n";
        }
        return $txt;
    }

    /**
     * @param  $extLogs a log buffer external to this client
     * @return void
     */
    public function setExternalLogReference(&$extLogs) {
        $this->logs = &$extLogs;
    }

    /**
     * @return the log buffer
     */
    public function getLogs() {
        return $this->logs;
    }

    /**
     * clears log buffer
     */
    public function clearLogs() {
        $this->logs = null;
    }
}

?>