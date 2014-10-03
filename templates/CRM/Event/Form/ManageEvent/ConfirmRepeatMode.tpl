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
    {ts}How would you like this change to affect other events in the repetition set?{/ts}<br/><br/>
    <div class="show-block">
        <div class="recurring-dialog-inner-wrapper">
            <div class="recurring-dialog-inner-left">
                <button class="recurring-dialog-button only-this-event">{ts}Only this Event{/ts}</button>
            </div>
          <div class="recurring-dialog-inner-right">{ts}All other events in the series will remain same.{/ts}</div>
        </div>
        <div class="recurring-dialog-inner-wrapper">
            <div class="recurring-dialog-inner-left">
                <button class="recurring-dialog-button this-and-all-following-event">{ts}This and Following Events{/ts}</button>
            </div>
            <div class="recurring-dialog-inner-right">{ts}Change applies to this and all the following events.{/ts}</div>
        </div>
        <div class="recurring-dialog-inner-wrapper">
            <div class="recurring-dialog-inner-left">
                <button class="recurring-dialog-button all-events">{ts}All the Events{/ts}</button>
            </div>
            <div class="recurring-dialog-inner-right">{ts}Change applies to all the events in the series.{/ts}</div>
        </div>
    </div>
</div>
<input type="hidden" value="" name="isRepeatingEvent" id="is-repeating-event"/>
{if $isRepeat eq 'repeat'}
{literal}
<script>
  CRM.$(function($) {
    //Tab and table mapper
    var mapper = {'CRM_Event_Form_ManageEvent_EventInfo': '',
                'CRM_Event_Form_ManageEvent_Location': '',
                'CRM_Event_Form_ManageEvent_Fee': '',
                'CRM_Event_Form_ManageEvent_Registration': '',
                'CRM_Friend_Form_Event': 'civicrm_tell_friend',
                'CRM_PCP_Form_Event': 'civicrm_pcp_block'
                };
    
    var form = '';
    $('#crm-main-content-wrapper').on('click', 'div.crm-submit-buttons span.crm-button input[value="Save"], div.crm-submit-buttons span.crm-button input[value="Save and Done"]', function() {
        form = $(this).parents('form:first').attr('class');
        if( form != "" && mapper.hasOwnProperty(form) ){
          $("#recurring-dialog").dialog({
            title: ts('How does this change affect other repeating events in the set?'),
            modal: true,
            width: '650',
            buttons: {
              Cancel: function() { //cancel
                $( this ).dialog( "close" );
              }
            }
          }).dialog('open');
          return false;
        }
    }); 

    $(".only-this-event").click(function(){
      updateMode(1);
    });
  
    cj(".this-and-all-following-event").click(function(){
      updateMode(2);
    });
  
    cj(".all-events").click(function(){
      updateMode(3);
    });
    
    function updateMode(mode) {
      var eventID = {/literal}{$id}{literal};
      if (eventID != "" && mode && form != "") {
        var ajaxurl = CRM.url("civicrm/ajax/recurringentity/update-mode");
        var data    = {mode: mode, entityId: eventID, entityTable:'civicrm_event', linkedEntityTable:mapper[form]};
        $.ajax({
          dataType: "json",
          data: data,
          url:  ajaxurl,
          success: function (result) {
            if(result.status != "" && result.status == 'Done'){
              $("#recurring-dialog").dialog('close');
              $('#mainTabContainer div:visible Form').submit();
            }else if(result.status != "" && result.status == 'Error'){
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
{/if}
