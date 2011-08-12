package forceworkbench.tests;

import com.sun.source.tree.AssertTree;
import com.thoughtworks.selenium.Selenium;

public enum DmlAction {

    INSERT("create", true, false) {
        @Override
        public String getTestData(Selenium selenium) {
            return null;
        }
    },
    UPDATE("update", true, false),
    UPSERT("update", true, false), // todo: test both insert and update paths
    DELETE("delete", false, false),
    UNDELETE("undelete", false, true),
    PURGE("purge", false, true);

    private final String actionName;
    private final boolean requiresObject;
    private final boolean requiresSoftDeleted;

    private DmlAction(String actionName, boolean requiresObject, boolean requiresSoftDeleted) {
        this.actionName = actionName;
        this.requiresObject = requiresObject;
        this.requiresSoftDeleted = requiresSoftDeleted;
    }

    public String getPageUrl() {
        return name().toLowerCase() + ".php";
    }

    public String getSuccessfulVerb() {
        char firstChar = actionName.toLowerCase().charAt(0);
        firstChar -= 32; // a constant difference between the ASCII code of lower and upper case letters.
        String name = String.valueOf(firstChar) + actionName.substring(1);
        return name.endsWith("e") ? (name + "d") : (name + "ed");
    }

    public boolean requiresObject() {
        return requiresObject;
    }

    public String getTestData(Selenium selenium) {
        selenium.open("restExplorer.php?url=/services/data/v22.0/sobjects/Account&requestMethod=POST&requestBody={\"NAME\":\"SAMPLE ACCOUNT\"}");
        selenium.click("execBtn");
        selenium.waitForPageToLoad(WorkbenchSeleneseBaseTest.WAIT_TIMEOUT);
        final String id = selenium.getText("//ul[@id='responseList']/li[1]/strong");
        assert id != null;

        if (requiresSoftDeleted) {
            selenium.open("restExplorer.php?url=/services/data/v22.0/sobjects/Account/" + id + "&requestMethod=DELETE");
            selenium.click("execBtn");
            selenium.waitForPageToLoad(WorkbenchSeleneseBaseTest.WAIT_TIMEOUT);
            assert selenium.getText("codeViewPort").contains("HTTP/1.1 204 No Content");
        }

        return id;
    }
}
