// contains helper function(s) for AsyncSOQL Define Query tab; functions are called to 
// 1. construct and send ajax requests to asyncSOQLDisplayFields.php and asyncSOQLSubmitJob.php
// 2. check DOM elements before proceeding to the next step

var xhttp = new XMLHttpRequest(); // ajax request
var READY = 4;
var OK = 200;

function updateSourceFields() {
    var source_object = document.getElementById("QB_object_sel").value;
    xhttp.onreadystatechange = function() {       
        if (xhttp.readyState == READY && xhttp.status == OK) {
            var source_fields = xhttp.responseText;
            document.getElementById("soql_query_textarea").value =  "";
            document.getElementById("sourcefields_container").innerHTML = source_fields; 
            var QB_right_sub_table = document.getElementById("QB_right_sub_table");
            QB_right_sub_table.innerHTML = "";
            var new_table_body = document.createElement('tbody');
            QB_right_sub_table.appendChild(new_table_body);
            var num_filters = document.getElementById("numFilters");
            num_filters.value = 1;
            //used in buildQuery() function in query.js
            var field_array = document.getElementById("field_array");
            if (field_array != null) {
                 field_type_array = JSON.parse(field_array.value);
             }           
            addFilterRow(0,null,null,null);
            toggleFieldDisabled();
            }
    };
    xhttp.open("GET", "asyncSOQLDisplayFields.php?object="+source_object+"&action=displaySourceFields", true);
    xhttp.setRequestHeader('Authorization', null);
    xhttp.send();
}

function sourceBlocker() {
    var soql = document.getElementById('soql_query_textarea').value;
    if (soql == null || soql.length ==0){
        return false;
    } else {
        displayMapping();
    }
}

function showTargetExternalFields() {
    var operation_type_value = document.getElementById("operation_type").value;
    if (operation_type_value =='upsert') {
        document.getElementById("target_external_field").className = "";
    } else if (operation_type_value =='insert') {
        document.getElementById("target_external_field").className = "hidden";
    }
}

function displayMapping() {
    document.getElementById("source_object").className = "hidden";
    document.getElementById("target_object").className = "";
    var soql = document.getElementById("soql_query_textarea").value;
    var source_object = document.getElementById("QB_object_sel").value;
    var tokens = tokenize(soql, source_object);
    var num_tokens = tokens.length;
    document.getElementById("num_tokens").value = num_tokens;
    for (var i = 0, row; i<num_tokens; i++) {
        
        var row = "<select id='target_field_sel_"+i+"' name='target_field_sel_"+i+"' style='width: 16em;' ></select>";       
        
        var new_mapping_cell = document.createElement('td');
        new_mapping_cell.setAttribute('vAlign','middle');
        new_mapping_cell.setAttribute('nowrap','true');
        new_mapping_cell.innerHTML = row;
        
        var new_mapping_row = document.createElement('tr');
        new_mapping_row.appendChild(new_mapping_cell);
        
        document.getElementById('mapping_table').getElementsByTagName("TBODY").item(0).appendChild(new_mapping_row);
    }
    generateSourceFields(tokens);
    addValueRows(0);
}

function tokenize(soql, source) {
    var regexp = new RegExp("\\bFROM\\b\\s+\\b(" + source + ")\\b", "gi");
    var match = regexp.exec(soql);
    soql = soql.trim(); 
    // var start = soql.indexOf("SELECT")+7;
    var start = soql.indexOf(" ")+1;
    var finish = match.index;
    var soql_fields_str = soql.substring(start, finish);
    soql_fields_str = soql_fields_str.trim(); 
    var tokenized_soql = soql_fields_str.split(",");
    return tokenized_soql;
}

function generateSourceFields(tokenized_soql) {
    var num_tokens = tokenized_soql.length;
    var source_fields = new Array(num_tokens);
    for ( var i = 0; i < num_tokens; i++ ) {
        var current_token = tokenized_soql[i];
        current_token = current_token.trim();
        current_token = current_token.split(/\s+/);
        var num_elements = current_token.length;
        if (num_elements == 1) {  //either field OR aggregate field and alias mentioned with space
            var token = current_token[0];
            var alias;
            if (current_token[0].indexOf('(') > -1) { //if aggregate field
                token_and_alias = current_token[0].split(")");
                token = token_and_alias[0]+')';
                source_fields[i] = token.valueOf();
                if (token_and_alias.length > 1) {
                    alias = token_and_alias[1].trim();
                    if (alias.length > 1) { //if alias is present
                        source_fields[i] = alias.valueOf();
                    }    
                } 
            }
            if (source_fields[i] === undefined || source_fields[i] === null) { //if not aggregate field
                source_fields[i] = token.valueOf();
            }

        } else { //field and alias are present
            var alias = current_token[1];
            source_fields[i] = alias;
        }       
    }    
    var mapping_table = document.getElementById('mapping_table');
    j = 0;
    var aggr_count = 0;
    for (var i = 0, row; row = mapping_table.rows[i]; i++) { //first row of table is title, so start from second row 
        var cell = row.insertCell(0);
        cell.setAttribute('id','source_field_'+i);
        if (source_fields[j].indexOf('(') > -1) { //if aggregate field, replace with 'expr' aliases
            cell.innerHTML = "expr"+(aggr_count++).toString();
        } else {
            cell.innerHTML = source_fields[j];
        }
        j++;
    }
}

function updateTargetFields(){
    var target_object = document.getElementById("target_object_sel").value;
    xhttp.onreadystatechange = function() {
        
        if (xhttp.readyState == 4 && xhttp.status == 200) {

            var jobDetails = xhttp.responseText;
            var num_tokens = document.getElementById("num_tokens").value;
            document.getElementById("target_field_sel_external").innerHTML = jobDetails;
            for (var i=0; i<num_tokens; i++){
                var field_name = "target_field_sel_"+i.toString();
                document.getElementById(field_name).innerHTML = jobDetails;
            }
            var value_table = document.getElementById("value_table");
            value_table.innerHTML = "";
            var num_values = document.getElementById("num_values");
            num_values.value = 1;
            addValueRows(0);

        }
    };
xhttp.open("GET", "asyncSOQLDisplayFields.php?object="+target_object+"&action=displayTargetFields", true);
xhttp.setRequestHeader('Authorization', null);
xhttp.send();
}



function addValueRows(value_row_num) {
    var target_fields_dropdown = document.getElementById("target_field_sel_0");
    var target_object_all_fields = new Array(target_fields_dropdown.length);
    for (var i = 0; i < target_fields_dropdown.length; i++) {
        target_object_all_fields[i] = target_fields_dropdown.options[i].value;
    }
  
    var text_value = "<input type='text' style='width: 16em;' name='target_value[]' id='target_value_"+value_row_num+"'/>";  
    var row = "<select style='width: 16em;' name='target_value_field_sel[]' id='target_value_field_sel_"+value_row_num+"'>";

    for (var key in target_object_all_fields) {
        field = target_object_all_fields[key];
        row += "<option value='" + field + "'";
        row += "'>" + field + "</option>";
    }

    var new_value_cell = document.createElement('td');
    new_value_cell.setAttribute('vAlign','middle');
    new_value_cell.setAttribute('nowrap','true');
    new_value_cell.innerHTML = text_value;

    var new_field_cell = document.createElement('td');
    new_field_cell.setAttribute('vAlign','middle');
    new_field_cell.setAttribute('nowrap','true');
    new_field_cell.innerHTML = row;

    var new_plus_cell = document.createElement('td');
    new_plus_cell.setAttribute('id','value_plus_cell_' + value_row_num);
    new_plus_cell.setAttribute('vAlign','bottom');
    new_plus_cell.setAttribute('style','text-align: center;');
    new_plus_cell.setAttribute('width', '30px');
    new_plus_cell.innerHTML = "<img id='filter_plus_button' src='" + getPathToStaticResource('/images/plus_icon.jpg') + "' onclick='addValueRows(document.getElementById(\"num_values\").value++);' onmouseover='this.style.cursor=\"pointer\";'  style='padding-top: 4px;'/>";

    var new_targetfield_row = document.createElement('tr');
    new_targetfield_row.setAttribute('id','Target_' + value_row_num);
    new_targetfield_row.appendChild(new_value_cell);
    new_targetfield_row.appendChild(new_field_cell);
    new_targetfield_row.appendChild(new_plus_cell);

    document.getElementById('value_table').appendChild(new_targetfield_row);
}

function targetBlocker() {
    var target_object = document.getElementById('target_object_sel').value;
    if (target_object == null || target_object.length ==0) {
        alert('Target object must be selected');
        return false;
    }
    var num_tokens = document.getElementById("num_tokens").value;
    for (var i=0; i < num_tokens; i++) {
        var field_name = "target_field_sel_"+i.toString();
        var selected_target = document.getElementById(field_name).value;
        if (selected_target == null || selected_target.length ==0 ) {
            alert("Target field(s) must be assigned");
            return false;
        }
    }
    mapSubmit.disabled = true;
    submitJob();
}

function showSourceObjectsStep(){
    mapSubmit.disabled = false;
    document.getElementById("target_object").className = 'hidden';
    document.getElementById("results_container").className = 'hidden';
    document.getElementById("target_external_field").className= 'hidden';
    document.getElementById("operation_type").selectedIndex=0;
    var mapping_table = document.getElementById("mapping_table");
    while(mapping_table.rows.length > 0) {
      mapping_table.deleteRow(0);
    }
    document.getElementById("value_table").innerHTML = "";
    document.getElementById("target_object_sel").selectedIndex = -1;
    document.getElementById("source_object").className = '';  
}

function clearSourceObject(){
    document.getElementById("QB_object_sel").selectedIndex = -1;
    document.getElementById("soql_query_textarea").value = "";
    var QB_right_sub_table = document.getElementById("QB_right_sub_table");
    while(QB_right_sub_table.rows.length > 0) {
      QB_right_sub_table.deleteRow(0);
    }
    QB_field_sel = document.getElementById("QB_field_sel");
    QB_field_sel.options.length = 0;
    QB_field_sel.setAttribute('size','5');
    addFilterRow(0,null,null,null);
    toggleFieldDisabled();
    
}

function submitJob() {
    var source_object = document.getElementById("QB_object_sel").value;
    var source_query = document.getElementById("soql_query_textarea").value;
    var operation_type = document.getElementById("operation_type").value;
    var target_object = document.getElementById("target_object_sel").value;
    
    var mapping_table = document.getElementById('mapping_table');
    var target_field_map_arrays = mapSourceToTarget(mapping_table, 'td', 'source_field_', 'target_field_sel_');
    var target_external_field = document.getElementById("target_field_sel_external").value;
    var source_fields = target_field_map_arrays.source_array;
    var target_fields = target_field_map_arrays.target_array;  
    var mapped_fields = createMappingJson(source_fields,target_fields);

    var table = document.getElementById('value_table'); 
    var target_value_map_arrays = mapSourceToTarget(value_table, 'text', 'target_value_', 'target_value_field_sel_');
    var target_values = target_value_map_arrays.source_array;
    var target_value_fields = target_value_map_arrays.target_array;  
    var value_fields = createMappingJson(target_values,target_value_fields);

    xhttp.onreadystatechange = function() {       
        if (xhttp.readyState == 4 && xhttp.status == 200) {
            submit_job_result = xhttp.responseText;
            document.getElementById("submit_job_results").innerHTML = submit_job_result; 
            document.getElementById("target_object").className = "hidden";
            document.getElementById("results_container").className = "";
        }
    };
    xhttp.open("GET", "asyncSOQLSubmitJob.php?sourceObject="+source_object+"&queryType="+operation_type+"&targetExternalIdField="+target_external_field+"&targetObject="+target_object+"&sourceQuery="+source_query+"&mappedfields="+mapped_fields+"&valuefields="+value_fields, true);
    xhttp.setRequestHeader('Authorization', null);
    xhttp.send();
}

function mapSourceToTarget(table, html_element_type, source_prefix, target_prefix) {
    source_array = [];
    target_array = [];
    for (var i = 0, row; row = table.rows[i]; i++) {
        var postfix = (i).toString();
        var source_id = source_prefix + postfix;
        var source;
        if (html_element_type == 'td') {
            source = document.getElementById(source_id).textContent;
        } else {
            source = document.getElementById(source_id).value;
        }
        source_array.push(source);        
        var target_id = target_prefix + postfix;
        var target = document.getElementById(target_id).value;
        target_array.push(target);
    }
    return {
     source_array: source_array,
     target_array: target_array
    }
}

function createMappingJson(source_array,target_array) {
    var mapped_string="";
    for (var i=0; i < source_array.length; i++) {
        if ((source_array[i].length>0) && (target_array[i].length>0)) {
            mapped_string = mapped_string+'"'+source_array[i]+'": "' +target_array[i]+ '",';
        }
    }
    mapped_string = mapped_string.slice(0, -1);
    return mapped_string;
}
