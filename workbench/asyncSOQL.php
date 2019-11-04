<!-- Main AsyncSOQL page with two tabs: one to submit jobs and the other to view submitted jobs -->

<?php
$MIGRATION_MESSAGE = "Visual Studio Code now includes <a href=\"https://developer.salesforce.com/tools/vscode/en/soql/writing\">SOQL code completion</a>. <a href=\"https://developer.salesforce.com/tools/vscode/en/getting-started/install\">Try it today!</a>";

require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';

set_exception_handler('handleAllExceptionsNoHeaders');
?>

<head>
  <script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/jquery.js'); ?>"></script>
  <link
    rel="stylesheet" type="text/css"
    href="<?php echo getPathToStaticResource('/style/jquery-ui.css'); ?>" />
  <script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/jquery-ui.js'); ?>"></script>
  <script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/wz_tooltip.js'); ?>"></script>
  <script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/simpletreemenu.js'); ?>">
      /***********************************************
      * Dynamic Countdown script- © Dynamic Drive (http://www.dynamicdrive.com)
      * This notice MUST stay intact for legal use
      * Visit http://www.dynamicdrive.com/ for this script and 100s more.
      ***********************************************/
  </script>

  <script>
    $(function() {

      $("#tabs").tabs({
          ajaxOptions: {
              error: function(xhr, status, index, anchor) {
                  $(anchor.hash).html();
              },
              beforeSend: function() {
                  $('#loader').show();
                  index = getSelectedTabIndex();                 
                  if (index == 0){
                    window.location.hash = "defineQuery";
                  } else {
                    window.location.hash = "viewStatus";
                  }
              },
              complete: function() {
                  $("#loader").hide();

              }
          }
      });
    });

    function getSelectedTabIndex() { 
        return $("#tabs").tabs('option', 'selected');
    }

  </script>
</head>

<body>
  <div id="tabs">
      <ul>
          <li><a href="asyncSOQLDefineQuery.php" title="defineQuery">Define Query</a></li>
          <li><a href="asyncSOQLViewStatus.php" title="viewStatus">View Status</a></li>
      </ul>
  </div>

  <div id="loader" style="display:none">Loading...<img src='static/images/wait16trans.gif'></div>

</body>
<?php
include_once 'footer.php';
?>
