package forceworkbench.tests;

import java.util.ArrayList;
import java.util.List;

import org.apache.log4j.Logger;

import com.thoughtworks.selenium.SeleneseTestCase;

public class QueryTests extends WorkbenchSeleneseTestCase {
	
	private static Logger logger = Logger.getLogger(QueryTests.class);

	public void setUp() throws Exception{
		super.setUp();
		loginWithConfig();
	}
	
	public void testSampleQueries() throws Exception{
		logger.info("Starting testSampleQueries()");
		
		final List<String> SAMPLE_QUERIES = new ArrayList<String>();
			SAMPLE_QUERIES.add("SELECT Id FROM Contact");
			SAMPLE_QUERIES.add("SELECT Id, Name FROM Contact");
			SAMPLE_QUERIES.add("SELECT AccountId FROM Contact");
			SAMPLE_QUERIES.add("SELECT Account.Id FROM Contact");
			SAMPLE_QUERIES.add("SELECT Account.Name FROM Contact");
			SAMPLE_QUERIES.add("SELECT Account.Owner.Name FROM Contact");
			SAMPLE_QUERIES.add("SELECT (SELECT Subject FROM Cases) FROM Contact");
			SAMPLE_QUERIES.add("SELECT (SELECT Subject FROM Cases) FROM Account");
			SAMPLE_QUERIES.add("SELECT (SELECT Subject FROM Cases),(SELECT Name FROM Contacts) FROM Account");
			SAMPLE_QUERIES.add("SELECT (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account");
			SAMPLE_QUERIES.add("SELECT (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account WHERE Id IN (SELECT AccountId FROM Contact) AND Id IN (SELECT AccountId FROM Case)");
			SAMPLE_QUERIES.add("SELECT Id, (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account WHERE Id IN (SELECT AccountId FROM Contact) AND Id IN (SELECT AccountId FROM Case)");
			SAMPLE_QUERIES.add("SELECT Name, (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account WHERE Id IN (SELECT AccountId FROM Contact) AND Id IN (SELECT AccountId FROM Case)");
			SAMPLE_QUERIES.add("SELECT Id, Name, (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account WHERE Id IN (SELECT AccountId FROM Contact) AND Id IN (SELECT AccountId FROM Case)");
	
		for(String query : SAMPLE_QUERIES){
			logger.info("Testing SOQL: " + query);
			
			selenium.open("query.php");
			selenium.waitForPageToLoad("30000");
			selenium.type("soql_query_textarea", query);
			selenium.click("querySubmit");
			selenium.waitForPageToLoad("30000");
			
			verifyTrue(selenium.isTextPresent("Query Results"));
		}
	}
	
}
