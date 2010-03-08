package forceworkbench.tests;

import java.util.ArrayList;
import java.util.List;

import org.apache.log4j.Logger;

public class QueryTests extends WorkbenchSeleneseTestCase {
	
	private static Logger logger = Logger.getLogger(QueryTests.class);

	public void setUp() throws Exception{
		super.setUp();
		loginWithConfig();
	}
	
	public void testSampleQueries() throws Exception{
		logger.info("Starting testSampleQueries()");
		
		final List<String> SAMPLE_QUERIES = new ArrayList<String>();
			//simple
		    SAMPLE_QUERIES.add("SELECT Id FROM Contact");
			SAMPLE_QUERIES.add("SELECT Id, Name FROM Contact");
			SAMPLE_QUERIES.add("SELECT AccountId FROM Contact");
			
			//child-parent relationships
			SAMPLE_QUERIES.add("SELECT Account.Id FROM Contact");
			SAMPLE_QUERIES.add("SELECT Account.Name FROM Contact");
			SAMPLE_QUERIES.add("SELECT Account.Owner.Name FROM Contact");
			
			//parent-child relationships
			SAMPLE_QUERIES.add("SELECT (SELECT Subject FROM Cases) FROM Contact");
			SAMPLE_QUERIES.add("SELECT (SELECT Subject FROM Cases) FROM Account");
			SAMPLE_QUERIES.add("SELECT (SELECT Subject FROM Cases),(SELECT Name FROM Contacts) FROM Account");
			SAMPLE_QUERIES.add("SELECT (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account");
			
			//joins
			SAMPLE_QUERIES.add("SELECT (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account WHERE Id IN (SELECT AccountId FROM Contact) AND Id IN (SELECT AccountId FROM Case)");
			SAMPLE_QUERIES.add("SELECT Id, (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account WHERE Id IN (SELECT AccountId FROM Contact) AND Id IN (SELECT AccountId FROM Case)");
			SAMPLE_QUERIES.add("SELECT Name, (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account WHERE Id IN (SELECT AccountId FROM Contact) AND Id IN (SELECT AccountId FROM Case)");
			SAMPLE_QUERIES.add("SELECT Id, Name, (SELECT Name FROM Contacts), (SELECT Subject FROM Cases) FROM Account WHERE Id IN (SELECT AccountId FROM Contact) AND Id IN (SELECT AccountId FROM Case)");
			SAMPLE_QUERIES.add("SELECT Id FROM Contact WHERE AccountId IN (SELECT AccountId FROM Opportunity)");
			
			//aggregates
			SAMPLE_QUERIES.add("SELECT OwnerId FROM Account GROUP BY OwnerId");
			SAMPLE_QUERIES.add("SELECT OwnerId, count(id) FROM Account GROUP BY OwnerId");
			SAMPLE_QUERIES.add("SELECT OwnerId, count(id), max(AnnualRevenue) FROM Account GROUP BY OwnerId");
			SAMPLE_QUERIES.add("SELECT Owner.Name FROM Account GROUP BY Owner.Name");
			SAMPLE_QUERIES.add("SELECT OwnerId FROM Account GROUP BY OwnerId HAVING count(id) > 1");
	
		for(String query : SAMPLE_QUERIES){
			logger.info("Testing SOQL: " + query);
			
			selenium.open("query.php");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			selenium.type("soql_query_textarea", query);
			selenium.click("querySubmit");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			
			assertTrue(selenium.isTextPresent("Query Results"));
			assertNoPhpErrors();
		}
	}

}
