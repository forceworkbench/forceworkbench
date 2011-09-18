<?php
// STEP 1: OBTAIN SESSION ID AND ENDPOINT FROM PARTNER API. REPLACE WITH YOUR ENDPOINT AND SESSION ID.
// For demo purposes, these can just be GET parameters on this page, but should be
// obtained from the login() call using the Force.com Partner API with a username and password.
// In PHP, it is recommended to use the PHP Toolkit to call the Partner API. For more info:
//
// Partner API Doc: http://www.salesforce.com/us/developer/docs/api/index.htm
// PHP Toolkit: http://wiki.developerforce.com/index.php/PHP_Toolkit
//
// If these required parameters are not provided, you will be redirected to index.php, 
// which has a form to conveniently provide these parameters to any script in this folder.

if (!isset($_REQUEST["partnerApiEndpoint"]) || !isset($_REQUEST["sessionId"])) {
    header("Location: index.php") ;    
}

// STEP 2: INITIALIZE THE BULK API CLIENT
require_once '../BulkApiClient.php';
$myBulkApiConnection = new BulkApiClient($_REQUEST["partnerApiEndpoint"], $_REQUEST["sessionId"]);
$myBulkApiConnection->setLoggingEnabled(true); //optional, but using here for demo purposes
$myBulkApiConnection->setCompressionEnabled(true); //optional, but recommended. defaults to true.


// STEP 3: CREATE A NEW JOB
// create in-memory representation of the job
$job = new JobInfo();
$job->setObject("Contact");
$job->setOpertion("insert");
$job->setContentType("CSV");
$job->setConcurrencyMode("Parallel");                         //can also set to Serial
//$job->setExternalIdFieldName("My_Contact_External_Id");     //used with Upsert operations
//$job->setAssignmentRuleId("01Q60000000EPDU");             //optional for objects that support Assignment Rules

//send the job to the Bulk API and pass back returned JobInfo to the same variable
$job = $myBulkApiConnection->createJob($job);


// STEP 4. CREATE A NEW BATCH
//prep the data. normally this would be loaded from a file,
//but showing in plain text for demo purposes
$csvData = "\"FirstName\",\"LastName\",\"Email\"\n" .      //header row
           "\"Tom\",\"Collins\",\"tom@collins.com\"\n" .   //data row #1 
           "\"Mary\",\"Martini\",\"#N/A\"\n";              //data row #2 - using #N/A to null out .Email

$batch = $myBulkApiConnection->createBatch($job, $csvData);

//add more and more batches.... (here, we will only do one)


// STEP 5. CLOSE THE JOB
$myBulkApiConnection->updateJobState($job->getId(), "Closed");


// STEP 6: MONITOR BATCH STATUS UNTIL DONE
while($batch->getState() == "Queued" || $batch->getState() == "InProgress") {
    $batch = $myBulkApiConnection->getBatchInfo($job->getId(), $batch->getId());
    sleep(5); //wait for 5 seconds before polling again. in the real world, probably make this exponential as to not ping the server so much
}


// STEP 7: GET BATCH RESULTS
$batchResults = $myBulkApiConnection->getBatchResults($job->getId(), $batch->getId());


// PRINT EVERYTHING THAT HAPPENED ABOVE
print "<pre>" .
      "PHP BULK API CLIENT SAMPLE CODE OUTPUT\n" . 
      "This is the output of the PHP Bulk API Client Sample Code. View the source code for step-by-step explanations.\n\n";
print "== CSV DATA == \n" . htmlspecialchars($csvData) . "\n\n";
print "== BATCH RESULTS == \n" . htmlspecialchars($batchResults) . "\n\n";
print "== CLIENT LOGS == \n" . $myBulkApiConnection->getLogs() . "\n\n";
$myBulkApiConnection->clearLogs(); //clear log buffer
print "</pre>";

?>