{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

<div id="recurring-dialog" class="hide-block">
    {ts}How would you like this change to affect other {$entityType}s in the repetition set?{/ts}<br/><br/>
    <div class="show-block">
        <div class="recurring-dialog-inner-wrapper">
            <div class="recurring-dialog-inner-left">
                <button class="recurring-dialog-button only-this-event">{ts}Only this {$entityType}{/ts}</button>
            </div>
          <div class="recurring-dialog-inner-right">{ts}All other {$entityType}s in the series will remain same.{/ts}</div>
        </div>
        <div class="recurring-dialog-inner-wrapper">
            <div class="recurring-dialog-inner-left">
                <button class="recurring-dialog-button this-and-all-following-event">{ts}This and Following {$entityType}s{/ts}</button>
            </div>
            <div class="recurring-dialog-inner-right">{ts}Change applies to this and all the following {$entityType}s.{/ts}</div>
        </div>
        <div class="recurring-dialog-inner-wrapper">
            <div class="recurring-dialog-inner-left">
                <button class="recurring-dialog-button all-events">{ts}All the {$entityType}s{/ts}</button>
            </div>
            <div class="recurring-dialog-inner-right">{ts}Change applies to all the {$entityType}s in the series.{/ts}</div>
        </div>
    </div>
</div>
<input type="hidden" value="" name="isRepeatingEvent" id="is-repeating-event"/>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {  
      var form = '';
      form = $(this).parents('form:first').attr('class');
      
      $(".only-this-event").click(function() {
        updateMode(1);
      });

      $(".this-and-all-following-event").click(function() {
        updateMode(2);
      });

      $(".all-events").click(function() {
        updateMode(3);
      });
      
      function updateMode(mode) {
        var entityID = parseInt('{/literal}{$entityID}{literal}');
        var entityTable = '{/literal}{$entityTable}{literal}';
        var testmapper = '{/literal}$mapper['{literal}+form+{/literal}']{literal}';
        alert(testmapper);
        if (entityID != "" && mode && form != "" && entityTable !="") {
          var ajaxurl = CRM.url("civicrm/ajax/recurringentity/update-mode");
          var data    = {mode: mode, entityId: entityID, entityTable: entityTable, linkedEntityTable:'{/literal}$mapper['{literal}+form+{/literal}']{literal}'};
          $.ajax({
            dataType: "json",
            data: data,
            url:  ajaxurl,
            success: function (result) {
              if (result.status != "" && result.status == 'Done') {
                $("#recurring-dialog").dialog('close');
                $('#mainTabContainer div:visible Form').submit();
              } else if (result.status != "" && result.status == 'Error') {
                var errorBox = confirm(ts("Mode could not be updated, save only this event?"));
                if (errorBox == true) {
                  $("#recurring-dialog").dialog('close');
                  $('#mainTabContainer div:visible Form').submit();
                } else {
                  $("#recurring-dialog").dialog('close');
                  return false;
                }
              }
            }
          });
        }
      }  
    });
  </script>
  {/literal}