<?php
require_once 'JobInfo.php';
require_once 'BatchInfo.php';

/**
 * PHP BULK API CLIENT
 * @author Ryan Brainard
 *
 * BulkApiClient.php
 * Main client for interacting with the REST-based Force.com Bulk API
 * to asynchronously insert, update, and upsert data to Salesforce.
 * Requires PHP cURL library to be installed.
 *
 *
 * This client is NOT a supported product of or supported by salesforce.com, inc.
 * For support from the Open Source community, please visit the resources below:
 *
 * * Main Project Site
 *   https://github.com/ryanbrainard/forceworkbench
 *
 * * Feedback & Discussion
 *   http://groups.google.com/group/forceworkbench
 *
 * Copyright (c) 2013, salesforce.com, inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided
 * that the following conditions are met:
 *
 *    Redistributions of source code must retain the above copyright notice, this list of conditions and the
 *    following disclaimer.
 *
 *    Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *    the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *    Neither the name of salesforce.com, inc. nor the names of its contributors may be used to endorse or
 *    promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

class BulkApiClient {

    const URL_SLASH = "/";
    const JOB = "job";
    const BATCH = "batch";
    const REQUEST = "request";
    const RESULT = "result";

    const CONTENT_TYPE_XML = "application/xml";
    const CONTENT_TYPE_CSV = "text/csv";
    const CONTENT_TYPE_ZIP_CSV = "zip/csv";
    const CONTENT_TYPE_ZIP_XML = "zip/xml";


    private $endpoint;
    private $sessionId;
    private $proxySettings;
    private $userAgent = "PHP-BulkApiClient/25.0.0";
    private $compressionEnabled = true;
    private $includeSessionCookie = false;
    private $logs;
    private $loggingEnabled = false;
    const CSV = "CSV";
    const XML = "XML";
    const ZIP_CSV = "ZIP_CSV";
    const ZIP_XML = "ZIP_XML";

    /**
     * Create a new Bulk API Client from an existing Partner API enpoint and session id
     *
     * @param  string $endpoint endpoint from Async/Bulk, Partner, or Enterprise APIs
     * @param  string $sessionId active Salesforce session id
     */
    public function __construct($endpoint, $sessionId) {
		if (!extension_loaded('curl')) {
			throw new Exception('Missing required cURL extension.');
		}

        if ($endpoint == null) {
            throw new Exception("Endpoint not set.");
        }

        if ($sessionId == null) {
            throw new Exception("Session Id not set.");
        }
	
        $this->endpoint = $this->convertEndpoint($endpoint);
        $this->sessionId = $sessionId;
    }

    /**
     * Sets proxy settings as an array with the keys:
     * "proxy_host", "proxy_port", "proxy_username", "proxy_password"
     *
     * @param array $proxySettings
     */
    public function setProxySettings($proxySettings) {
        $this->proxySettings = $proxySettings;
    }

    /**
     * @return string the user agent for this client
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent customized user agent for this client
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
     * @param  bool $compressionEnabled true to enable compression
     */
    public function setCompressionEnabled($compressionEnabled) {
        $this->compressionEnabled = $compressionEnabled;
    }

    /**
     * @return bool true is session id is included as a cookie
     */
    public function getIncludeSessionCookie() {
        return $this->includeSessionCookie;
    }

    /**
     * @param $includeSessionCookie true to have session id included as a cookie
     */
    public function setIncludeSessionCookie($includeSessionCookie) {
        $this->includeSessionCookie = $includeSessionCookie;
    }

    private function convertEndpoint($endpoint) {
        $endpoint = preg_replace("!Soap/\w/(\d{1,2}\.\d)(/)?(00D.*)?!", "async/$1", $endpoint);

        if (preg_match("!https?://.*/services/async/\d{1,2}\.\d$!", $endpoint) == 0) {
            throw new Exception("Invalid endpoint format: " . $endpoint);
        }

        if (!$this->apiVersionIsAtLeast($endpoint, 16.0)) {
            throw new Exception("Bulk API operations only supported in API 16.0 and higher.");
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
        $createdJob = $this->post($this->url(array(self::JOB)), self::CONTENT_TYPE_XML, $job->asXml());
        return new JobInfo($createdJob);
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
        $updatedJob = $this->post($this->url(array(self::JOB, $job->getId())), self::CONTENT_TYPE_XML, $job->asXml());
        return new JobInfo($updatedJob);
    }

    private function validateJob(JobInfo $job) {
        if ($job->getContentType() == self::CSV && !$this->apiVersionIsAtLeast($this->endpoint, 17.0)) {
            throw new Exception("Content Type 'CSV' only supported in API 17.0 and higher.");
        }

        if ($job->getOpertion() == "delete" && !$this->apiVersionIsAtLeast($this->endpoint, 18.0)) {
            throw new Exception("Bulk API 'Delete' operation only supported in API 18.0 and higher.");
        }

        if ($job->getOpertion() == "hardDelete" && !$this->apiVersionIsAtLeast($this->endpoint, 19.0)) {
            throw new Exception("Bulk API 'Hard Delete' operation only supported in API 19.0 and higher.");
        }

        if (in_array($job->getContentType(), array(self::ZIP_CSV, self::ZIP_XML)) && !$this->apiVersionIsAtLeast($this->endpoint, 20.0)) {
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
        return new JobInfo($this->get($this->url(array(self::JOB, $jobId))));
    }

    /**
     * Create a new Batch with the given data and associate with the given job
     *
     * @param JobInfo $job
     * @param  $data
     * @return BatchInfo
     */
    public function createBatch(JobInfo $job, $data) {
        if ($job->getContentType() == self::CSV) {
            $contentType = self::CONTENT_TYPE_CSV;
        } else if ($job->getContentType() == self::XML) {
            $contentType = self::CONTENT_TYPE_XML;
        } else if ($job->getContentType() == self::ZIP_CSV) {
            $contentType = self::CONTENT_TYPE_ZIP_CSV;
        } else if ($job->getContentType() == self::ZIP_XML) {
            $contentType = self::CONTENT_TYPE_ZIP_XML;
        } else {
            throw new Exception("Invalid content type specified for batch");
        }

        return new BatchInfo($this->post($this->url(array(self::JOB, $job->getId(), self::BATCH)), $contentType, $data));
    }

    /**
     * Retrieves the BatchInfo for a given jobId and batchId
     *
     * @param  $jobId
     * @param  $batchId
     * @return BatchInfo
     */
    public function getBatchInfo($jobId, $batchId) {
        return new BatchInfo($this->get($this->url(array(self::JOB, $jobId, self::BATCH, $batchId))));
    }

    /**
     * Finds all the BatchInfos associated with a given jobId
     *
     * @param  $jobId
     * @return array of BatchInfos
     */
    public function getBatchInfos($jobId) {
        $batchInfos = array();

        $batchInfoList = new SimpleXMLElement($this->get($this->url(array(self::JOB, $jobId, self::BATCH))));
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
            throw new Exception("Getting a batch request is only supported in API 19.0 and higher.");
        }

        return $this->get($this->url(array(self::JOB, $jobId, self::BATCH, $batchId, self::REQUEST)), $toFile);
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
        return $this->get($this->url(array(self::JOB, $jobId, self::BATCH, $batchId, self::RESULT)), $toFile);
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
        return $this->get($this->url(array(self::JOB, $jobId, self::BATCH, $batchId, self::RESULT, $resultId)), $toFile);
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                                //TODO: use ca-bundle instead
        if($this->compressionEnabled) curl_setopt($ch, CURLOPT_ENCODING, "gzip");   //TODO: add  outbound compression support

        if ($this->proxySettings != null) {
            if (isset($this->proxySettings["proxy_host"])) curl_setopt($ch, CURLOPT_PROXY, $this->proxySettings["proxy_host"]);
            if (isset($this->proxySettings["proxy_port"])) curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxySettings["proxy_port"]);
            if (isset($this->proxySettings["proxy_username"]) && isset($this->proxySettings["proxy_password"])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxySettings["proxy_username"] . ":" . $this->proxySettings["proxy_password"]);
            }
        }

        $cookies = array();
        if ($this->includeSessionCookie) {
            $cookies[] = "sid=" . $this->sessionId;
        }

        if (count($cookies) > 0) {
            curl_setopt($ch, CURLOPT_COOKIE, implode("; ", $cookies));
        }

        if (isset($toFile)) {
            curl_setopt($ch, CURLOPT_FILE, $toFile);
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        }

        $this->log("REQUEST \n POST: $isPost \n URL: $url \n HTTP HEADERS: \n" . print_r($httpHeaders, true) . " DATA:\n " . htmlspecialchars($data));

        $chResponse = curl_exec($ch);

        $this->log("RESPONSE \n" . (isset($toFile) ? ("Sent to file: " . $toFile) : htmlspecialchars($chResponse)));

        if (curl_error($ch) != null) {
            $this->log("ERROR \n" . htmlspecialchars(curl_error($ch)));
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

    private function url(array $parts) {
        return $this->endpoint . self::URL_SLASH . implode(self::URL_SLASH, $parts);
    }


    //LOGGING FUNCTIONS

    /**
     * @return bool true if logging is enabled
     */
    public function isLoggingEnabled() {
        return $this->loggingEnabled;
    }

    /**
     * @param  $loggingEnabled bool enables logging if true
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
