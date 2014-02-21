<?php
include_once "futures.php";

class QueryFutureTask extends FutureTask {

    private $queryRequest;
    private $queryLocator;
    private $nextQueryLocator;
    private $totalQuerySize;

    function __construct($queryRequest, $queryLocator = null) {
        parent::__construct();
        $this->queryRequest = $queryRequest;
        $this->queryLocator = $queryLocator;
    }

    function perform() {
        ob_start();

        $queryTimeStart = microtime(true);
        $records = $this->query($this->queryRequest->getSoqlQuery(), $this->queryRequest->getQueryAction(), $this->queryLocator);
        $queryTimeEnd = microtime(true);
        $queryTimeElapsed = $queryTimeEnd - $queryTimeStart;
        $this->displayQueryResults($records, $queryTimeElapsed, $this->queryRequest);

        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    function query($soqlQuery,$queryAction,$queryLocator = null,$suppressScreenOutput=false) {
        if (!WorkbenchConfig::get()->value("allowParentRelationshipQueries") && preg_match("/SELECT.*?(\w+\.\w+).*FROM/i", $soqlQuery, $matches)) {

            $msg = "Parent relationship queries are disabled in Workbench: " . $matches[1];

            if (WorkbenchConfig::get()->overrideable("allowParentRelationshipQueries")) {
                $msg .= "\n\nDue to issues rendering query results, parent relationship queries are disabled by default. " .
                         "If you understand these limitations, parent relationship queries can be enabled under Settings. " .
                         "Alternatively, parent relationship queries can be run with REST Explorer under the Utilities menu without issue.";
            }

            throw new WorkbenchHandledException($msg);
        }

        try {
            if ($queryAction == 'Query') $queryResponse = WorkbenchContext::get()->getPartnerConnection()->query($soqlQuery);
            if ($queryAction == 'QueryAll') $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryAll($soqlQuery);
        } catch (SoapFault $e) {
            foreach (array("MALFORMED_QUERY", "INVALID_FIELD", "INVALID_TYPE", "INVALID_QUERY_FILTER_OPERATOR", "QUERY_TIMEOUT", "EXCEEDED_ID_LIMIT") as $known) {
                if (strpos($e->getMessage(), $known) > -1) {
                    throw new WorkbenchHandledException($e->getMessage());
                }
            }
            throw $e;
        }

        if ($queryAction == 'QueryMore' && isset($queryLocator)) $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryMore($queryLocator);

        if (stripos($soqlQuery, "count()") && $suppressScreenOutput == false) {
            return $queryResponse->size;
        } else if (!isset($queryResponse->records)) {
            return null;
        }

        $records = $queryResponse->records;

        $this->totalQuerySize = $queryResponse->size;

        if (!$queryResponse->done) {
            $this->nextQueryLocator = $queryResponse->queryLocator;
        }

        //correction for documents and attachments with body. issue #176
        if ($queryResponse->size > 0 && !is_array($records)) {
            $records = array($records);
        }

        $memLimitBytes = toBytes(ini_get("memory_limit"));
        $memWarningThreshold = WorkbenchConfig::get()->value("memoryUsageWarningThreshold") / 100;
        while(($suppressScreenOutput || WorkbenchConfig::get()->value("autoRunQueryMore")) && !$queryResponse->done) {

            if ($memLimitBytes != 0 && (memory_get_usage() / $memLimitBytes > $memWarningThreshold)) {
                displayError("Workbench almost exhausted all its memory after only processing " . count($records) . " rows of data.
                When performing a large queries, it is recommended to export as Bulk CSV or Bulk XML.",
                $suppressScreenOutput, true);
                return; // bail out
            }

            $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryMore($queryResponse->queryLocator);

            if (!is_array($queryResponse->records)) {
                $queryResponse->records = array($queryResponse->records);
            }

            $records = array_merge($records, $queryResponse->records); //todo: do memory check here
        }

        return $records;
    }

    function getQueryResultHeaders($sobject, $tail="") {
        if (!isset($headerBufferArray)) {
            $headerBufferArray = array();
        }

        if (isset($sobject->Id) && !isset($sobject->fields->Id)) {
            $headerBufferArray[] = $tail . "Id";
        }

        if (isset($sobject->fields)) {
            foreach ($sobject->fields->children() as $field) {
                $headerBufferArray[] = $tail . htmlspecialchars($field->getName(),ENT_QUOTES);
            }
        }

        if (isset($sobject->sobjects)) {
            foreach ($sobject->sobjects as $sobjects) {
                $recurse = $this->getQueryResultHeaders($sobjects, $tail . htmlspecialchars($sobjects->type,ENT_QUOTES) . ".");
                $headerBufferArray = array_merge($headerBufferArray, $recurse);
            }
        }

        if (isset($sobject->queryResult)) {
            if(!is_array($sobject->queryResult)) $sobject->queryResult = array($sobject->queryResult);
            foreach ($sobject->queryResult as $qr) {
                $headerBufferArray[] = $qr->records[0]->type;
            }
        }

        return $headerBufferArray;
    }

    function getQueryResultRow($sobject, $escapeHtmlChars=true) {

        if (!isset($rowBuffer)) {
            $rowBuffer = array();
        }

        if (isset($sobject->Id) && !isset($sobject->fields->Id)) {
            $rowBuffer[] = $sobject->Id;
        }

        if (isset($sobject->fields)) {
            foreach ($sobject->fields as $datum) {
                $rowBuffer[] = ($escapeHtmlChars ? htmlspecialchars($datum,ENT_QUOTES) : $datum);
            }
        }

        if (isset($sobject->sobjects)) {
            foreach ($sobject->sobjects as $sobjects) {
                $rowBuffer = array_merge($rowBuffer, $this->getQueryResultRow($sobjects,$escapeHtmlChars));
            }
        }

        if (isset($sobject->queryResult)) {
            $rowBuffer[] = $sobject->queryResult;
        }

        return localizeDateTimes($rowBuffer);
    }

    function createQueryResultsMatrix($records, $matrixCols, $matrixRows) {
        $allColNames = array();
        $allRowNames = array();

        foreach ($records as $rawRecord) {
            $record = new SObject($rawRecord);

            $data = "";
            if (isset($record->Id)) $record->fields->Id = $record->Id;

            foreach ($record->fields as $fieldName => $fieldValue) {
                if ($fieldName == $matrixCols || $fieldName == $matrixRows) {
                    continue;
                }

                $data .= "<em>" . htmlspecialchars($fieldName) . ":</em>  " . htmlspecialchars($fieldValue,ENT_QUOTES) . "<br/>";
            }

            foreach ($record->fields as $rowName => $rowValue) {
                if ($rowName != $matrixRows) continue;
                foreach ($record->fields as $colName => $colValue) {
                    if ($colName != $matrixCols) continue;
                    $allColNames["$colValue"] = $colValue;
                    $allRowNames["$rowValue"] = $rowValue;
                    $matrix["$rowValue"]["$colValue"][] = $data;
                }
            }
        }

        if (count($allColNames) == 0 || count($allRowNames) == 0) {
            displayWarning("No records match matrix column and row selections.", false, true);
            return;
        }

        $table =  "<table id='query_results_matrix' border='1' class='" . getTableClass() . "'>";

        $hw = false;
        foreach ($allRowNames as $rowName) {
            if (!$hw) {
                $table .= "<tr><td></td>";
                foreach ($allColNames as $colName) {
                    $table .= "<th>" . htmlspecialchars($colName) . "</th>";
                }
                $table .= "</tr>";
                $hw = true;
            }

            $table .= "<tr>";
            $table .= "<th>" . htmlspecialchars($rowName) . "</th>";

            foreach ($allColNames as $colName) {
                $table .= "<td>";

                if (isset($matrix["$rowName"]["$colName"])) {
                    foreach ($matrix["$rowName"]["$colName"] as $data) {
                        $table .= "<div class='matrixItem'" . ($data == "" ? "style='width: 0px;'" : "") . ">$data</div>";
                    }
                }

                $table .= "</td>";
            }
            $table .= "</tr>";
        }

        $table .= "</table>";

        return localizeDateTimes($table);
    }

    function createQueryResultTable($records, $rowNum) {
        $table = "<table id='query_results' class='" . getTableClass() . "'>\n";

        //call shared recusive function above for header printing
        $table .= "<tr><th>&nbsp;</th><th>";
        if ($records[0] instanceof SObject) {
            $table .= implode("</th><th>", $this->getQueryResultHeaders($records[0]));
        } else {
            $table .= implode("</th><th>", $this->getQueryResultHeaders(new SObject($records[0])));
        }
        $table .= "</th></tr>\n";


        //Print the remaining rows in the body
        foreach ($records as $record) {
            //call shared recusive function above for row printing
            $table .= "<tr><td>" . $rowNum++ . "</td><td>";

            if ($record instanceof SObject) {
                $row = $this->getQueryResultRow($record);
            } else {
                $row = $this->getQueryResultRow(new SObject($record));
            }


            for ($i = 0; $i < count($row); $i++) {
                if($row[$i] instanceof QueryResult && !is_array($row[$i])) $row[$i] = array($row[$i]);
                if (isset($row[$i][0]) && $row[$i][0] instanceof QueryResult) {
                    foreach ($row[$i] as $qr) {
                        $table .= $this->createQueryResultTable($qr->records, 1);
                        if($qr != end($row[$i])) $table .= "</td><td>";
                    }
                } else {
                    $table .= $row[$i];
                }

                if ($i+1 != count($row)) {
                    $table .= "</td><td>";
                }
            }

            $table .= "</td></tr>\n";
        }

        $table .= "</table>";

        return $table;
    }


    //If the user selects to display the form on screen, they are routed to this function
    function displayQueryResults($records, $queryTimeElapsed, QueryRequest $queryRequest) {
        if (is_numeric($records)) {
            $countString = "Query would return $records record";
            $countString .= ($records == 1) ? "." : "s.";
            displayInfo($countString);
            return;
        }

        if (!$records) {
            displayWarning("Sorry, no records returned.");
            return;
        }

        if (WorkbenchConfig::get()->value("areTablesSortable")) {
            addFooterScript("<script type='text/javascript' src='" . getPathToStaticResource('/script/sortable.js') . "></script>");
        }

        print "<a name='qr'></a><div style='clear: both;'><br/><h2>Query Results</h2>\n";

        if (isset($this->queryLocator)) {
            preg_match("/-(\d+)/", $this->queryLocator, $lastRecord);
            $rowOffset = $lastRecord[1];
        } else {
            $rowOffset = 0;
        }

        $minRowNum = $rowOffset + 1;
        $maxRowNum = $rowOffset + count($records);

        print "<p>Returned records $minRowNum - $maxRowNum of " . $this->totalQuerySize . " total record" .
              ($this->totalQuerySize !== 1 ? "s": "") . " in " . sprintf ("%01.3f", $queryTimeElapsed) . " seconds:</p>\n";

        if (!WorkbenchConfig::get()->value("autoRunQueryMore") && $this->nextQueryLocator) {
            print "<p><input type='hidden' name='queryLocator' value='" . $this->nextQueryLocator . "' /></p>\n";
            print "<p><input type='submit' name='queryMore' id='queryMoreButtonTop' value='More...' /></p>\n";
        }

        print addLinksToIds($queryRequest->getExportTo() == 'matrix'
            ? $this->createQueryResultsMatrix($records, $queryRequest->getMatrixCols(), $queryRequest->getMatrixRows())
            : $this->createQueryResultTable($records, $minRowNum)
        );

        if (!WorkbenchConfig::get()->value("autoRunQueryMore") && $this->nextQueryLocator) {
            print "<p><input type='hidden' name='queryLocator' value='" . $this->nextQueryLocator . "' /></p>\n";
            print "<p><input type='submit' name='queryMore' id='queryMoreButtonBottom' value='More...' /></p>";
        }

        print    "</form></div>\n";
    }

    function exportQueryAsCsv($records,$queryAction) {
        if (!WorkbenchConfig::get()->value("allowQueryCsvExport")) {
            throw new Exception("Export to CSV not allowed");
        }

        if ($records) {
            try {
                $csvFile = fopen('php://output','w') or die("Error opening php://output");
                $csvFilename = "export" . date('YmdHis') . ".csv";
                header("Content-Type: application/csv");
                header("Content-Disposition: attachment; filename=$csvFilename");

                //Write first row to CSV and unset variable
                fputcsv($csvFile, $this->getQueryResultHeaders(new SObject($records[0])));

                //Export remaining rows and write to CSV line-by-line
                foreach ($records as $record) {
                    fputcsv($csvFile, $this->getQueryResultRow(new SObject($record),false));
                }

                fclose($csvFile) or die("Error closing php://output");

            } catch (Exception $e) {
                require_once("header.php");
                displayQueryForm(new QueryRequest($_POST),'csv',$queryAction);
                print "<p />";
                displayError($e->getMessage(),false,true);
            }
        } else {
            require_once("header.php");
            displayQueryForm(new QueryRequest($_POST),'csv',$queryAction);
            print "<p />";
            displayWarning("No records returned for CSV output.",false,true);
        }
    }
}
?>
