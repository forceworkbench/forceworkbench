PHP BULK API CLIENT
Distributed under the Open Source BSD License.
Developed By Ryan Brainard

The files in this folder make up a client for PHP developers to interact with the
REST-based Force.com Bulk API to asynchronously insert, update, upsert, and
query data to Salesforce. Please see the individual files below for more
information:

* BulkApiClientSample.php -- START HERE: sample code and explanations
* BulkApiClient.php -- main client code
* JobInfo.php -- represents a Job
* BatchInfo.php -- represents a Batch

This client also requires the PHP cURL library to be installed.

Note: This client was originally created as part of Workbench, a complete
data describing, loading, and extracting tool using multiple Salesforce
APIs. It has been extracted from the larger application to remove any 
dependencies; however, it is still an integral part of Workbench and is
maintained as part of the forceworkbench project on Google Code. If you
simply want a working implementation of this client for users to load data into 
Salesforce, it is recommended to download Workbench. This client is designed
for developers who wish to integrate it into their PHP projects.
http://code.google.com/p/forceworkbench/

This client is NOT a supported product of or supported by salesforce.com, inc.
For support from the Open Source community, please visit the resources below:

* Main Project Site
  https://github.com/ryanbrainard/forceworkbench

* Feedback & Discussion 
  http://groups.google.com/group/forceworkbench

Copyright (c) 2013, salesforce.com, inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided
that the following conditions are met:

   Redistributions of source code must retain the above copyright notice, this list of conditions and the
   following disclaimer.

   Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
   the following disclaimer in the documentation and/or other materials provided with the distribution.

   Neither the name of salesforce.com, inc. nor the names of its contributors may be used to endorse or
   promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.