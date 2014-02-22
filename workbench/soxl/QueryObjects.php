<?php
class QueryRequest {
    //FIELDS
    private $name         = null;

    private $exportTo     = "screen";
    private $queryAction  = "Query";

    private $object       = null;
    private $fields       = null;

    private $orderByField = null;
    private $orderByDir   = "ASC";
    private $orderByNulls = "FIRST";

    private $limit        = null;

    private $matrixRows   = null;
    private $matrixCols   = null;

    private $filters      = array();
    private $numFilters   = null;

    private $soqlQuery    = null;


    //CONSTRUCTORS
    public function __construct($source) {
        if(isset($source['QB_object_sel']))    $this->object       = $source['QB_object_sel'];
        if(isset($source['QB_field_sel']))     $this->fields       = $source['QB_field_sel'];
        if(isset($source['QB_orderby_field'])) $this->orderByField = $source['QB_orderby_field'];
        if(isset($source['QB_orderby_sort']))  $this->orderByDir   = $source['QB_orderby_sort'];
        if(isset($source['QB_nulls']))         $this->orderByNulls = $source['QB_nulls'];
        if(isset($source['QB_limit_txt']))     $this->limit        = $source['QB_limit_txt'];
        if(isset($source['QB_orderby_field'])) $this->orderByField = $source['QB_orderby_field'];
        if(isset($source['matrix_rows']))      $this->matrixRows   = $source['matrix_rows'];
        if(isset($source['matrix_cols']))      $this->matrixCols   = $source['matrix_cols'];
        if(isset($source['numFilters']))       $this->numFilters   = $source['numFilters'];

        for ($f = 0; $f < $this->numFilters; $f++) {
            if (isset($source["QB_filter_field_$f"]) && isset($source["QB_filter_compOper_$f"]) && isset($source["QB_filter_value_$f"])) {
                $this->filters[$f] = new QueryRequestFilter($source["QB_filter_field_$f"], $source["QB_filter_compOper_$f"], $source["QB_filter_value_$f"]);
            } else {
                $this->filters[$f] = new QueryRequestFilter(null, null, null);
            }
        }

        if (isset($source['soql_query'])) {
            if (get_magic_quotes_gpc()) {
                $this->soqlQuery = stripslashes($source['soql_query']);
            } else {
                $this->soqlQuery = $source['soql_query'];
            }
        }

        if(isset($source['export_action'])) $this->exportTo    = $source['export_action'];
        if(isset($source['query_action']))  $this->queryAction = $source['query_action'];
    }

    public function toJson() {
        $o = array();
        $o['QB_object_sel']    = $this->object       ;
        $o['QB_field_sel']     = $this->fields       ;
        $o['QB_orderby_field'] = $this->orderByField ;
        $o['QB_orderby_sort']  = $this->orderByDir   ;
        $o['QB_nulls']         = $this->orderByNulls ;
        $o['QB_limit_txt']     = $this->limit        ;
        $o['QB_orderby_field'] = $this->orderByField ;
        $o['matrix_rows']      = $this->matrixRows   ;
        $o['matrix_cols']      = $this->matrixCols   ;
        $o['numFilters']       = $this->numFilters   ;
        for ($f = 0; $f < $this->numFilters; $f++) {
            if ($this->filters[$f]->isPopulated()) {
                $o["QB_filter_field_$f"]    = $this->filters[$f]->getField();
                $o["QB_filter_compOper_$f"] = $this->filters[$f]->getCompOper();
                $o["QB_filter_value_$f"]    = $this->filters[$f]->getValue();
            }

        }
        $o['soql_query']       = $this->soqlQuery    ;
        return json_encode($o);
    }

    //GETTERS
    public function getName() {
        return $this->name;
    }

    public function getExportTo() {
        return $this->exportTo;
    }

    public function getQueryAction() {
        return $this->queryAction;
    }

    public function getObject() {
        return $this->object;
    }

    public function getFields() {
        return $this->fields;
    }

    public function getOrderByField() {
        return $this->orderByField;
    }

    public function getOrderByDir() {
        return $this->orderByDir;
    }

    public function getOrderByNulls() {
        return $this->orderByNulls;
    }

    public function getLimit() {
        return $this->limit;
    }

    public function getMatrixRows() {
        return $this->matrixRows;
    }

    public function getMatrixCols() {
        return $this->matrixCols;
    }

    public function getFilters() {
        return $this->filters;
    }

    public function getSoqlQuery() {
        return $this->soqlQuery;
    }

    //SETTERS
    public function setName($name) {
        $this->name = $name;
    }

    public function setQueryAction($queryAction) {
        $this->queryAction = $queryAction;
    }

    public function setExportTo($exportTo) {
        $this->exportTo = $exportTo;
    }

    public function setObject($object) {
        $this->object = $object;
    }
}

class QueryRequestFilter {
    private $logicOper = "AND";
    private $field     = null;
    private $compOper  = "=";
    private $value     = null;

    public function __construct($field, $compOper, $value, $logicOper = "AND") {
        $this->field = $field;
        $this->compOper = $compOper;
        $this->value = $value;
    }

    public function getField() {
        return $this->field;
    }

    public function getCompOper() {
        return $this->compOper;
    }

    public function getValue() {
        return $this->value;
    }

    public function isPopulated() {
        return isset($this->field) && isset($this->compOper) && isset($this->value);
    }
}
?>