package forceworkbench.tests;

import java.util.ArrayList;
import java.util.List;

public class Configuration {
	private String baseUrl;
	private String browser;
	
	private String username;
	private String password;
	private String instance;
	private String loginInstance;
	private String apiVersion;
	
	private List<String> ignoredInstances;
	
	protected String getBaseUrl() {
		return baseUrl;
	}
	protected void setBaseUrl(String baseUrl) {
		this.baseUrl = baseUrl;
	}
	protected String getBrowser() {
		return browser;
	}
	protected void setBrowser(String browser) {
		this.browser = browser;
	}
	protected String getUsername() {
		return username;
	}
	protected void setUsername(String username) {
		this.username = username;
	}
	protected String getPassword() {
		return password;
	}
	protected void setPassword(String password) {
		this.password = password;
	}
	protected String getInstance() {
		return instance;
	}
	protected void setInstance(String instance) {
		this.instance = instance;
	}
	protected String getApiVersion() {
		return apiVersion;
	}
	protected void setApiVersion(String apiVersion) {
		this.apiVersion = apiVersion;
	}
	protected String getLoginInstance() {
		return loginInstance;
	}
	protected void setLoginInstance(String loginInstance) {
		this.loginInstance = loginInstance;
	}
	protected List<String> getIgnoredInstances() {
		return ignoredInstances;
	}
	protected void setIgnoredInstances(List<String> ignoredInstances) {
		this.ignoredInstances = ignoredInstances;
	}
}
