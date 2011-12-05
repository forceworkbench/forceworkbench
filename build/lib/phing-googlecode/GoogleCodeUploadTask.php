<?php

// Copyright 2009 Geoff Catlin
// 
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
// 
//     http://www.apache.org/licenses/LICENSE-2.0
// 
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

require_once 'phing/Task.php';

/**
 * GoogleCodeUploadTask is an Phing task for uploading a file to a Google Code
 * project's downloads area.
 */
class GoogleCodeUploadTask extends Task {

	/**
	 * The boundary string used for encoding the HTTP request.
	 */
	protected static $boundary = '----------phing_googlecode_boundary_freedom';
	
	/**
	 * The local path to the file.
	 */
	protected $file;

	/**
	 * A comma-separated list of label strings with which to tag the file. (optional)
	 */
	protected $labels;

	/**
	 * The Google Code password for your account.
	 * Note that this is NOT your global Google account password!
	 */
	protected $password;

	/**
	 * The name of your project on Google Code.
	 */
	protected $projectName;

	/**
	 * 	A brief description for the file.
	 */
	protected $summary;

	/**
	 * Your Google account name (don't include @gmail.com).
	 */
	protected $username;

	/**
	 * Uploads the contents of the file to the Google Code project's download
	 * area. Performs the basic http authentication required by Google Code.
	 */
	public function main() {
		$body = array();

		// Prepare "summary" and "label" fields
		$fields = array(array('summary', $this->summary));
		if ($this->labels) {
			$labels = explode(',', $this->labels);
			foreach ($labels as $label) {
				$fields[] = array('label', trim($label));
			}
		}
		
		// Add the metadata about the upload first
		foreach ($fields as $field) {
			list($key, $value) = $field;
			$body[] = '--' . self::$boundary;
			$body[] = sprintf('Content-Disposition: form-data; name="%s"', $key);
			$body[] = '';
			$body[] = $value;
		}

		// Now add the file itself
		$body[] = '--' . self::$boundary;
		$body[] = sprintf('Content-Disposition: form-data; name="filename"; filename="%s"', $this->file);
		$body[] = 'Content-Type: application/octet-stream';
		$body[] = '';
		$body[] = file_get_contents($this->file);

		// Finalize the form body
		$body[] = '--' . self::$boundary . '--';
		$body[] = '';
		$body = implode("\r\n", $body);

		$host = $this->projectName . '.googlecode.com';
		$authToken = base64_encode($this->username . ':' . $this->password);
		$contentType = 'multipart/form-data; boundary=' . self::$boundary;
		$contentLength = strlen($body);
		
		// Build headers
		$headers = array(
			'Host: ' . $host,
			'Authorization: Basic ' . $authToken,
			'Content-Length: ' . $contentLength,
			'Content-Type: ' . $contentType,
			'User-Agent: phing-googlecode/0.1');
		$headers = implode("\r\n", $headers);

		// Build request
		$request = "POST /files HTTP/1.0\r\n";
		$request .= $headers . "\r\n\r\n";
		$request .= $body;
		
		// Send request and read response
		$fp = fsockopen('ssl://' . $host, 443, $errno, $errstr);
		fwrite($fp, $request);
		$response = stream_get_contents($fp);
		fclose($fp);
		
		$response = explode("\r\n", $response);
		if ($response[0] == 'HTTP/1.0 201 Created') {
			$location = substr($response[1], 10);
	    $this->log('Uploaded file: ' . $location);
		} elseif ($response[0] == 'HTTP/1.0 403 Forbidden') {
			$msg = "Unable to upload " . $this->file . ": File exists or exceeds max upload size, or filename was invalid.";
      throw new BuildException($msg, $this->location);
		}
	}

	/**
	 * The setter for the attribute "file"
	 */
	public function setFile($file) {
		$this->file = trim($file);
	}

	/**
	 * The setter for the attribute "labels"
	 */
	public function setLabels($labels) {
		$this->labels = trim($labels);
	}

	/**
	 * The setter for the attribute "password"
	 */
	public function setPassword($password) {
		$this->password = trim($password);
	}

	/**
	 * The setter for the attribute "projectName"
	 */
	public function setProjectName($projectName) {
		$this->projectName = trim($projectName);
	}

	/**
	 * The setter for the attribute "summary"
	 */
	public function setSummary($summary) {
		$this->summary = trim($summary);
	}

	/**
	 * The setter for the attribute "username"
	 */
	public function setUsername($username) {
		$this->username = trim($username);
	}

}
