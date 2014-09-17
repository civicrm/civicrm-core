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

<div id="repeat-mode-dailog" style="display:none;">
    How would you like this change to affect other events in the repetition set?<br/><br/>
    <div style="display: inline-block">
        <div style="display:inline-block;width:100%;">
            <div style="width:30%;float:left;">
                <button class="repeat-mode-dailog-button only-this-event">Only this Event</button>
            </div>
            <div style="width:70%;float:left;">All other events in the series will remain same.</div>
        </div>
        <div style="display:inline-block;width:100%;">
            <div style="width:30%;float:left;">
                <button class="repeat-mode-dailog-button this-and-all-following-event">This and Following Events</button>
            </div>
            <div style="width:70%;float:left;">Change applies to this and all the following events.</div>
        </div>
        <div style="display:inline-block;width:100%;">
            <div style="width:30%;float:left;">
                <button class="repeat-mode-dailog-button all-events">All the Events</button>
            </div>
            <div style="width:70%;float:left;">Change applies to all the events in the series.</div>
        </div>
    </div>
</div>
<input type="hidden" value="" name="isRepeatingEvent" id="is-repeating-event"/>
{literal}
   <style type="text/css">
      .repeat-mode-dailog-button{
         background: #f5f5f5;
         background-image: -webkit-linear-gradient(top,#f5f5f5,#f1f1f1);
         border: 1px solid rgba(0,0,0,0.1);
         padding: 5px 8px;
         text-align: center;
         border-radius: 2px;
         cursor: pointer;  
         font-size: 11px !important;
      }
   </style>
{/literal}


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
          $("#repeat-mode-dailog").dialog({
            title: 'How does this change affect other repeating events in the set?',
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
              $("#repeat-mode-dailog").dialog('close');
              $('#mainTabContainer div:visible Form').submit();
            }else if(result.status != "" && result.status == 'Error'){
              $("#repeat-mode-dailog").html('').append("<span id='error-text'>Some error ocurred, please try after some time</span>").css('color', 'red');
            }
          }
        });
      }
    }
  });
</script>
{/literal}
{/if}
