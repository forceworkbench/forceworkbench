<?php
class SearchRequest {
    //FIELDS
    private $name                = null;//
    private $searchString        = null;
    private $fieldType           = null;
    private $limit               = null;
    private $returningObjects    = array();
    private $numReturningObjects = null;
    private $soslSearch          = null;

    //CONSTRUCTORS
    public function __construct($source) {
        if(isset($source['SB_searchString']))     $this->searchString         = $source['SB_searchString'];
        if(isset($source['SB_fieldTypeSelect']))  $this->fieldType            = $source['SB_fieldTypeSelect'];
        if(isset($source['SB_limit']))            $this->limit                 = $source['SB_limit'];
        if(isset($source['numReturningObjects'])) $this->numReturningObjects  = $source['numReturningObjects'];

        for ($ro = 0; $ro < $this->numReturningObjects; $ro++) {
            if (isset($source["SB_objSelect_$ro"]) && isset($source["SB_objDetail_$ro"])) {
                $this->returningObjects[$ro] = new ReturningObject($source["SB_objSelect_$ro"], $source["SB_objDetail_$ro"]);
            } else {
                $this->returningObjects[$ro] = new ReturningObject(null, null);
            }
        }

        if (isset($source['sosl_search'])) {
            if (get_magic_quotes_gpc()) {
                $this->soslSearch = stripslashes($source['sosl_search']);
            } else {
                $this->soslSearch = $source['sosl_search'];
            }
        }
    }

    public function toJson() {
        $o = array();
        $o['SB_searchString']     = $this->searchString;
        $o['SB_fieldTypeSelect']  = $this->fieldType;
        $o['SB_limit']            = $this->limit;
        $o['numReturningObjects'] = $this->numReturningObjects;
        for ($ro = 0; $ro < $this->numReturningObjects; $ro++) {
            if ($this->returningObjects[$ro]->isPopulated()) {
                $o["SB_objSelect_$ro"] = $this->returningObjects[$ro]->getObject();
                $o["SB_objDetail_$ro"] = $this->returningObjects[$ro]->getFields();
            }
        }
        $o['sosl_search'] = $this->soslSearch;
        return json_encode($o);
    }

    //GETTERS
    public function getName() {
        return $this->name;
    }

    public function getSearchString() {
        return $this->searchString;
    }

    public function getFieldType() {
        return $this->fieldType;
    }

    public function getLimit() {
        return $this->limit;
    }

    public function getReturningObjects() {
        return $this->returningObjects;
    }

    public function getSoslSearch() {
        return $this->soslSearch;
    }

    //SETTERS
    public function setName($name) {
        $this->name = $name;
    }
}

class ReturningObject {
    private $object = null;
    private $fields = null;

    public function __construct($object, $fields) {
        $this->object = $object;
        $this->fields = $fields;
    }

    public function getObject() {
        return $this->object;
    }

    public function getFields() {
        return $this->fields;
    }

    public function isPopulated() {
        return isset($this->object) && isset($this->fields);
    }
}
?>