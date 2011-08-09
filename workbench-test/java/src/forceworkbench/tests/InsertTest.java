package forceworkbench.tests;

import java.text.MessageFormat;
import java.util.HashMap;
import java.util.Map;

public class InsertTest extends WorkbenchSeleneseBaseTest {

    public void setUp() throws Exception {
        super.setUp();
        loginWithConfig();
    }

    public void testSingleRow() throws Exception {
        selenium.open("insert.php");
        selenium.select("default_object", "Account");
        selenium.click("sourceType_singleRecord");
        selenium.click("action");

        selenium.waitForPageToLoad("10000");
        selenium.type("Name", "This is a Selenium Test Account");
        selenium.click("action");

        selenium.waitForPageToLoad("10000");
        assertTrue(selenium.isTextPresent("There was 1 success and 0 errors"));
        assertTrue(selenium.isElementPresent("//input[@value='Download Full Results']"));

        final Map<Integer, String> expectedRows = new HashMap<Integer, String>(4) {{
            put(1, "1");
            put(2, "001\\w{15}");
            put(3, "Success");
            put(4, "Created");
        }};

        for (Map.Entry<Integer, String> row : expectedRows.entrySet()) {
            final String actualText = "//table[@class='dataTable']//tr[2]/td[" + row.getKey() + "]";
            assertTrue(row.getValue() + " should match " + actualText, selenium.getText(actualText).matches(row.getValue()));
        }
    }

}
