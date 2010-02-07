<?php
class QueryTest extends PHPUnit_Framework_TestCase {
	
	public $WORKBENCH_HOME = "/Users/ryan/Sites/workbench (trunk)/workbench";
    
    public function testQueryRequest() {	
    	require($this->WORKBENCH_HOME . '/soxl/QueryObjects.php');
    	  	
    	$source = array();
    	
    	$source['QB_object_sel'] = "Account";
    	$source['QB_field_sel'] = array("Id", "Name", "Phone");
		$source['QB_filter_field_sel'] = "BillingCity";
		$source['QB_oper_sel'] = "=";
		$source['QB_filter_txt'] = "New York";
		$source['QB_filter_field_sel2'] = "BillingState";
		$source['QB_oper_sel2'] = "!=";
		$source['QB_filter_txt2'] = "FL";
		$source['QB_nulls'] = "LAST";
		$source['QB_orderby_sort'] = "DESC";
		$source['QB_orderby_field'] = "Phone";
		$source['QB_limit_txt'] = 10;

		$source['soql_query'] = "SELECT Id FROM Account";
		$source['query_action'] = "queryAll";
		$source['export_action'] = "CSV";
		
	    	
    	$request = new QueryRequest($source);

    	$this->assertEquals($source['QB_object_sel'], $request->getObject());
    	$this->assertEquals($source['QB_field_sel'], $request->getFields());
    	
    	$this->assertEquals($source['QB_nulls'], $request->getOrderByNulls());
    	$this->assertEquals($source['QB_orderby_sort'], $request->getOrderByDir());
    	$this->assertEquals($source['QB_orderby_field'], $request->getOrderByField());
    	$this->assertEquals($source['QB_limit_txt'], $request->getLimit());
    	
    	$this->assertEquals($source['QB_filter_field_sel'], $request->getFilter(0)->getField());
    	$this->assertEquals($source['QB_oper_sel'], $request->getFilter(0)->getCompOper());
    	$this->assertEquals($source['QB_filter_txt'], $request->getFilter(0)->getValue());

    	$this->assertEquals($source['QB_filter_field_sel2'], $request->getFilter(1)->getField());
    	$this->assertEquals($source['QB_oper_sel2'], $request->getFilter(1)->getCompOper());
    	$this->assertEquals($source['QB_filter_txt2'], $request->getFilter(1)->getValue());

    	$this->assertEquals($source['soql_query'], $request->getSoqlQuery());
        $this->assertEquals($source['query_action'], $request->getQueryAction());
        $this->assertEquals($source['export_action'], $request->getExportTo());
    	    	
    }

}
?>