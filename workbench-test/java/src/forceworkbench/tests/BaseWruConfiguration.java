package forceworkbench.tests;

public class BaseWruConfiguration extends Configuration {

	public BaseWruConfiguration() {
		setUsername("admin.test.rbrainard@salesforce.com");
		setPassword("123456");
		setApiVersion("18.0");
		setInstance("NA4");
		setLoginInstance("Production Login");
				
		setIgnoredInstances(new java.util.ArrayList<String>());
		getIgnoredInstances().add("NA8");
		getIgnoredInstances().add("Sandbox CS4");
		getIgnoredInstances().add("Sandbox CS6");
		getIgnoredInstances().add("Sandbox CS7");
		getIgnoredInstances().add("Sandbox CS8");
	}	
}