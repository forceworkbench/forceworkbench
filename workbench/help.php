<?php
//require_once ('session.php');
//require_once ('shared.php');
require_once ('header.php');
?>

<h1>Help</h1>


  <ul>
  <strong>
    <li><a href="#support">Support</a></li>
    <li><a href="#faq">FAQ</a></li>
    <li><a href="#installation">Installation</a></li>
    <li><a href="#versions">Version History</a></li>
    <li><a href="about.php">About</a></li>
  </strong>
  </ul>
  

  <h2><a name="Support"></a>Support</h2>
  Please note that the Workbench is NOT a product of or supported by salesforce.com, inc.


  For support from the Open Source community, please visit this project's SourceForge pages below:.

  <ul>
  	<li><a href='http://sourceforge.net/projects/forceworkbench/'>Project Main Page</a></li>
  	<li><a href='http://sourceforge.net/forum/?group_id=203311'>Forums</a></li>
  	<li><a href='http://sourceforge.net/tracker/?group_id=203311&atid=985083'>Feature Requests</a></li>
  	<li><a href='http://sourceforge.net/tracker/?group_id=203311&atid=985080'>Bugs</a></li>
  </ul>


  <h2>
  <a name="faq"></a>FAQ</h2>

 	<h3>What are the advantages of the Workbench compared to the Data Loader?</h3>
	  <ul>
	    <li>On-demand access to your organization's data anywhere you have an Internet connection. 
	    There is nothing for the end users to download and nothing to upgrade.</li>
	    <li>Clean, easy-to-use, user-centered interface that remembers your settings throughout your session.</li>
	    <li><a href="describe.php">Describe</a> function to access your organization's metadata.</li>
	    <li>Simplified results on one easy-to-read table displayed right in your browser.</li>
	    <li>Easier and faster SOQL query builder that dynamically updates itself.</li>
	    <li>The choice to view queries right in your browser or download them as a CSV file.</li>
	    <li><a href="export.php">Query All</a> function to query archived items as well as items that in your recycle bin.</li>
	    <li><a href="undelete.php">Undelete</a> function to restore items from your recycle bin by Id.</li>
	    <li><a href="purge.php">Purge</a> function to permanently delete items from your recycle bin by Id.</li>
	    <li><a href="#sso">Single sign on integration with your Salesforce organization</a> 
  </li>
	  </ul>
	  
	<h3>What is the Workbench <u>not</u> designed for?</h3>
	  <ul>
	    <li>Being web-based, the Workbench is subject to browser and connection timeouts. As such, is it not recommended to use the Workbench
	    for large data loads or exports. It is much better suited for quick, on-the-fly data management, which is something other
	    API integration tools lack</li>
	    <li>The Workbench cannot be run from the command line, used for automated processes, or support mapping files</li>
	  </ul>
	  
   <h3>Is the Workbench just an extension of the DataLoader?</h3>
	No, the Workbench was built completely from the ground-up using PHP and JavaScript, and binds to the Force.com Web Services API via the PHP Toolkit.
	It does not reference or build upon any of the Java code in the DataLoader, but was modeled after its concept.

  	<h3>How are the version numbers formatted?</h3>
	Because the version number is appended by the version number of the Force.com API WSDL file being used, the
	the version numbers tend to get rather long. The version numbers follow the format below:<p/>

	<em>Major.Minor.API</em>

	<h3>If this software is Open Source, where can I get the source code?</h3>
	The source code is available at the <a href="https://sourceforge.net/projects/forceworkbench/">Sourceforge project site</a> for download
	and distribution under the Open Source BSD License. Please see the <a href="about.php">
	About</a> page for details.
	
	<h3><a name="sso"></a>How I use the Workbench in a Web Tab or S-control in Salesforce for single sign on?</h3>
	Introduced in v.0.5.12, the Workbench has an exposed API to allow users to be automatically 
	logged in by providing their Server URL and Session Id in the URL arguments. This can 
	be to integrate the Workbench into a Web Tab or S-control directly in Salesforce for 
	single sign on into the Workbench. To integrate the Workbench into your org, follow the 
	instructions below:
	<ol>
		<li>Login to Salesforce</li>
		<li>Setup | Create | Tabs | Web Tabs | New</li>
		<li>Choose Tab Layout
			<ul>
				<li><em>Full page width</em> is recommended</li>
			</ul>
		</li>
		<li>Define Content and Display Properties
			<ul>
				<li>Tab Type: <em>URL</em></li>
				<li>Tab Label: <em>Workbench</em></li>
				<li>Tab Tab Style: Choose a style</li>
				<li>Content Frame Height (pixels): Choose the maximum amount available for your screen (you may have to edit this value to find the correct value for your screen)</li>
			</ul>
		</li>
		<li>Button or Link URL
			<ul>
				<li>Button or Link URL: <em><?php if($_SERVER[HTTPS]) print "https://"; else print "http://"; print $_SERVER[SERVER_NAME]; ?>/workbench/login.php?serverUrl={!API.Partner_Server_URL_120}&sid={!API.Session_ID}</em></li>
				<li>Encoding: <em>Unicode UTF-8</em></li>
			</ul>
		</li>
		<li>Save</li>	
	</ol> 
	
	<h3>What is the difference between Delete and Purge?</h3>
	Delete moves the records to your organization's recycle bin and can be undeleted if that object has the Undeletable attribute, 
	whereas Purge permentantly deletes items that are already in our organization's recycle bin. Note, some types of objects are
	immediately deleted from your organization when they are deleted. Be sure to check the if the record has the the Undeletable attribute
	before deleting records. This can be done using the Describe function and opening the Attributes folder.

<h2><a name="installation"></a>Installation</h2>
<p>
The Workbench is built on PHP and connects to the Force.com API using SOAP-based web services. As such, 
to install and run the Workbench, you must have a working web server, such as Apache HTTP Server, with 
support for PHP. Follow the instructions below to install Apache HTTP Server, PHP, and the Workbench:

<ol>
<li>
	Easier
		<ul>
			Install a LAMP/WAMP bundled installation of Apache HTTP Server, MySQL, and PHP. For a list of WAMPs, please see 
			the <a href="http://en.wikipedia.org/wiki/Comparison_of_WAMPs">Comparison of WAMPs</a>. The Workbench has been known
			to work with <a href="http://sourceforge.net/projects/webdeveloper/">Web Developer Server Suite</a>.
		</ul>
	Manual
		<ol type="a">
			<li>Install Apache HTTP Server from <a href="http://httpd.apache.org/">http://httpd.apache.org/</a></li>
			<li>Install PHP 5.x+ from <a href="http://www.php.net">http://www.php.net</a></li>
			<li>Ensure that PHP is registered with your Apache HTTP Server to handle .php files. The following lines
			should be in  <span class='mono'>&lt;your_apache_dir&gt;\conf\httpd.conf</span> file:<p/>	
				
<pre>
# Dynamic Shared Object (DSO) Support
LoadModule php5_module "&lt;your_apache_dir&gt;/php5apache2.dll"

# AddType
AddType application/x-httpd-php .php

# Anywhere
PHPIniDir "&lt;your_apache_dir&gt;/php5"
</pre>
				
			</li>
		</ol>
	<p/></li>
	
	<li>
	Ensure that PHP works on a basic level:
	<ol type="a">
		<li>Create a file called phpinfo.php in your web server's document root directory and paste the following into the file:<br/>
		<pre>&lt;?php phpinfo();&nbsp;?&gt;</pre></li>
		<li>Access the in your web browser by navigating to http://localhost/phpinfo.php. You should be presented with a page of PHP information about the current stage of PHP.
		If you did not, there is a problem with configuration of either your web server or PHP.</li>

	</ol>		
	<p/></li>
	
	<li>
	PHP 5 includes two .ini files to be used as templates for configuration. Rename php.ini-recommended to php.ini
	<p/></li>
	
	<li>
	Search for the string "extension_dir" in php.ini. Uncomment it and set it equal to "<your_php5_dir>/ext/". PHP requires an explicit path to find your extensions under Windows.
	<p/></li>
	
	<li>
	In order to use the HTTPS protocol and other features of Workbench, you need to edit some of the configurations in your php.ini file:
		<ol type="a">
			<li>Search for "extension=php_curl.dll". There should be a semi-colon in front of that line - remove it to enable the extension.</li>
			<li>Scroll down and find "extension=php_openssl.dll" and do the same.</li>
			<li>Now scroll down a bit further and find "extension=php_sockets.dll". Leave this line alone, but insert a new line below it and type "extension=php_soap.dll" on that line.</li>
			<li>Search for "magic_quotes_gpc" and ensure that it is "Off"</li>
			<li>Search for "file_uploads" and ensure that it is "On"</li>
		</ol>
	<p/></li>
		
	<li>
	Now you need to copy the SSL library files from the PHP installation directory to your Windows system directory. The two files are libeay32.dll and ssleay32.dll. They need to be copied into the system directory, usually c:\windows\system32 on an XP system. If you happen to have OpenSSL already installed on your computer you may find that these files are already installed. If they are, you should only replace them if the ones from the PHP directory are more recent. Change the extensions on the existing ones by adding '.bak' just to be safe.

    Note: these files must be readable by the Apache process, which may not run with the same permissions that you have when you copy the file into system32, please check that these are read and executable by world/all users 
	<p/></li>
	
	<li>
	You'll need to add the PHP installation directory to your system path. Right-click on My Computer on your desktop (or in the Start menu) and select 'Properties'. Click on the Advanced tab and then the 'Environment Variables' button. Scroll down in the System variables list until you find 'Path'. Select it and click the 'Edit' button. Click at the end of the string and make sure that the rest of the string is not highlighted. Type ";c:\php" at the end of the existing string and click OK until all of the windows are closed.
	<p/></li>
	
	<li>
	Restart your web server and re-load your phpinfo.php file to ensure it is still working.
	<p/></li>
	
	<li>
	Download and unzip the Workbench zip file into your web server's document root.
	<p/></li>
	
	<li>
	Point your web browser to https://localhost/workbench and you should be redirected to the Workbench
	login page, where you can login using your salesforce.com username and password.
	<p/></li>
	
	

</ol>

  	<h2><a name="versions"></a>Version History</h2>
   		<strong>0.0.8</strong> 2007/08/05
  		<ul>
  		   <li>Blank screen</li>
  		   <li>Stripped down Apex API 8.0 Partner WSDL-based PHP Toolkit</li>
  		</ul>


  		<strong>0.1.9</strong> 2007/08/23
  		<ul>
	  		<li>Completed development of all basic functions without JavaScript flourishes</li>
	  		<li>Upgraded WSDL to Apex API 9.0</li>
		</ul>


  		<strong>0.2.10</strong> 2007/09/04
  		<ul>
	  		<li>Partially abstracted PHP code and shared common functions for more efficient code re-use</li>
	  		<li>Added support for Query All method to query recycled and archived records</li>
	  		<li>Re-coded the Export functions to support queries of more than 2000 records by passing the Query Locater to a looping Query More calls to the Apex API</li>
	  		<li>Re-coded all basic put functions to support more than 200 records by looping multiple calls to the Apex API</li>
	  		<li>Added JavaScript functions for persistent login and SOQL builder</li>
	  		<li>Upgraded WSDL to Apex API 10.0</li>
		</ul>

		<strong>0.3.10</strong> 2007/09/12
  		<ul>
	  		<li>Changed branding from Apex DataLoader.PHP to Workbench</li>
	  		<li>Enhanced and streamlined the SOQL builder JavaScript function to automatically update the entire SOQL query when the user makes any changes to the criteria in the form.
	  		Also added onKeyUp event handling for realtime updating while the user types criteria in text fields.</li>
	  		<li>Added ORDER BY sorting to SOQL builder</li>
	  		<li>Added error handling if no object is selected when using SOQL builder</li>
	  		<li>Streamlined login and action-picking process with Jump To menu on login screen to avoid extra step with Select page</li>
	  	</ul>

	  	<strong>0.4.11</strong> 2007/11/18
  		<ul>
	  		<li>Upgraded WSDL to Force.com API Partner 11.0</li>
	  		<li>Added Purge functionality to remove items from the Recycle Bin by ID</li>
	  		<li>Expanded deleteUndelete function to allow for any simple API call that takes only IDs</li>
	  		<li>Updated Salesforce.com branding from Apex to Force.com</li>
	  	</ul>
	  	
	  	<strong>0.5.12</strong> 2008/02/25
  		<ul>
	  		<li>Upgraded WSDL to Force.com API Partner 12.0</li>
	  		<li>Upgraded base client to PHP Toolkit 11.1</li>
	  		<li>Added support for login with URL arguments for single sign on inside a Salesforce Web Tab. <a href="#sso">[Instructions]</a></li>
	  		<li>Simplified standard and advanced logins at code level</li>
	  		<li>Added support for endpoint changes when logging in with username and password under Advanced login option with auto-enabling fields</li>
	  		<li>Improved user interface on Export with more accessible and intuitive layout, auto-enabling field choices, and an additional filter selection</li>
	  		<li>Support for count() keyword in SOQL queries displaying results in browser</li>
	  		<li>Query result anchor jumping so the user does not have to scroll after running a query</li>
	  		<li>Enhanced Select page with auto-enabling field choices for more intuitive workflow</li>
	  		<li>Collapsible tree view for Describe function</li>
	  		<li>Increased maximum record size for Insert, Update, and Upsert to 2000 records, and Delete, Undelete, and Purge to 5000 records</li>
	  		<li>Added tooltip hovers to menubar</li>
	  		<li>Fixed minor bugs:
	  			<ul>
				    <li>Corrected PHP warnings for non-existent foreach() variables before field selections are made</li>
				    <li>Corrected wording of Purge info and error messages</li>
				    <li>Allowed API calls that do not depend on an object secion to not require selection of an object</li>
			  	</ul>
	  	</ul>
	  	
	  	<strong>0.6.12</strong> 2008/03/18
  		<ul>
	  		<li>Added an elapsed time clock to Query Results. Note, this is the time for the query() and queryMore() functions took to complete their requests with the Force.com API, not including the time it takes for PHP to process, transmit, and display the results to the end user.</li>
	  		<li>Added line numbers to the Query Results to match the Excel row numbers.</li>
	  	</ul>
	  	
		<strong>1.0.12</strong> 2008/03/22
  		<ul>
	  		<li>Updated documentation for general availability</li>
	  		<li>First public release on Sourceforge</li>
	  	</ul>
	  	
	  	<strong>1.1.12</strong> 2008/04/07
  		<ul>
	  		<li>Added auto-update reminder function if cURL is enabled</li>
	  		<li>Correced EMEA login URL Bug #1931515</li>
	  		<li>Correced Export Query Builder Bug #1930905 that did not properly unquote null values</li>
	  		<li>Started to move some constants to central shared.php for version control and auto-update reminders</li>
	  	</ul>



</div>

<?php
include_once ('footer.php');
?>


