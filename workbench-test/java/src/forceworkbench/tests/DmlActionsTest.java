package forceworkbench.tests;

import junit.framework.Test;
import junit.framework.TestSuite;

import java.util.HashMap;
import java.util.Map;

public class DmlActionsTest extends WorkbenchSeleneseBaseTest {

    static final String ACCT_ID_PATTERN = "001\\w{15}";

    public static Test suite() {
        final TestSuite suite = new TestSuite();
        for (DmlAction dmlAction : DmlAction.values()) {
            suite.addTest(new DmlActionsTest(dmlAction));
        }
        return suite;
    }

    private final DmlAction dmlAction;

    public DmlActionsTest(DmlAction dmlAction) {
        this.dmlAction = dmlAction;
    }

    @Override
    public void setUp() throws Exception {
        super.setUp();
        loginWithConfig();
    }

    @Override
    public String getName() {
        return "test" + dmlAction;
    }

    @Override
    protected void runTest() throws Throwable {
        testSingleRow();
    }

    public void testSingleRow() throws Exception {
        final String testDataId = dmlAction.getTestData(selenium);

        selenium.open(dmlAction.getPageUrl());
        assertEquals(config.getBaseUrl() + dmlAction.getPageUrl(), selenium.getLocation());

        if (dmlAction.requiresObject()) selenium.select("default_object", "Account");
        selenium.click("sourceType_singleRecord");
        if (testDataId != null) selenium.type("id", testDataId);
        selenium.click("action");

        selenium.waitForPageToLoad(WAIT_TIMEOUT);
        if (dmlAction.requiresObject()) selenium.type("Name", "This is a Selenium Test Account");
        selenium.click("action");

        selenium.waitForPageToLoad(WAIT_TIMEOUT);
        assertTrue(selenium.isTextPresent("There was 1 success and 0 errors"));
        assertTrue(selenium.isElementPresent("//input[@value='Download Full Results']"));

        final Map<Integer, String> expectedRows = new HashMap<Integer, String>(4) {{
            put(1, "1");
            put(2, ACCT_ID_PATTERN);
            put(3, "Success");
            put(4, dmlAction.getSuccessfulVerb());
        }};

        for (Map.Entry<Integer, String> row : expectedRows.entrySet()) {
            final String actualText = selenium.getText("//table[@class='dataTable']//tr[2]/td[" + row.getKey() + "]");
            assertTrue(row.getValue() + " should match " + actualText, actualText.matches(row.getValue()));
        }
    }
}
