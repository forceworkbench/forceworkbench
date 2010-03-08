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
	
	public void testSavedQuerues() throws Exception {
		selenium.open("query.php");
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		
		final String SOQL1 = "SELECT Id FROM Account LIMIT 1";
		final String SOQL1_NAME = "SOQL 1";
		
		final String SOQL2 = "SELECT Id FROM Contact LIMIT 1";
		final String SOQL2_NAME = "SOQL 2";
		
		final String SOQL3 = "SELECT Id FROM Opportunity LIMIT 1";
		
		selenium.type("soql_query_textarea", SOQL1);
		selenium.type("saveQr", SOQL1_NAME);
		selenium.click("doSaveQr");
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertEquals(SOQL1, selenium.getText("soql_query_textarea"));
		assertEquals(SOQL1_NAME, selenium.getValue("saveQr"));
		assertFalse(selenium.isTextPresent("Query Results"));
		assertNoPhpErrors();

		selenium.type("soql_query_textarea", SOQL2);
		selenium.type("saveQr", SOQL2_NAME);
		selenium.click("doSaveQr");
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertEquals(SOQL2, selenium.getText("soql_query_textarea"));
		assertEquals(SOQL2_NAME, selenium.getValue("saveQr"));
		assertFalse(selenium.isTextPresent("Query Results"));
		assertNoPhpErrors();
		
		selenium.select("getQr", SOQL1_NAME);
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertEquals(SOQL1, selenium.getText("soql_query_textarea"));
		assertEquals(SOQL1_NAME, selenium.getValue("saveQr"));
		assertTrue(selenium.isTextPresent("Query Results"));
		assertNoPhpErrors();
		
		selenium.type("soql_query_textarea", SOQL3);
		selenium.click("querySubmit");
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertEquals(SOQL1_NAME, selenium.getValue("saveQr"));
		assertTrue(selenium.isTextPresent("Query Results"));
		assertNoPhpErrors();
		
		selenium.select("QB_object_sel", "Case");
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		selenium.type("soql_query_textarea", SOQL3);
		selenium.click("querySubmit");
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertEquals("", selenium.getValue("saveQr"));
		assertTrue(selenium.isTextPresent("Query Results"));
		assertNoPhpErrors();
	}
	
	public void testSampleQueries() throws Exception {
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