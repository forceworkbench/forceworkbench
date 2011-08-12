package forceworkbench.tests;

import com.thoughtworks.selenium.SeleneseTestCase;

public abstract class WorkbenchSeleneseBaseTest extends SeleneseTestCase {
	
	final static String WAIT_TIMEOUT = "30000";
	Configuration config; 
	
	public void setUp() throws Exception {       
		config = new BaseWruConfiguration();
		config.setBaseUrl(System.getProperty("baseUrl"));
		config.setBrowser(System.getProperty("browser"));
		
		setUp(config.getBaseUrl(), config.getBrowser());
	}
	
	void loginWithConfig(){
		simpleLogin(config.getUsername(), config.getPassword(), config.getInstance(), config.getApiVersion());
	}
	
	void simpleLogin(String username, String password, String instance, String apiVersion){
		selenium.open("login.php?&un=" + username + "&pw=" + password + "&inst=" + instance + "&api=" + apiVersion);
		assertTrue(selenium.getLocation().contains("select.php"));			
	}

    void setApiVersion(String version){
        selenium.open("settings.php");
        selenium.waitForPageToLoad(WAIT_TIMEOUT);
        selenium.select("defaultApiVersion", "label=" + version);
        selenium.click("submitConfigSetter");
        selenium.waitForPageToLoad(WAIT_TIMEOUT);            
    }
    
    @Override
    public void tearDown() throws Exception {
    	assertNoPhpErrors();
    	super.tearDown();
    	selenium.stop();
    }
    
    void assertNoPhpErrors() {
    	final String html = selenium.getHtmlSource();
    	
    	assertFalse("Should not contain PHP notices:\n" + html, html.contains("<b>Notice</b>:"));
    	assertFalse("Should not contain PHP warnings:\n" + html, html.contains("<b>Warning</b>:"));
    	assertFalse("Should not contain PHP errors:\n" + html, html.contains("<b>Error</b>:"));
    	assertFalse("Should not contain PHP fatal errors:\n" + html, html.contains("Fatal")); //TODO: make more specific
	}

}
