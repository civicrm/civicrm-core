{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
  <h3>Importing records...</h3><br />
       <div id="status" style="margin-left:6px;"></div>
  <div class="progressBar" id="importProgressBar" style="margin-left:6px;display:none;"></div>
  <div id="intermediate"></div>
  <div id="error_status"></div>
</div>
