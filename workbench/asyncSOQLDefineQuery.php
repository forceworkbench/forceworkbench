<!-- Front end elements of Async SOQL Submit Jobs tab; Used to select the source object, source fields, apply filters (optional) and edit source query -->

<?php
require_once 'session.php';
require_once 'shared.php';

set_exception_handler('handleAllExceptionsNoHeaders');
?>

<link
    rel="stylesheet" type="text/css"
    href="<?php echo getPathToStaticResource('/style/restexplorer.css'); ?>" />
<head>
    <script 
        type="text/javascript" 
        src="<?php echo getPathToStaticResource('/script/query.js'); ?>"></script>
    <script 
        type="text/javascript" 
        src="<?php echo getPathToStaticResource('/script/async_submitjobs.js'); ?>"></script>
    <script
        type="text/javascript"
        src="<?php echo getPathToStaticResource('/script/restexplorer.js'); ?>"></script>
    <script
        type="text/javascript"
        src="<?php echo getPathToStaticResource('/script/wz_tooltip.js'); ?>"></script>
</head>

<!-- hidden fields to use buildQuery() function in query.js -->
<input type='hidden' id='export_action_screen' name='export_action' value='screen' >
<input type='hidden' id='QB_orderby_field' name='QB_orderby_field' >
<input type='hidden' id='QB_orderby_sort' name='QB_orderby_sort' >
<input type='hidden' id='QB_nulls' name='QB_nulls' >
<input type='hidden' id='QB_limit_txt' size='10' name='QB_limit_txt' />
<input type='hidden' id='matrix_rows' size='10' name='matrix_rows' />

<!-- hidden fields to control the number of filter and target value rows -->
<input type='hidden' id='num_values' name='num_values' value='1' />
<input type='hidden' id='numFilters' name='numFilters' value='1'/>

<input type='hidden' id='num_tokens' name='num_tokens'/>

<!-- Source div -->
<div id='source_object'>
    <h3> <i> Source Object and Fields: </i> </h3>
    <p class='instructions'>Choose the source object and the fields to map to the target object</p>
    <table id='source_container'>
        <tr><td>
            Source object:&nbsp;
            <?php
            printObjectSelection(false, 'QB_object_sel', "16", "onChange='updateSourceFields();'", "queryable");
            ?>
        </td></tr>
        <tr><td valign='top'><br/>Source fields:&nbsp;&nbsp;
            <div id = 'sourcefields_container'>
                <select id='QB_field_sel' name='QB_field_sel[]' multiple='mutliple' size='5' style='width: 16em; height: 100%; display:inline-block;' onChange='buildQuery();'>\n";
                </select>
            </div>
        </td>
        <td valign='top'>
            <?php
            // for opertors used in filters as part of constructing the query
            $ops = getComparisonOperators();
            ?>
            <table id='QB_right_sub_table' border='0' style='width: 100%;'>
                <tbody></tbody>
            </table> 
        </td></tr>
    </table> 

    <?php
    print "<br/>Enter or modify query below:\n" .
            "<br/><textarea id='soql_query_textarea' type='text' name='soql_query_textarea' rows='" . WorkbenchConfig::get()->value("textareaRows") . "' style='width: 99%; overflow: auto; font-family: monospace, courier;'></textarea>\n";
    ?>
    <input type='submit' id='querySubmit' name='querySubmit' class='disableWhileAsyncLoading' value='Next';/>
</div>

<!-- Target div -->
<div id='target_object' class='hidden'>
    <a href='javascript:showSourceObjectsStep();' style="float: right;">Back to Source Objects</a>
    <h3> <i> Query Type, Target Object and Fields: </i> </h3>
    <p class='instructions'>Choose query type, the target object and map fields and values</p>
    <table id='target_container'>
        <tr><td>
            Operation Type:&nbsp;
            <?php 
            print "<select id='operation_type' onChange='showTargetExternalFields()'>
                        <option value='insert' selected='selected'>INSERT</option>
                        <option value='upsert'>UPSERT</option>
                    </select>";
            ?>
        </td></tr>
        <tr><td>
            Target object:&nbsp;
            <?php
            printObjectSelection(false, 'target_object_sel', "16", "onChange='updateTargetFields();'", "queryable");
            ?>
        </td></tr>
        <tr id='target_external_field' class='hidden'><td>
            Target External Id Field:&nbsp;
            <?php
            print "<select id='target_field_sel_external' name='target_field_sel_external' style='width: 16em;' ></select>";
            ?>
        </td></tr>
        <tr><td>
            <?php
            print "<p>Map source fields to target fields:<label class='note'> View source query here<img align='right' height='15' width='15' onmouseover=\"Tip(document.getElementById('soql_query_textarea').value)\" align='right' src='" . getPathToStaticResource('/images/letter-q.png') . "'/></label></p>";
            ?>
            <div id='targetFields'>
                <table id='mapping_table' name='mapping_table' class ='list' border='0' align='left' style='width: 100%;'>
                    <tbody></tbody>
                </table>
            </div>
        </td></tr>
        <tr><td>
            <br>Assign target values to fields (optional): &nbsp;
            <?php
            print "<img onmouseover=\"Tip('Arbitrary text values to be written to result records. Map key is literal value, map value is field name in targetObject. If \'\$JOB_ID\' is used as the key, Async SOQL will write the Id of the current job to specified target field, which can be of type TEXT or LOOKUP')\" src='" . getPathToStaticResource('/images/help16.png') . "'/>";
            ?>
            <table id='value_table' border='0'>
            </table><br>
        </td></tr>
    </table>
    <input type='submit' id='mapSubmit' name='mapSubmit' class='disableWhileAsyncLoading' value='Submit';/>
</div>

<!-- Results div -->
<div id='results_container' class='hidden'>
    <a href='javascript:clearSourceObject();showSourceObjectsStep();' style="float: right;">Back to Source Objects</a><br><br>
    <div id='submit_job_results'>
    </div>    
</div>

<script>
    document.getElementById("querySubmit").setAttribute('onclick', 'return sourceBlocker();');
    document.getElementById("mapSubmit").setAttribute('onclick', 'targetBlocker()');
    var compOper_array = <?php echo json_encode($ops); ?>;
    var field_type_array = [];
    //default first filter on page
    addFilterRow(0,null,null,null);
    toggleFieldDisabled();
</script>
