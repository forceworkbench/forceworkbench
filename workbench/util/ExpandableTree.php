<?php
 
class ExpandableTree {

    private $name;
    private $nodes;
    private $forceCollapse = false;
    private $additionalMenus = null;
    private $containsIds = false;
    private $containsDates = false;

    function __construct($name, $nodes) {
        $this->name = $name;
        $this->nodes = $nodes;
    }

    public function setForceCollapse($forceCollapse) {
        $this->forceCollapse = $forceCollapse;
    }

    public function setAdditionalMenus($additionalMenus) {
        $this->additionalMenus = $additionalMenus;
    }

    public function setContainsIds($containsIds) {
        $this->containsIds = $containsIds;
    }

    public function setContainsDates($containsDates) {
        $this->containsDates = $containsDates;
    }

    public function printTree() {
        print "<a class=\"pseudoLink\" onclick=\"javascript:ddtreemenu.flatten('$this->name', 'expand'); return false;\">Expand All</a> | " .
              "<a class=\"pseudoLink\" onclick=\"javascript:ddtreemenu.flatten('$this->name', 'collapse'); return false;\">Collapse All</a>\n";

        if (isset($this->additionalMenus)) {
            print $this->additionalMenus;
        }

        print "<ul id='$this->name' class='treeview'>";

        $this->printNode($this->nodes);

        print "</ul>\n";

        addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/simpletreemenu.js'></script>");

        addFooterScript("<script type='text/javascript'>" .
                           "ddtreemenu.createTree('$this->name', true);" .
                            ($this->forceCollapse ? "ddtreemenu.flatten('$this->name', 'collapse');" : "") .
                        "</script>");
    }

    private function printNode($node) {
        foreach ($node as $nodeKey => $nodeValue) {
            if (is_array($nodeValue) || is_object($nodeValue)) {
                print "<li>$nodeKey<ul style='display:none;'>\n";
                $this->printNode($nodeValue);
                print "</ul></li>\n";
            } else {
                $nodeKey = is_numeric($nodeKey) ? "" : htmlspecialchars($nodeKey) . ": ";

                if (is_bool($nodeValue)) {
                    $nodeValue = $nodeValue == 1 ? "<span class='trueColor'>true</span>" : "<span class='falseColor'>false</span>";
                } else {
                    $nodeValue = htmlspecialchars($nodeValue);
                    $nodeValue = $this->containsDates ? localizeDateTimes($nodeValue) : $nodeValue;
                    $nodeValue = $this->containsIds ? addLinksToUiForIds($nodeValue) : $nodeValue;
                }

                print "<li>$nodeKey<span style='font-weight:bold;'>$nodeValue</span></li>\n";
            }
        }
    }

    public static function processResults($raw, $groupTopLevelScalarsIn = null, $unCamelCaseKeys = false, $parentRawKey = null) {
        $systemFields = array("Id","IsDeleted","CreatedById","CreatedDate","LastModifiedById","LastModifiedDate","SystemModstamp");
        $processed = array();

        foreach (array(true, false) as $scalarProcessing) {
            foreach ($raw as $rawKey => $rawValue) {
                if (is_array($rawValue) || is_object($rawValue)) {
                    if ($scalarProcessing) continue;

                    $processedSubResults = self::processResults($rawValue, null, $unCamelCaseKeys, $rawKey);
                    $subCount = " (" . count($processedSubResults) . ")";

                    if (isset($rawValue->name) && $rawValue->name != "") {
                        $nameKey = $rawValue->name;
                        if (isset($parentRawKey) && $parentRawKey == "fields") {
                            if (in_array($rawValue->name, $systemFields)) {
                                $nameKey = "<span class='highlightSystemField'>$rawValue->name</span>";
                            } else if ($rawValue->custom) {
                                $nameKey = "<span class='highlightCustomField'>$rawValue->name</span>";
                            }
                        }
                        $processed[$nameKey] = $processedSubResults;
                    } else if (isset($rawValue->fileName) && $rawValue->fileName != "") {
                        $processed[$rawValue->fileName] = $processedSubResults;
                    } else if (isset($rawValue->fullName) && $rawValue->fullName != "") {
                        $processed[$rawValue->fullName] = $processedSubResults;
                    } else if (isset($rawValue->label) && $rawValue->label != "") {
                        $processed[$rawValue->label] = $processedSubResults;
                    } else if (isset($rawValue->column) && isset($rawValue->line)) {
                        $processed[$rawValue->column . ":" . $rawValue->line] = $processedSubResults;
                        krsort($processed);
                    } else if (isset($rawValue->childSObject) && isset($rawValue->field)) {
                        $processed[$rawValue->childSObject . "." . $rawValue->field] = $processedSubResults;
                    } else if ($unCamelCaseKeys) {
                        $processed[unCamelCase($rawKey) . $subCount] = $processedSubResults;
                    } else {
                        $processed[$rawKey . $subCount] = $processedSubResults;
                    }
                } else {
                    if ($groupTopLevelScalarsIn != null) {
                        $processed[$groupTopLevelScalarsIn][$rawKey] = $rawValue;
                    } else {
                        $processed[$rawKey] = $rawValue;
                    }
                }
            }
        }

        return $processed;
    }

}

?>
