package forceworkbench.tests;



public class DescribeTests extends WorkbenchSeleneseBaseTest {
		
	public void setUp() throws Exception{
		super.setUp();
		loginWithConfig();
	}

	public void testDescribeAllObjects()throws Exception{
		selenium.open("describe.php");			
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		verifyFalse(selenium.isTextPresent("Object Description"));
		
		for(String type : selenium.getSelectOptions("default_object")){			
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
