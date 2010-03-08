package forceworkbench.tests;

import junit.framework.Test;
import junit.framework.TestSuite;

public class AllTests {

    public static Test suite() {            
        TestSuite suite = new TestSuite("Test for forceworkbench.tests");
                suite.addTestSuite(LoginTests.class);
                suite.addTestSuite(DescribeTests.class);
                suite.addTestSuite(QueryTests.class);
        return suite;
    }
    
}
