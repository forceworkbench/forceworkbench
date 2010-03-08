package forceworkbench.tests;

import org.apache.log4j.Logger;

public class LoginTests extends WorkbenchSeleneseTestCase {
	
	private static Logger logger = Logger.getLogger(LoginTests.class);
	
	public void setUp() throws Exception{
		super.setUp();
	}

	public void testAutoLoginUnPw() throws Exception{
		setApiVersion(config.getApiVersion());
		selenium.open("login.php?&un=" + config.getUsername() + "&pw=" + config.getPassword());
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertEquals("Workbench - Select", selenium.getTitle());
	}

	public void testAutoLoginUnPwStartUrl() throws Exception{
		setApiVersion(config.getApiVersion());
		selenium.open("login.php?&un=" + config.getUsername() + "&pw=" + config.getPassword() + "&startUrl=query.php");
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertEquals("Workbench - Query", selenium.getTitle());	
	}

	public void testAutoLoginApiVersions() throws Exception {		
		selenium.open("login.php");
		selenium.click("login_become_adv");
		for(String ep : selenium.getSelectOptions("endp")){
			logger.info("Starting to Test API Version: " + ep);

			if(Double.valueOf(ep) > Double.valueOf(config.getApiVersion())){
				logger.info("API Version too high. Skipping: " + ep);
				continue;
			}
			
			selenium.open("login.php?&un=" + config.getUsername()  + "&pw=" + config.getPassword()  + "&inst=" + config.getInstance() + "&api=" + ep);
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			assertEquals("Workbench - Select", selenium.getTitle());
			assertNoPhpErrors();
		}		
	}
	
	public void testStdLoginJumpTo() throws Exception {		
		selenium.open("login.php");
		for(String action : selenium.getSelectOptions("actionJumpStd")){
			selenium.open("login.php");
			assertEquals("Workbench - Login", selenium.getTitle());
			assertTrue(selenium.isTextPresent("Username:"));
			assertTrue(selenium.isTextPresent("Password:"));
			assertFalse(selenium.isTextPresent("Session ID:"));
			assertNoPhpErrors();
			
			selenium.type("username", config.getUsername());
			selenium.type("password", config.getPassword());
			selenium.select("actionJumpStd", "label=" + action);
			selenium.click("loginClick");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			if(action.equals("")) action = "Select";
			assertEquals("Workbench - " + action, selenium.getTitle());
			assertNoPhpErrors();
		}		
	}

	
	public void testAdvLoginJumpTo() throws Exception {		
		selenium.open("login.php");
		selenium.click("login_become_adv");
		for(String action : selenium.getSelectOptions("actionJumpAdv")){
			selenium.open("login.php");
			assertEquals("Workbench - Login", selenium.getTitle());			
			selenium.click("login_become_adv");
			assertTrue(selenium.isTextPresent("Username:"));
			assertTrue(selenium.isTextPresent("Password:"));
			assertTrue(selenium.isTextPresent("Session ID:"));
			assertNoPhpErrors();
			
			selenium.type("usernameAdv", config.getUsername());
			selenium.type("passwordAdv", config.getPassword());
			selenium.select("actionJumpAdv", "label=" + action);
			selenium.click("loginClick");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			if(action.equals("")) action = "Select";
			assertEquals("Workbench - " + action, selenium.getTitle());		
			assertNoPhpErrors();
		}		
	}

	
	public void testAdvLoginApiVersions() throws Exception {		
		selenium.open("login.php");
		selenium.click("login_become_adv");
		for(String ep : selenium.getSelectOptions("endp")){
			logger.info("Starting to Test API Version: " + ep);

			if(Double.valueOf(ep) > Double.valueOf(config.getApiVersion())) {
				logger.info("API Version too high. Skipping: " + ep);
				continue;
			}
				
			selenium.open("login.php");
			assertEquals("Workbench - Login", selenium.getTitle());			
			selenium.click("login_become_adv");
			assertNoPhpErrors();
			
			selenium.select("endp", "label=" + ep);
			assertEquals("https://login.salesforce.com/services/Soap/u/" + ep, selenium.getValue("serverUrl"));
			selenium.type("usernameAdv", config.getUsername());
			selenium.type("passwordAdv", config.getPassword());
			selenium.click("loginClick");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			assertEquals("Workbench - Select", selenium.getTitle());
			assertNoPhpErrors();
		}		
	}


	public void testAdvLoginInstances() throws Exception {		
		selenium.open("login.php");
		selenium.click("login_become_adv");
		for(String inst : selenium.getSelectOptions("inst")){
			logger.info("Starting to Test Instance: " + inst);
			
			//skip future instances
			if(config.getIgnoredInstances().contains(inst)){
				logger.info("Skipping: " + inst);
				continue;
			}
			
			selenium.open("login.php");
			assertEquals("Workbench - Login", selenium.getTitle());			
			selenium.click("login_become_adv");
			
			selenium.select("inst", "label=" + inst);
			
			selenium.type("usernameAdv", config.getUsername());
			selenium.type("passwordAdv", config.getPassword());
			selenium.click("loginClick");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			
			if(inst.contains(config.getInstance()) || inst.contains(config.getLoginInstance())){
				assertEquals("Workbench - Select", selenium.getTitle());
			} else {
				assertTrue(selenium.isTextPresent("INVALID_LOGIN"));
			}
			
			assertNoPhpErrors();
		}		
	}	
	
}
