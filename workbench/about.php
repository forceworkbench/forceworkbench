<?php
require_once ('session.php');
require_once ('shared.php');
require_once ('header.php');
?>

<h1>About</h1>


<p>
The Workbench is a community-contributed, web-based application that gives administrators 
and developers on-demand access to useful tools to manage salesforce.com organizations. 
Starting with a combination of the features of the Apex Data Loader, Force.com Explorer, 
and System Log, the Workbench can insert, upsert, update, query, search, delete, undelete, 
and purge data as well as describe any object or execute Apex scripts directly in your web 
browser. These functions build on the strengths of these existing products to create an even 
more powerful and easier-to-use on-demand application. Not only can the Workbench be used as 
a standalone application in your browser, but it can also be integrated within Salesforce as 
a single-sign-on web tab for more convenient access.
</p>

<p>
<strong>
Workbench v<?php echo $GLOBALS['version']; ?><br/>
</strong>
Distributed under the Open Source BSD License.<br/>
Developed by Ryan Brainard<br/>

</p>

<p>
<img src='images/open_source_logo.png' width='119' height='96' alt='Open Source Logo' align='center' />&nbsp;&nbsp;
<img src='images/php-med-trans-light.gif' width='95' height='51' alt='PHP Logo' align='center' />
</p>

<p>
<strong>The Workbench is NOT a product of or supported by salesforce.com, inc.
For support from the Open Source community, please visit the recources below:</strong>
<ul>
  	<li><a href="http://wiki.apexdevnet.com/index.php/Workbench" target="_blank">Main Page</a></li>
	<li><a href="http://wiki.apexdevnet.com/index.php/Workbench#FAQ" target="_blank">FAQ</a></li>
	<li><a href="http://groups.google.com/group/forceworkbench" target="_blank">Feedback &amp; Discussion</a></li>
	<li><a href="http://code.google.com/p/forceworkbench/" target="_blank">Development</a></li>
	<li><a href="http://code.google.com/p/forceworkbench/issues/list" target="_blank">Report an Issue</a></li>
	<li><a href="http://code.google.com/p/forceworkbench/source/browse" target="_blank">Source Code</a></li>
	<li><a href="http://code.google.com/p/forceworkbench/downloads/list" target="_blank">Download</a></li>
</ul>
</p>

<strong>
<p>
THIS APPLICATION IS STILL IN ACTIVE DEVELOPMENT AND HAS NOT UNDERGONE COMPLETE QUALITY ASSURANCE TESTING.
DO NOT USE WITH PRODUCTION DATA.
THIS APPLICATION IS PROVIDED 'AS IS' AND THE USER ASSUMES ALL RISKS ASSOCIATED WITH ITS USE.

MAY CONTAIN PEANUTS, SOY, OR WHEAT PRODUCTS.
</p>
</strong>

<hr/>

<p>This application is based on the salesforce.com PHP Toolkit and calls against the
Force.com Web Services API, but is not itself a product of salesforce.com, inc. and not supported by
salesforce.com, inc or its contributors. Below is the copyright and license for the PHP Toolkit:</p>

<p>
  Copyright (c) 2008, salesforce.com, inc.<br/>
  All rights reserved.
</p>
<p>
  Redistribution and use in source and binary forms, with or without modification, are permitted provided
  that the following conditions are met:
<ul>
     <li>Redistributions of source code must retain the above copyright notice, this list of conditions and the
     following disclaimer.</li>

     <li>Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
     the following disclaimer in the documentation and/or other materials provided with the distribution.</li>

     <li>Neither the name of salesforce.com, inc. nor the names of its contributors may be used to endorse or
     promote products derived from this software without specific prior written permission.</li>
</ul>
</p>
<p>
  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
  WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
  PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
  TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
  HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
  POSSIBILITY OF SUCH DAMAGE.
</p>

<hr/>
<p>
Collapsible tree on Describe page is built on
<a href="http://www.dynamicdrive.com/dynamicindex1/navigate1.htm">Simple Tree Menu</a>
framework from <a href="http://www.dynamicdrive.com">Dynamic Drive DHTML code library</a>
</p>


<hr/>
<p>
Code for menu bar tool tips is built on
<a href="http://www.walterzorn.com/tooltip/tooltip_e.htm">JavaScript, DHTML Tooltips </a>
framework from <a href="http://www.walterzorn.com/">Walter Zorn</a>
</p>
</div>

<?php
include_once ('footer.php');
?>
