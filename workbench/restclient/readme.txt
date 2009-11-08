PHP BULK API CLIENT 17.0
Distributed under the Open Source BSD License.
Developed By Ryan Brainard

The files in this folder make up a client for PHP developers to interact with the
REST-based Force.com Bulk API 17.0 to asynchronously insert, update, and
upsert data to Salesforce. Please see the individual files below for more
information:

* BulkApiClientSample.php -- START HERE: sample code and explations
* BulkApiClient.php -- main client code
* JobInfo.php -- represents a Job
* BatchInfo.php -- represents a Batch

This client also requires the PHP cURL library to be installed.
 
Note: This client was orginally created as part of Workbench, a complete
data describing, loading, and extracting tool using multiple Salesforce
APIs. It has been extracted from the larger application to remove any 
depenecies; however, it is still an integral part of Workbench and is
maintained as part of the forceworkbench project on Google Code. If you
simply want a working implenation of this client for users to load data into 
Salesforce, it is recommended to download Workbench. This client is designed
for developers who wish to integrate it into their PHP projects.
http://code.google.com/p/forceworkbench/ 