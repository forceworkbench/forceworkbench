package forceworkbench.tests;

import java.util.ArrayList;
import java.util.List;
import org.apache.log4j.Logger;
import com.thoughtworks.selenium.SeleneseTestCase;

public class DescribeTests extends WorkbenchSeleneseTestCase {
	
	private static Logger logger = Logger.getLogger(DescribeTests.class);
	
	public void setUp() throws Exception{
		super.setUp();
		loginWithConfig();
	}

	public void testDescribeAllObjects()throws Exception{
		selenium.open("describe.php");			
		selenium.waitForPageToLoad("30000");
		verifyFalse(selenium.isTextPresent("Object Description"));
		
		for(String type : selenium.getSelectOptions("default_object")){
			logger.info("Testing describe of: " + type);
			if(!type.equalsIgnoreCase("")){
				selenium.click("link=Describe");
				selenium.waitForPageToLoad("30000");
				selenium.select("default_object", "label=" + type);
				selenium.click("action");
				selenium.waitForPageToLoad("30000");
				if(selenium.isTextPresent(type + " Object Description")){
					selenium.click("link=Expand All");
					selenium.click("link=Collapse All");
					selenium.click("link=Table View");
					//selenium.captureScreenshot("describe_test_success_" + type + ".png");
				} else {
					verifyTrue(false);
					selenium.captureScreenshot("describe_test_failure_" + type + ".png");
				}
			} else {
				continue;
			}
		}
	}
	
}
