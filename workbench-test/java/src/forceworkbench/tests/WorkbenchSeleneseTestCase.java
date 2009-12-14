package forceworkbench.tests;

import org.apache.log4j.Logger;

import com.thoughtworks.selenium.SeleneseTestCase;

public abstract class WorkbenchSeleneseTestCase extends SeleneseTestCase {

	private static Logger logger = Logger.getLogger(WorkbenchSeleneseTestCase.class);
	
	static Configuration config; 

	public Configuration getConfig() {
		return config;
	}

	public void setConfig(Configuration config) {
		this.config = config;
	}
	
	public void setUp() throws Exception {
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
        selenium.waitForPageToLoad("30000");
        selenium.select("defaultApiVersion", "label=" + version);
        selenium.click("submitConfigSetter");
        selenium.waitForPageToLoad("30000");            
    }

}
