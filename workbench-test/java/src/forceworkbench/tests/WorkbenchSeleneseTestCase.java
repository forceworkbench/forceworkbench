package forceworkbench.tests;

import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Logger;

import com.thoughtworks.selenium.SeleneseTestCase;

public abstract class WorkbenchSeleneseTestCase extends SeleneseTestCase {

	private static Logger logger = Logger.getLogger(WorkbenchSeleneseTestCase.class);
	static {
		BasicConfigurator.configure();		
	}
	
	final String WAIT_TIMEOUT = "30000";
	Configuration config; 
	
	public void setUp() throws Exception {       
		config = new BaseWruConfiguration();
		config.setBaseUrl("http://localhost:8888/~ryan/workbench%20(trunk)/workbench/");
		config.setBrowser("*chrome");
		
		logger.info("Starting WorkbenchSeleneseTestCase");
		setUp(config.getBaseUrl(), config.getBrowser());
	}
	
	void loginWithConfig(){
		simpleLogin(config.getUsername(), config.getPassword(), config.getInstance(), config.getApiVersion());
	}
	
	void simpleLogin(String username, String password, String instance, String apiVersion){
		logger.info("Logging in as " + username + " on " + instance + " with API version " + apiVersion);
		selenium.open("login.php?&un=" + username + "&pw=" + password + "&inst=" + instance + "&api=" + apiVersion);
		verifyEquals("Workbench - Select", selenium.getTitle());			
		logger.info("Login successful");
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
    }
    
    void assertNoPhpErrors() {
    	final String html = selenium.getHtmlSource();
    	
    	assertFalse("Should not contain PHP notices:\n" + html, html.contains("<b>Notice</b>:"));
    	assertFalse("Should not contain PHP warnings:\n" + html, html.contains("<b>Warning</b>:"));
    	assertFalse("Should not contain PHP errors:\n" + html, html.contains("<b>Error</b>:"));
    	assertFalse("Should not contain PHP fatal errors:\n" + html, html.contains("Fatal")); //TODO: make more specific
	}

}
