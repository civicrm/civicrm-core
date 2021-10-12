{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
<script type="text/javascript">
CRM.$(function($) {
  var loop;
  $("form#Preview").on('submit', function (e) {
    if (!confirm("{/literal}{ts escape='js'}Backing up your database before importing is recommended, as there is no Undo for this. Are you sure you want to import now?{/ts}{literal}")) {
      e.preventDefault();
    }
    else {
      showProgressBar();
    }
  });
  function setIntermediate() {
    var dataUrl = {/literal}{$statusUrl|@json_encode}{literal};
    $.getJSON(dataUrl, function(response) {
      var dataStr = response.toString();
      var result  = dataStr.split(",");
      $("#intermediate").html(result[1]);
      $("#importProgressBar .ui-progressbar-value").show();
      if (result[0] < 100) {
        $("#importProgressBar .ui-progressbar-value").animate({width: result[0] + "%"}, 500);
        $("#status").text(result[0] + "% Completed");
      }
      else {
        window.clearInterval(loop);
      }
    });
  }

  function showProgressBar() {
    $("#id-processing").show( ).dialog({
      modal         : true,
      width         : 450,
      height        : 200,
      resizable     : false,
      draggable     : true,
      closeOnEscape : false,
      open          : function () {
        $("#id-processing").dialog().parents(".ui-dialog").find(".ui-dialog-titlebar").remove();
      }
    });
    $("#importProgressBar" ).progressbar({value:0});
    $("#importProgressBar").show( );
    loop = window.setInterval(setIntermediate, 5000)
  }
});
</script>
{/literal}

{* Import Progress Bar and Info *}
<div id="id-processing" class="hiddenElement">
  <h3>{ts}Importing records...{/ts}</h3><br />
       <div id="status" style="margin-left:6px;"></div>
  <div class="progressBar" id="importProgressBar" style="margin-left:6px;display:none;"></div>
  <div id="intermediate"></div>
  <div id="error_status"></div>
</div>
