// contains helper function(s) for AsyncSOQL View Status tab; functions are called to 
// 1. construct and send ajax requests to asyncSOQLViewJobDetails.php
// 2. render HTML elements in asyncViewStatus.php

var xhttp = new XMLHttpRequest(); // ajax request
var READY = 4
var OK = 200

function toggleTable() {
    var results_table = document.getElementById("soql_results");
    if (results_table.className == "hidden") {
        results_table.className = "none";
        document.getElementById('viewResultsToggler').innerHTML = "Hide Target Object query results";
    }
    else {
        results_table.className = "hidden";
        document.getElementById('viewResultsToggler').innerHTML = "Show Target Object query results";
    }
}

function queryMoreRecords(value) {
    var queryLocator = document.getElementById("queryLocator").value;
    xhttp.onreadystatechange = function() {
        if (xhttp.readyState == 4 && xhttp.status == 200) {
            var more_query_records = xhttp.responseText;
            document.getElementById("soql_results").innerHTML = more_query_records;
            var query_more_top_btn = document.getElementById("queryMoreButtonTop");
            var query_more_bottom_btn = document.getElementById("queryMoreButtonBottom");
            if (query_more_top_btn != null) {
                query_more_top_btn.setAttribute('onclick', "queryMoreRecords(\'' +value+ '\')");
            }
            if (query_more_bottom_btn != null) {
                query_more_bottom_btn.setAttribute('onclick', "queryMoreRecords(\'' +value+ '\')");
            }
        }
    };
    xhttp.open("GET", "asyncSOQLViewJobDetails.php?action=queryMore&queryLocator="+queryLocator, true);
    xhttp.setRequestHeader('Authorization', null);
    xhttp.send();
}

function cancelJob(value) {
    var xhttp;
    xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (xhttp.readyState == READY && xhttp.status == OK) {
            var cancel_job_status = xhttp.responseText;
        }
    };
    xhttp.open("GET", "asyncSOQLViewJobDetails.php?id="+value+"&action=cancel", true);
    xhttp.setRequestHeader('Authorization', null);
    xhttp.send();
} 

function showGrid(data) {
    var result = JSON.parse(data);
    var async_jobs = result.asyncQueries;
    var results_table = document.getElementById("results");
    results_table.className = 'list';
    for (var i = 0; i < async_jobs.length; i++) {
        var row = results_table.insertRow(i+1); //pagination treats first row of table as title
        var jobDetails = async_jobs[i];
        var keys = Object.keys(jobDetails);
        var colnum=0;
        for (var j=0; j < keys.length; j++) {
            var field = keys[j];
            var value = jobDetails[field];
            if ((field == 'jobId') || (field == 'status') || (field == 'targetObject') || (field == 'query')) {
                var cell = row.insertCell(colnum++);
                if (field == 'jobId') {
                    row.id = value;                    
                    var link = document.createElement("a");
                    link.setAttribute("id", value);  
                    link.setAttribute("href", "#"+value);
                    link.setAttribute( "onClick", "javascript: showJobDetails(this.id); return false;" );
                    var linkText = document.createTextNode(value);
                    link.appendChild(linkText);
                    cell.appendChild(link);
                } else {
                    if (field == 'status'){
                        cell.style.fontWeight = 'bold';
                        cell.style.color = setColor(value);
                        
                    } else if ((field == 'query') && (value.length>45)) { // show only 45 or less chars of query in the grid
                        value = value.substring(0, 45)+"...";
                    }
                    cell.innerHTML = value;  
                }   
            }                
        } 
    }
}

function showJobDetails(value) {
    var job = value;
    xhttp.onreadystatechange = function() {
        if (xhttp.readyState == 4 && xhttp.status == 200) {
            var job_details = xhttp.responseText;
            document.getElementById("details_table").innerHTML = job_details;
            var cancel = document.getElementById("cancel");
            if (document.getElementById("disable_cancel").value == "true") {
                cancel.disabled = true;
            } else {
                cancel.disabled = false;
            }
            document.getElementById("all_jobs").className = "hidden";
            document.getElementById("job_details").className = "";
            document.getElementById("type_jobID").className = "hidden";

            var grid_status_cell = document.getElementById(value).cells[2];
            var details_table_status_cell  = document.getElementById(value+'_status');
            grid_status_cell.style.color = details_table_status_cell.style.color;

            grid_status_cell.innerHTML = details_table_status_cell.innerHTML;

            cancel.onclick = function() {
                cancelJob(value);
                showJobDetails(value);
            };

            var refresh = document.getElementById("refresh");
            refresh.onclick = function() {
                showJobDetails(value);
                var grid_status_cell = document.getElementById(value).cells[2];
                var details_table_status_cell  = document.getElementById(value+'_status');
                grid_status_cell.style.color = details_table_status_cell.style.color;
                grid_status_cell.innerHTML = details_table_status_cell.innerHTML;
            };

            var query_more_top_btn = document.getElementById("queryMoreButtonTop");
            var query_more_bottom_btn = document.getElementById("queryMoreButtonBottom");
            if (query_more_top_btn != null) {
                query_more_top_btn.setAttribute('onclick', "queryMoreRecords(\'' +value+ '\')");
            }
            if (query_more_bottom_btn != null) {
                query_more_bottom_btn.setAttribute('onclick', "queryMoreRecords(\'' +value+ '\')");
            }
        }
    };
    xhttp.open("GET", "asyncSOQLViewJobDetails.php?id="+value+"&action=display", true);
    xhttp.setRequestHeader('Authorization', null);
    xhttp.send();
}

function cancelJob(value) {
    var xhttp;
    xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (xhttp.readyState == READY && xhttp.status == OK) {
            var cancel_job_status = xhttp.responseText;
        }
    };
    xhttp.open("GET", "asyncSOQLViewJobDetails.php?id="+value+"&action=cancel", true);
    xhttp.setRequestHeader('Authorization', null);
    xhttp.send();
} 

function showAllJobsTable() { 
    window.location.hash = "viewStatus";
    document.getElementById("all_jobs").className = "";
    document.getElementById("job_details").className = "hidden";
    document.getElementById("type_jobID").className = "";
    var refresh = document.getElementById("refresh");
    refresh.onclick = function() {
        location.reload();
    };
}

function setColor (value) {
    var color;
    switch (value) {
    case "Complete":
        color = 'ForestGreen';
        break;
    case "Running":
        color = 'DodgerBlue';
        break;
    case "Canceled":
        color = 'SlateGrey';
        break;
    case "New":
        color = 'MediumBlue';
        break;
    case "Error":
        color = 'Red';
        break;
    }
    return color;
}