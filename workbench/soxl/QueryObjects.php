<?php

class QueryRequest {	
	//FIELDS
	private $exportTo 		= "screen";
	private $queryAction	= "query";
	
	private $object			= null;
	private $fields			= null;
	
	private $orderByField	= null;
	private $orderByDir 	= "ASC";
	private $orderByNulls	= "FIRST";
	
	private $limit			= null;
	
	private $filters		= array();
		
	private $soqlQuery		= null;
	
	//CONSTRUCTORS
	public function __construct($source){
		$this->object        = $source['QB_object_sel'];
		$this->fields 		 = $source['QB_field_sel'];
		$this->orderByField  = $source['QB_orderby_field'];
		$this->orderByDir	 = $source['QB_orderby_sort'];
		$this->orderByNulls  = $source['QB_nulls'];
		$this->limit		 = $source['QB_limit_txt'];
		$this->orderByField  = $source['QB_orderby_field'];
		
		$this->filters[0] = new QueryRequestFilter($source['QB_filter_field_sel'], $source['QB_oper_sel'], $source['QB_filter_txt']);
		$this->filters[1] = new QueryRequestFilter($source['QB_filter_field_sel2'], $source['QB_oper_sel2'], $source['QB_filter_txt2']);
		
		if(get_magic_quotes_gpc()){
			$this->soqlQuery = stripslashes($source['soql_query']);
		} else {
			$this->soqlQuery = $source['soql_query'];
		}

		$this->exportTo		 = $source['export_action'];
		$this->queryAction	 = $source['query_action'];
	}
	
	//GETTERS
	function getExportTo(){
		return $this->exportTo;
	}	
	
	function getQueryAction(){
		return $this->queryAction;
	}	
	
	function getObject(){
		return $this->object;
	}	
	
	function getFields(){
		return $this->fields;
	}
		
	function getOrderByField(){
		return $this->orderByField;
	}	
	
	function getOrderByDir(){
		return $this->orderByDir;
	}	

	function getOrderByNulls(){
		return $this->orderByNulls;
	}
	
	function getLimit(){
		return $this->limit;
	}	
	
	function getFilter($filterIndex){
		return $this->filters[$filterIndex];
	}

	function getSoqlQuery(){
		return $this->soqlQuery;
	}
}

class QueryRequestFilter {
	private $logicOper	= "AND";
	private $field 		= null;
	private $compOper	= "=";
	private $value		= null;
	
	public function __construct($field, $compOper, $value, $logicOper = "AND"){
		$this->field = $field;
		$this->compOper = $compOper;
		$this->value = $value;
	}

	function getField(){
		return $this->field;
	}	
	
	function getCompOper(){
		return $this->compOper;
	}	

	function getValue(){
		return $this->value;
	}
	
}

?> 