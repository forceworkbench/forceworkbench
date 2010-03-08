package forceworkbench.tests;

import org.apache.log4j.Logger;

public class DescribeTests extends WorkbenchSeleneseTestCase {
	
	private static Logger logger = Logger.getLogger(DescribeTests.class);
	
	public void setUp() throws Exception{
		super.setUp();
		loginWithConfig();
	}

	public void testDescribeAllObjects()throws Exception{
		selenium.open("describe.php");			
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		verifyFalse(selenium.isTextPresent("Object Description"));
		
		for(String type : selenium.getSelectOptions("default_object")){
			logger.info("Testing describe of: " + type);
			
			if(type.equalsIgnoreCase("")) continue;
			
			selenium.click("link=Describe");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			selenium.select("default_object", "label=" + type);
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			
			assertTrue(selenium.isTextPresent(type + " Object Description"));
			assertNoPhpErrors();
		}
	}
	
}
