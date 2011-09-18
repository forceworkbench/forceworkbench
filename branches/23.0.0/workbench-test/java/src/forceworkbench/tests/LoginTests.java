package forceworkbench.tests;



public class LoginTests extends WorkbenchSeleneseBaseTest {
	
	public void setUp() throws Exception{
		super.setUp();
	}

	public void testAutoLoginUnPw() throws Exception{
		setApiVersion(config.getApiVersion());
		selenium.open("login.php?&un=" + config.getUsername() + "&pw=" + config.getPassword());
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertTrue(selenium.getLocation().contains("select.php"));
	}

	public void testAutoLoginUnPwStartUrl() throws Exception{
		setApiVersion(config.getApiVersion());
		selenium.open("login.php?&un=" + config.getUsername() + "&pw=" + config.getPassword() + "&startUrl=query.php");
		selenium.waitForPageToLoad(WAIT_TIMEOUT);
		assertEquals("Workbench: SOQL Query", selenium.getTitle());
	}

	public void testAutoLoginApiVersions() throws Exception {
		selenium.open("login.php");
		selenium.click("loginType_adv");
		for(String ep : selenium.getSelectOptions("api")){
			if(Double.valueOf(ep) > Double.valueOf(config.getApiVersion())){
				continue;
			}

			selenium.open("login.php?&un=" + config.getUsername()  + "&pw=" + config.getPassword()  + "&inst=" + config.getInstance() + "&api=" + ep);
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			assertTrue(selenium.getLocation().contains("select.php"));
			assertNoPhpErrors();
            break;
		}
	}

	public void testStdLoginJumpTo() throws Exception {
		selenium.open("login.php");
        selenium.open("login.php");
        assertEquals("Workbench", selenium.getTitle());
        assertTrue(selenium.isTextPresent("Username:"));
        assertTrue(selenium.isTextPresent("Password:"));
        assertFalse(selenium.isVisible("sid"));
        assertNoPhpErrors();

        selenium.type("un", config.getUsername());
        selenium.type("pw", config.getPassword());
        selenium.click("loginBtn");
        selenium.waitForPageToLoad(WAIT_TIMEOUT);
        assertEquals("Workbench", selenium.getTitle());
        assertTrue(selenium.getLocation().contains("select.php"));
        assertNoPhpErrors();
	}


	public void testAdvLoginJumpTo() throws Exception {
		selenium.open("login.php");
		selenium.click("loginType_adv");
        selenium.open("login.php");
        assertEquals("Workbench", selenium.getTitle());
        selenium.click("loginType_adv");
        assertTrue(selenium.isTextPresent("Username:"));
        assertTrue(selenium.isTextPresent("Password:"));
        assertTrue(selenium.isVisible("sid"));
        assertNoPhpErrors();

        selenium.type("un", config.getUsername());
        selenium.type("pw", config.getPassword());
        selenium.click("loginBtn");
        selenium.waitForPageToLoad(WAIT_TIMEOUT);
        assertEquals("Workbench", selenium.getTitle());
        assertTrue(selenium.getLocation().contains("select.php"));
        assertNoPhpErrors();
	}


	public void testAdvLoginApiVersions() throws Exception {
		selenium.open("login.php");
		selenium.click("loginType_adv");
		for(String api : selenium.getSelectOptions("api")){

			if(Double.valueOf(api) > Double.valueOf(config.getApiVersion())) {
				continue;
			}

			selenium.open("login.php");
			assertEquals("Workbench", selenium.getTitle());
			selenium.click("loginType_adv");
			assertNoPhpErrors();

			selenium.select("api", "label=" + api);
			assertEquals("https://login.salesforce.com/services/Soap/u/" + api, selenium.getValue("serverUrl"));
			selenium.type("un", config.getUsername());
			selenium.type("pw", config.getPassword());
			selenium.click("loginBtn");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);
			assertTrue(selenium.getLocation().contains("select.php"));
			assertNoPhpErrors();
            break;
		}
	}


	public void testAdvLoginInstances() throws Exception {
		selenium.open("login.php");
		selenium.click("loginType_adv");
		for (String inst : selenium.getSelectOptions("inst")){

			//skip future instances
			if(config.getIgnoredInstances().contains(inst)){
				continue;
			}

			selenium.open("login.php");
			assertEquals("Title should be Workbench", "Workbench", selenium.getTitle());
			selenium.click("loginType_adv");

			selenium.select("inst", "label=" + inst);

			selenium.type("un", config.getUsername());
			selenium.type("pw", config.getPassword());
			selenium.click("loginBtn");
			selenium.waitForPageToLoad(WAIT_TIMEOUT);

            final String location = selenium.getLocation();
			if(inst.contains(config.getInstance()) || inst.contains(config.getLoginInstance())){
				assertTrue("Should be on Select page for " + inst + ". Location was " + location,
				           location.contains("select.php"));
			} else {
				assertTrue("Should have been an INVALID_LOGIN for " + inst + ". Location was " + location,
				           selenium.isTextPresent("INVALID_LOGIN"));
			}

			assertNoPhpErrors();
            break;
		}		
	}	
	
}
