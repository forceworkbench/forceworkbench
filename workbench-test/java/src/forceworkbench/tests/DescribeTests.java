package forceworkbench.tests;

public class DescribeTests extends WorkbenchSeleneseBaseTest {
		
	public void setUp() throws Exception{
		super.setUp();
		loginWithConfig();
	}

	public void testDescribeAllObjects()throws Exception{
		selenium.open("describe.php");			
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertTrue(selenium.isTextPresent("Choose an object to describe:"));
		assertFalse(selenium.isTextPresent("Expand All | Collapse All"));

		for(String type : selenium.getSelectOptions("default_object")){			
			if(type.equalsIgnoreCase("")) continue;
			
			selenium.select("default_object", "label=" + type);
			selenium.waitForPageToLoad(WAIT_TIMEOUT);

            assertTrue(selenium.isTextPresent("Choose an object to describe:"));
			assertTrue(selenium.isTextPresent("Expand All | Collapse All"));
			assertTrue(selenium.isTextPresent("Attributes"));
			assertTrue(selenium.isTextPresent("Fields ("));
			assertEquals(type, selenium.getSelectedValue("default_object"));
			assertNoPhpErrors();
		}
	}
	
}
