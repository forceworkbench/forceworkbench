<?php
/**
 * PHP BULK API CLIENT 18.0 SAMPLE CODE
 * @author Ryan Brainard
 * 
 * BulkApiClientSample.php
 * Sample Code for the using the PHP Bulk API Client 18.0 to
 * bulk load records into Salesforce. See the comments inline
 * to understand what is happening and execute the code to 
 * see it in action and see output. 
 * 
 * Remember to replace the hardcoded $sessionId and $partnerApiEndpoint
 * with your own before execution.
 * 
 * For more information about the Force.com Bulk API see:
 * http://www.salesforce.com/us/developer/docs/api_asynch/index.htm
 * 
 * For a working implementation of this client, download Workbench:
 * http://code.google.com/p/forceworkbench/
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

try{ 
// nornmally more granular exception handling would be recommened, but doing one big block here for readability
	
	// STEP 1: OBTAIN SESSION ID AND ENDPOINT FROM PARTNER API. REPLACE WITH YOUR ENDPOINT AND SESSION ID.
	// Hardcoding $partnerApiEndpoint and $sessionId values here for demo purposes, but should be 
	// obtained from the login() call using the Force.com Partner API with a username and password. 
	// In PHP, it is recommended to use the PHP Toolkit to call the Partner API. For more info:
	//
	// Partner API Doc: http://www.salesforce.com/us/developer/docs/api/index.htm
	// PHP Toolkit: http://wiki.developerforce.com/index.php/PHP_Toolkit 
	$partnerApiEndpoint = "https://na4-api.salesforce.com/services/Soap/u/17.0/00D600000006veT";
	$sessionId = "00D600000006veT!AQkAQBHagP4AUVvAZmtkxdKSwpIBqlwuT59BXuf_.P4TvOrSvWlBsNJy7rWWZY0P2q15vbrO8.dTjsipPRJaHlupekHEsv56";
	
	
	// STEP 2: INITIALIZE THE BULK API CLIENT
	require_once 'BulkApiClient.php';
	$myBulkApiConnection = new BulkApiClient($partnerApiEndpoint, $sessionId);
	$myBulkApiConnection->setLoggingEnabled(true); //optional, but using here for demo purposes
	$myBulkApiConnection->setCompressionEnabled(true); //optional, but recommended. defaults to true.
	
	
	// STEP 3: CREATE A NEW JOB
	// create in-memory representation of the job
	$job = new JobInfo();
	$job->setObject("Contact");
	$job->setOpertion("insert");
	$job->setContentType("CSV"); 								//can also set to XML
	$job->setConcurrencyMode("Parallel"); 						//can also set to Serial
	//$job->setExternalIdFieldName("My_Contact_External_Id"); 	//used with Upsert operations
	//$job->setAssignmentRuleId("01Q60000000EPDU"); 			//optional for objects that support Assignment Rules
	
	//send the job to the Bulk API and pass back returned JobInfo to the same variable
	$job = $myBulkApiConnection->createJob($job);
	
	
	// STEP 4. CREATE A NEW BATCH
	//prep the data. normally this would be loaded from a file, 
	//but showing in plain text for demo purposes
	$csvData = "\"FirstName\",\"LastName\",\"Email\"\n" . 	 //header row 
			   "\"Tom\",\"Collins\",\"tom@collins.com\"\n" . //data row #1 - using #N/A to null out .Email
			   "\"Mary\",\"Martini\",\"#N/A\"\n";    		 //data row #2
	
	$batch = $myBulkApiConnection->createBatch($job, $csvData);
	
	//add more and more batches.... (here, we will only do one)
	
	
	// STEP 5. CLOSE THE JOB
	$myBulkApiConnection->updateJobState($job->getId(), "Closed");
	
	
	// STEP 6: MONITOR BATCH STATUS UNTIL DONE
	while($batch->getState() == "Queued" || $batch->getState() == "InProgress"){
		$batch = $myBulkApiConnection->getBatchInfo($job->getId(), $batch->getId());
		sleep(5); //wait for 5 seconds before polling again. in the real world, probably make this exponential as to not ping the server so much
	}
	
	
	// STEP 7: GET BATCH RESULTS
	$batchResults = $myBulkApiConnection->getBatchResults($job->getId(), $batch->getId());
	
	
} catch (Exception $ex){
	print "<div style='color: red'>ERROR: " . $ex->getMessage() . "</div>"; 
}

// PRINT EVERYTHING THAT HAPPENED ABOVE
print "<pre>" .
	  "PHP BULK API CLIENT 17.0 SAMPLE CODE OUTPUT\n" . 
	  "This is the output of the PHP Bulk API Client Sample Code. View the source code for step-by-step explanations.\n\n";
print "== CSV DATA == \n" . $csvData . "\n\n";
print "== BATCH RESULTS == \n" . $batchResults . "\n\n";
print "== CLIENT LOGS == \n" . $myBulkApiConnection->getLogs() . "\n\n";
$myBulkApiConnection->clearLogs(); //clear log buffer
print "</pre>";

?>