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
		selenium.waitForPageToLoad("30000");
		verifyEquals("Workbench - Select", selenium.getTitle());	
	}

	public void testAutoLoginUnPwStartUrl() throws Exception{
		setApiVersion(config.getApiVersion());
		selenium.open("login.php?&un=" + config.getUsername() + "&pw=" + config.getPassword() + "&startUrl=query.php");
		selenium.waitForPageToLoad("30000");
		verifyEquals("Workbench - Query", selenium.getTitle());	
	}

	public void testAutoLoginApiVersions() throws Exception {		
		selenium.open("login.php");
		selenium.click("login_become_adv");
		for(String ep : selenium.getSelectOptions("endp")){
			logger.info("Starting to Test API Version: " + ep);

			if(Double.valueOf(ep) <= Double.valueOf(config.getApiVersion())){
				selenium.open("login.php?&un=" + config.getUsername()  + "&pw=" + config.getPassword()  + "&inst=" + config.getInstance() + "&api=" + ep);
				selenium.waitForPageToLoad("30000");
				verifyEquals("Workbench - Select", selenium.getTitle());
			} else {
				logger.info("API Version too high. Skipping: " + ep);
				continue;
			}
		}		
	}
	
	public void testStdLoginJumpTo() throws Exception {		
		selenium.open("login.php");
		for(String action : selenium.getSelectOptions("actionJumpStd")){
			selenium.open("login.php");
			verifyEquals("Workbench - Login", selenium.getTitle());
			verifyTrue(selenium.isTextPresent("Username:"));
			verifyTrue(selenium.isTextPresent("Password:"));
			verifyFalse(selenium.isTextPresent("Session ID:"));
			
			selenium.type("username", config.getUsername());
			selenium.type("password", config.getPassword());
			selenium.select("actionJumpStd", "label=" + action);
			selenium.click("loginClick");
			selenium.waitForPageToLoad("30000");
			if(action.equals("")) action = "Select";
			verifyEquals("Workbench - " + action, selenium.getTitle());			
		}		
	}

	
	public void testAdvLoginJumpTo() throws Exception {		
		selenium.open("login.php");
		selenium.click("login_become_adv");
		for(String action : selenium.getSelectOptions("actionJumpAdv")){
			selenium.open("login.php");
			verifyEquals("Workbench - Login", selenium.getTitle());			
			selenium.click("login_become_adv");
			verifyTrue(selenium.isTextPresent("Username:"));
			verifyTrue(selenium.isTextPresent("Password:"));
			verifyTrue(selenium.isTextPresent("Session ID:"));
			
			selenium.type("usernameAdv", config.getUsername());
			selenium.type("passwordAdv", config.getPassword());
			selenium.select("actionJumpAdv", "label=" + action);
			selenium.click("loginClick");
			selenium.waitForPageToLoad("30000");
			if(action.equals("")) action = "Select";
			verifyEquals("Workbench - " + action, selenium.getTitle());			
		}		
	}

	
	public void testAdvLoginApiVersions() throws Exception {		
		selenium.open("login.php");
		selenium.click("login_become_adv");
		for(String ep : selenium.getSelectOptions("endp")){
			logger.info("Starting to Test API Version: " + ep);

			if(Double.valueOf(ep) <= Double.valueOf(config.getApiVersion())){
				selenium.open("login.php");
				verifyEquals("Workbench - Login", selenium.getTitle());			
				selenium.click("login_become_adv");
				
				selenium.select("endp", "label=" + ep);
				verifyEquals("https://www.salesforce.com/services/Soap/u/" + ep, selenium.getValue("serverUrl"));
				selenium.type("usernameAdv", config.getUsername());
				selenium.type("passwordAdv", config.getPassword());
				selenium.click("loginClick");
				selenium.waitForPageToLoad("30000");
				verifyEquals("Workbench - Select", selenium.getTitle());
			} else {
				logger.info("API Version too high. Skipping: " + ep);
				continue;
			}
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
			verifyEquals("Workbench - Login", selenium.getTitle());			
			selenium.click("login_become_adv");
			
			selenium.select("inst", "label=" + inst);
			
			selenium.type("usernameAdv", config.getUsername());
			selenium.type("passwordAdv", config.getPassword());
			selenium.click("loginClick");
			selenium.waitForPageToLoad("30000");
			
			if(inst.contains(config.getInstance()) || inst.contains(config.getLoginInstance())){
				verifyEquals("Workbench - Select", selenium.getTitle());
			} else {
				verifyTrue(selenium.isTextPresent("INVALID_LOGIN"));
			}
			
		}		
	}	
	
}
