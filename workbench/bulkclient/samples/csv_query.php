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
$job->setOpertion("query");
$job->setContentType("CSV");
$job->setConcurrencyMode("Parallel");                         //can also set to Serial

//send the job to the Bulk API and pass back returned JobInfo to the same variable
$job = $myBulkApiConnection->createJob($job);

// STEP 4. CREATE A NEW BATCH
//prep the query and create a batch from it
$soql =  "SELECT Id, Name FROM Contact LIMIT 10";

$batch = $myBulkApiConnection->createBatch($job, $soql);

//add more and more batches.... (here, we will only do one)


// STEP 5. CLOSE THE JOB
$myBulkApiConnection->updateJobState($job->getId(), "Closed");


// STEP 6: MONITOR BATCH STATUS UNTIL DONE
while($batch->getState() == "Queued" || $batch->getState() == "InProgress") {
    $batch = $myBulkApiConnection->getBatchInfo($job->getId(), $batch->getId());
    sleep(5); //wait for 5 seconds before polling again. in the real world, probably make this exponential as to not ping the server so much
}


// STEP 7: GET BATCH RESULTS
// For queries, use get back an array of resultIds
$resultList = $myBulkApiConnection->getBatchResultList($job->getId(), $batch->getId());

// then retrieve the actual results and place in an array.
// the CSV data is left in raw form for simplicity 
foreach ($resultList as $resultId) {
    $queryResults["$resultId"] = $myBulkApiConnection->getBatchResult($job->getId(), $batch->getId(), $resultId);
}

// PRINT EVERYTHING THAT HAPPENED ABOVE
print "<pre>" .
      "PHP BULK API CLIENT SAMPLE CODE OUTPUT\n" . 
      "This is the output of the PHP Bulk API Client Sample Code. View the source code for step-by-step explanations.\n\n";
print "== SOQL QUERY STRING == \n" . htmlspecialchars($soql) . "\n\n";
print "== QUERY RESULTS == \n" . htmlspecialchars(print_r($queryResults, true)) . "\n\n";
print "== CLIENT LOGS == \n" . $myBulkApiConnection->getLogs() . "\n\n";
$myBulkApiConnection->clearLogs(); //clear log buffer
print "</pre>";

?>