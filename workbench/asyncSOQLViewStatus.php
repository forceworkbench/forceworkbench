<!-- Async SOQL View Jobs tab which provides functionalities for view all jobs in a grid, clicking on specific jobs for details, cancelling running jobs and viewing query results of completed jobs -->

<?php
require_once 'session.php';
require_once 'shared.php';

// for REST calls
require_once 'controllers/RestExplorerController.php';
require_once 'async/RestExplorerFutureTask.php';

set_exception_handler('handleAllExceptionsNoHeaders');
?>
<link
    rel="stylesheet" type="text/css"
    href="<?php echo getPathToStaticResource('/style/restexplorer.css'); ?>" />
<head>
    <script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/async_viewjobs.js'); ?>"></script>
    <script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/paging.js'); ?>"></script>
    <script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/restexplorer.js'); ?>"></script>
    <script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/wz_tooltip.js'); ?>"></script>
</head>

<div id='other_buttons_div'>
    <div id='refresh' style="float:left;">
        <input type='submit' value='Refresh' id='refresh'>
    </div>

    <div id = 'type_jobID' style="float:right;">
        Enter specific jobId to view details: <input type='text' id='jobID' name='jobID'><br>
        <div id = 'submit_btn' style="float:right;">
            <input type='submit' id='submit_jobID' name='submit_jobID' value='View' >
        </div>
    </div>
</div>

<br><br>
<div id='all_jobs'>
    <p class='instructions'> Summary of Async SOQL jobs (click on jobId for details): </p>
    <table id='results' class='hidden'>
    <tr> <th> jobId </th> <th> query </th> <th> status </th> <th> targetObject </th> </tr>
    </table>
    <div id='page_nav_position'></div>

<?php
    $c = new RestExplorerController();
    $c->getInstanceForAsyncSOQL(null,'GET');
    echo '<script>console.log("View Status C:")</script>';
    echo '<script>console.log('. json_encode( $c ) .')</script>';
    $f = new RestExplorerFutureTask($c);
    echo '<script>console.log("View status F:")</script>';
    echo '<script>console.log('. json_encode( $f ) .')</script>';
    $f->returnUnformattedResult(true);
    $viewJobsResult = $f->enqueueOrPerform();
    echo '<script>console.log("ViewJobsResult:")</script>';
    echo '<script>console.log('. json_encode( $viewJobsResult ) .')</script>';
    if (isset($viewJobsResult)) {
        $viewJobsResultInst = $viewJobsResult->instResponse;
        echo '<script>console.log("ViewJobsResultInst:")</script>';
        echo '<script>console.log('. json_encode( $viewJobsResultInst ) .')</script>';
        $viewJobsResultInst_json = json_decode($viewJobsResultInst);
        $viewJobsResultNum = count($viewJobsResultInst_json->{'asyncQueries'});
        echo '<script>console.log("ViewJobsResultNum:")</script>';
        echo '<script>console.log('. json_encode( $viewJobsResultNum ) .')</script>';
        $viewJobsResultInst = addslashes((string) $viewJobsResultInst);
        echo '<script>console.log("ViewJobsResultInst:")</script>';
        echo '<script>console.log('. json_encode( $viewJobsResultInst ) .')</script>';
        if ($viewJobsResultNum > 0) {
            ?>
            <script type='text/javascript' class='evalable'>
                var refresh = document.getElementById("refresh");
                refresh.onclick = function() {
                    location.reload();
                };
                document.getElementById("submit_jobID").setAttribute( "onClick", "javascript: showJobDetails(document.getElementById('jobID').value); return false;" );
                
                var data = '<?php echo $viewJobsResultInst ;?>'; 
                showGrid(data); 
                var pager = new Paging('results', 13); 
                pager.create(); 
                pager.showPageNavBar('pager', 'page_nav_position'); 
                pager.showPage(1);
            </script>
    <?php      
        } else {
            displayInfo("No Async SOQL jobs to display. Create one through the Define Query tab.");
        }
    }
    ?>
</div>

<div id='job_details' class='hidden'>
    <p><a href='javascript:showAllJobsTable();'>Back to Summary Grid</a></p>
    <div id='details_table'> </div>
    <div id = 'cancel_job' align='right'>
        <input type='submit' value='Cancel' id='cancel' align='right'>
    </div>    
</div>
