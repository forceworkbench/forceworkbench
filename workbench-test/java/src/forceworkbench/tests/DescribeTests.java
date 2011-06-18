package forceworkbench.tests;

public class DescribeTests extends WorkbenchSeleneseBaseTest {
		
	public void setUp() throws Exception{
		super.setUp();
		loginWithConfig();
	}

	public void testDescribeAllObjects()throws Exception{
		selenium.open("describe.php");			
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertTrue("Instructions should appear before selecting a type to describe", selenium.isTextPresent("Choose an object to describe:"));
		assertFalse("Tree controls should appear before selecting a type to describe", selenium.isTextPresent("Expand All | Collapse All"));

		for(String type : selenium.getSelectOptions("default_object")){			
			if(type.equalsIgnoreCase("")) continue;
			
			selenium.select("default_object", "label=" + type);
			selenium.waitForPageToLoad(WAIT_TIMEOUT);

            assertTrue("Instructions should appear for [" + type + "]", selenium.isTextPresent("Choose an object to describe:"));
			assertTrue("Tree controls should appear for [" + type + "]", selenium.isTextPresent("Expand All | Collapse All"));
			assertTrue("Attributes should appear for [" + type + "]", selenium.isTextPresent("Attributes"));
			assertTrue("Fields should appear for [" + type + "]", selenium.isTextPresent("Fields ("));
			assertEquals("Default object should be set to [" + type + "]", type, selenium.getSelectedValue("default_object"));
			assertNoPhpErrors();
		}
	}
	
}
