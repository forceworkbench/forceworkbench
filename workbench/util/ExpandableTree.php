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

        addFooterScript("<script type='text/javascript' src='" . getPathToStaticResource('/script/simpletreemenu.js') . "'>\n".
                        "/***********************************************\n".
                        "* Dynamic Countdown script- © Dynamic Drive (http://www.dynamicdrive.com)\n".
                        "* This notice MUST stay intact for legal use\n".
                        "* Visit http://www.dynamicdrive.com/ for this script and 100s more.\n".
                        "***********************************************/\n".
                        "</script>");

        addFooterScript("<script type='text/javascript'>" .
                           "ddtreemenu.createTree('$this->name', true);" .
                            ($this->forceCollapse ? "ddtreemenu.flatten('$this->name', 'collapse');" : "") .
                        "</script>");
    }

    private function printNode($node, $parentKey = null) {
        $systemFields = array("Id","IsDeleted","CreatedById","CreatedDate","LastModifiedById","LastModifiedDate","SystemModstamp");

        // TODO: remove this hacky special casing
        $escape = !($this->name == "listMetadataTree" && ($parentKey == "parentXmlName" || $parentKey == "childXmlNames"));

        foreach ($node as $nodeKey => $nodeValue) {
            $nodeKey = $escape ? htmlspecialchars($nodeKey) : $nodeKey;

            // TODO: replace special case with client defined strategies
            if ($this->name == "describeTree") {
                if (isset($parentKey) && strpos($parentKey, "Fields") > -1) {
                    if (in_array($nodeKey, $systemFields)) {
                        $nodeKey = "<span class='highlightSystemField'>$nodeKey</span>";
                    }
                    else if (substr_compare($nodeKey, "__c", -3) == 0) {
                        $nodeKey = "<span class='highlightCustomField'>$nodeKey</span>";
                    }
                }
            }

            if (is_array($nodeValue) || is_object($nodeValue)) {
                if (is_numeric($nodeKey)) {
                    $nodeKey = $nodeKey + 1;
                }
                print "<li>$nodeKey<ul style='display:none;'>\n";
                if (is_array($nodeValue) && count($nodeValue) == 1 && isset($nodeValue[0]) && is_array($nodeValue[0])) {
                    $this->printNode($nodeValue[0], $nodeKey); // flatten single element arrays
                } else {
                    $this->printNode($nodeValue, $nodeKey);
                }
                print "</ul></li>\n";
            } else {
                $nodeKey = is_numeric($nodeKey) ? "" : $nodeKey . ": ";

                if (is_bool($nodeValue)) {
                    $nodeValue = $nodeValue == 1 ? "<span class='trueColor'>true</span>" : "<span class='falseColor'>false</span>";
                } else {
                    $nodeValue = $escape ? htmlspecialchars($nodeValue) : $nodeValue;
                    $nodeValue = $this->containsDates ? localizeDateTimes($nodeValue) : $nodeValue;
                    $nodeValue = $this->containsIds ? addLinksToIds($nodeValue) : $nodeValue;
                }

                print "<li>$nodeKey<span style='font-weight:bold;'>$nodeValue</span></li>\n";
            }
        }
    }

    public static function processResults($raw, $groupTopLevelScalarsIn = null, $unCamelCaseKeys = false) {
        $processed = array();

        foreach (array(true, false) as $scalarProcessing) {
            foreach ($raw as $rawKey => $rawValue) {
                if (is_array($rawValue) || is_object($rawValue)) {
                    if ($scalarProcessing) continue;

                    $processedSubResults = self::processResults($rawValue, null, $unCamelCaseKeys);
                    $subCount = " (" . count($processedSubResults) . ")";

                    if (isset($rawValue->name) && isset($rawValue->methodName) ) {
                        $processed[$rawValue->name . "." . $rawValue->methodName][] = $processedSubResults;
                    } else if (isset($rawValue->name) && $rawValue->name != "") {
                        $processed[$rawValue->name][] = $processedSubResults;
                    } else if (isset($rawValue->fileName) && $rawValue->fileName != "") {
                        if (isset($rawValue->fullName) && $rawValue->fullName != "" && strpos($rawValue->fileName, $rawValue->fullName) === false) {
                            $processed[$rawValue->fileName . " (" . $rawValue->fullName . ")"][] = $processedSubResults;
                        } else {
                            $processed[$rawValue->fileName][] = $processedSubResults;
                        } 
                    } else if (isset($rawValue->fullName) && $rawValue->fullName != "") {
                        $processed[$rawValue->fullName][] = $processedSubResults;
                    } else if (isset($rawValue->label) && $rawValue->label != "") {
                        $processed[$rawValue->label][] = $processedSubResults;
                    } else if (isset($rawValue->column) && isset($rawValue->line)) {
                        $processed[$rawValue->column . ":" . $rawValue->line][] = $processedSubResults;
                        krsort($processed[$rawValue->column . ":" . $rawValue->line]);
                    } else if (isset($rawValue->childSObject) && isset($rawValue->field)) {
                        $processed[$rawValue->childSObject . "." . $rawValue->field][] = $processedSubResults;
                    } else if ($unCamelCaseKeys) {
                        $processed[unCamelCase($rawKey) . $subCount][] = $processedSubResults;
                    } else {
                        $processed[$rawKey . $subCount][] = $processedSubResults;
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

    public static function getClearCacheMenu() {
        return " | <a href='?clearCache' style='text-decoration:none;'>" .
                    "<span style='text-decoration:underline;'>Clear Cache</span> <img src='" . getPathToStaticResource('/images/sweep.png') . "' border='0' align='top'/>" .
                  "</a>";
    }

}

?>
