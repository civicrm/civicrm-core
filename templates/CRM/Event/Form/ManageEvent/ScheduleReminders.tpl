{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
{* This template is used for adding/scheduling reminders.  *}
<div class="crm-block crm-form-block crm-event-manage-scheduleReminder-form-block">

{if $rows}
    <div id="reminder">
      {include file="CRM/Admin/Page/Reminders.tpl"}

      <div class="action-link">
        <a href="{crmURL q="action=update&reset=1&id=$eventId&new=1"}" id="newScheduleReminder" class="button"><span><div class="icon add-icon"></div>{ts}Add Reminder{/ts}</span></a>
      </div>
    </div>

{else}

 <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
 </div>
{if $action eq 8}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts 1=$reminderName}WARNING: You are about to delete the Reminder titled <strong>%1</strong>.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
 {* added onload javascript for source contact*}
    {literal}
    <script type="text/javascript">
    var recipient_manual = '';
    var recipient_manual_id = null;
    var toDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1&noemail=1' h=0 }{literal}"; {/literal}

    {if $recipients}
    {foreach from=$recipients key=id item=name}
         {literal} recipient_manual += '{"name":"'+{/literal}"{$name}"{literal}+'","id":"'+{/literal}"{$id}"{literal}+'"},';{/literal}
    {/foreach}
    {literal} eval( 'recipient_manual = [' + recipient_manual + ']'); {/literal}
    {/if}

    {literal}
    if ( recipient_manual_id ) {
      eval( 'recipient_manual = ' + recipient_manual_id );
    }

    cj(document).ready( function( ) {
    {/literal}
    {literal}

    eval( 'tokenClass = { tokenList: "token-input-list-facebook", token: "token-input-token-facebook", tokenDelete: "token-input-delete-token-facebook", selectedToken: "token-input-selected-token-facebook", highlightedToken: "token-input-highlighted-token-facebook", dropdown: "token-input-dropdown-facebook", dropdownItem: "token-input-dropdown-item-facebook", dropdownItem2: "token-input-dropdown-item2-facebook", selectedDropdownItem: "token-input-selected-dropdown-item-facebook", inputToken: "token-input-input-token-facebook" } ');

    var sourceDataUrl = "{/literal}{$dataUrl}{literal}";
    var tokenDataUrl  = "{/literal}{$tokenUrl}{literal}";
    var hintText = "{/literal}{ts escape='js'}Type in a partial or complete name of an existing recipient.{/ts}{literal}";
    cj( "#recipient_manual_id").tokenInput( tokenDataUrl, { prePopulate: recipient_manual, classes: tokenClass, hintText: hintText });
    cj( 'ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px' );
    cj('#source_contact_id').autocomplete( sourceDataUrl, { width : 180, selectFirst : false, hintText: hintText, matchContains: true, minChars: 1
                                }).result( function(event, data, formatted) {
                                }).bind( 'click', function( ) {  });
    });
    </script>
    {/literal}
  <table class="form-layout-compressed">
    <tr class="crm-scheduleReminder-form-block-title">
        <td class="right">{$form.title.label}</td><td colspan="3">{$form.title.html}</td>
    </tr>
     <tr>
        <td class="label">{$form.entity.label}</td>
        <td>{$form.entity.html}</td>
    </tr>
    <tr class="crm-scheduleReminder-form-block-when">
        <td class="right">{$form.start_action_offset.label}</td>
  	<td colspan="3">{include file="CRM/common/jcalendar.tpl" elementName=absolute_date} <strong>{ts}OR{/ts}</strong><br /></td>
    </tr>
    <tr id="relativeDate" class="crm-scheduleReminder-form-block-description">
        <td class="right"></td>
  	<td colspan="3">{$form.start_action_offset.html}&nbsp;&nbsp;&nbsp;{$form.start_action_unit.html}&nbsp;&nbsp;&nbsp;{$form.start_action_condition.html}&nbsp;&nbsp;&nbsp;{$form.start_action_date.html}</td>
    </tr>
    <tr id="recordActivity" class="crm-scheduleReminder-form-block-record_activity">
    	<td class="label" width="20%">{$form.record_activity.label}</td>
        <td>{$form.record_activity.html}</td>
    </tr>
    <tr id="relativeDateRepeat" class="crm-scheduleReminder-form-block-is_repeat"><td class="label" width="20%">{$form.is_repeat.label}</td>
        <td>{$form.is_repeat.html}&nbsp;&nbsp;<span class="description">{ts}Enable repetition.{/ts}</span></td>
    </tr>
    <tr id="repeatFields" class="crm-scheduleReminder-form-block-repeatFields">
      <td></td>
      <td>
        <table class="form-layout-compressed">
            <tr class="crm-scheduleReminder-form-block-repetition_frequency_interval">
                <td class="label">{$form.repetition_frequency_interval.label}&nbsp;&nbsp;&nbsp;{$form.repetition_frequency_interval.html}</td>
    		<td>{$form.repetition_frequency_unit.html}</td>
            </tr>
      	    <tr class="crm-scheduleReminder-form-block-repetition_frequency_interval">
            	<td class="label">{$form.end_frequency_interval.label}&nbsp;&nbsp;&nbsp;{$form.end_frequency_interval.html}
    		<td>{$form.end_frequency_unit.html}&nbsp;&nbsp;&nbsp;{$form.end_action.html}&nbsp;&nbsp;&nbsp;{$form.end_date.html}</td>
            </tr>
        </table>
      </td>
    </tr>
    <tr class="crm-scheduleReminder-form-block-recipient">
        <td class="right">{$form.recipient.label}</td><td colspan="3">{$form.recipient.html}&nbsp;&nbsp;{help id="recipient" file="CRM/Admin/Page/ScheduleReminders.hlp" title=$form.recipient.label}</td>
    </tr>
    <tr id="recipientList" class="crm-scheduleReminder-form-block-recipientListing">
        <td class="right">{$form.recipient_listing.label}</td><td colspan="3">{$form.recipient_listing.html}</td>
    </tr>
    <tr id="recipientManual" class="crm-scheduleReminder-form-block-recipient_manual_id">
      	<td class="label">{$form.recipient_manual_id.label}</td>
        <td>{$form.recipient_manual_id.html}{edit}<span class="description">{ts}You can manually send out the reminders to these recipients.{/ts}</span>{/edit}</td>
    </tr>
    <tr id="recipientGroup" class="crm-scheduleReminder-form-block-recipient_group_id">
        <td class="label">{$form.group_id.label}</td>
        <td>{$form.group_id.html}</td>
    </tr>

  </table>
  <fieldset id="compose_id"><legend>{ts}Email{/ts}</legend>
     <table id="email-field-table" class="form-layout-compressed">
       <tr class="crm-scheduleReminder-form-block-active">
      	   <td class="label"></td>
           <td>{$form.is_active.html}&nbsp;{$form.is_active.label}</td>
       </tr>
       <tr class="crm-scheduleReminder-form-block-template">
           <td class="label">{$form.template.label}</td>
           <td>{$form.template.html}</td>
       </tr>
       <tr class="crm-scheduleReminder-form-block-subject">
           <td class="label">{$form.subject.label}</td>
           <td>{$form.subject.html}</td>
       </tr>
  </table>
        {include file="CRM/Contact/Form/Task/EmailCommon.tpl" upload=1 noAttach=1}
  </fieldset>
{/if}

 <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}</div>
 </div>
{/if}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    = "is_repeat"
    trigger_value       = "true"
    target_element_id   = "repeatFields"
    target_element_type = "table-row"
    field_type          = "radio"
    invert              = "false"
}

{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="recipient"
    trigger_value       = 'manual'
    target_element_id   ="recipientManual"
    target_element_type ="table-row"
    field_type          ="select"
    invert              = 0
}

{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="recipient"
    trigger_value       = 'group'
    target_element_id   ="recipientGroup"
    target_element_type ="table-row"
    field_type          ="select"
    invert              = 0
}

 {literal}
 <script type='text/javascript'>
 cj(function() {
       populateRecipient();
       cj('#recipient').click( function( ) {
           populateRecipient();
       });
     });

     function populateRecipient( ) {
         var recipient = cj("#recipient option:selected").text();
    var postUrl = "{/literal}{crmURL p='civicrm/ajax/populateRecipient' h=0}{literal}";
    if(recipient == 'Participant Status' || recipient == 'Participant Role'){
       var elementID = '#recipient_listing';
          cj( elementID ).html('');
      cj.post(postUrl, {recipient: recipient},
        function ( response ) {
      response = eval( response );
      for (iota = 0; iota < response.length; iota++) {
                     cj( elementID ).get(0).add(new Option(response[iota].name, response[iota].value), document.all ? iota : null);
                 }
    }
      );
      cj("#recipientList").show();
    } else {
      cj("#recipientList").hide();
    }
     }

      cj('#absolute_date_display').click( function( ) {
   if(cj('#absolute_date_display').val()) {
       cj('#relativeDate').hide();
       cj('#relativeDateRepeat').hide();
       cj('#repeatFields').hide();
     } else {
       cj('#relativeDate').show();
       cj('#relativeDateRepeat').show();
     }
      });

 </script>
 {/literal}

