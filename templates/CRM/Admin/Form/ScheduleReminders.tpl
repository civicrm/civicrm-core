{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
<div class="crm-block crm-form-block crm-scheduleReminder-form-block">
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

{if $action eq 8}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts 1=$reminderName}WARNING: You are about to delete the Reminder titled <strong>%1</strong>.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
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
    <tr id="recordActivity" class="crm-scheduleReminder-form-block-record_activity"><td class="label" width="20%">{$form.record_activity.label}</td>
        <td>{$form.record_activity.html}</td>
    </tr>
    <tr id="relativeDateRepeat" class="crm-scheduleReminder-form-block-is_repeat"><td class="label" width="20%">{$form.is_repeat.label}</td>
        <td>{$form.is_repeat.html}&nbsp;&nbsp;<span class="description">{ts}Enable repetition.{/ts}</span></td>
    </tr>
    <tr id="repeatFields" class="crm-scheduleReminder-form-block-repeatFields"><td></td><td>
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
    <tr>
      <td class="label" width="20%">{$form.from_name.label}</td>
      <td>{$form.from_name.html}&nbsp;&nbsp;{help id="id-from_name_email"}</td>
    </tr>
    <tr>
      <td class="label" width="20%">{$form.from_email.label}</td>
      <td>{$form.from_email.html}&nbsp;&nbsp;</td>
    </tr>
    <tr class="crm-scheduleReminder-form-block-recipient">
      <td id="recipientLabel" class="right">{$form.recipient.label}</td><td colspan="3">{$form.limit_to.html}&nbsp;&nbsp;{$form.recipient.html}&nbsp;&nbsp;{help id="recipient" title=$form.recipient.label}</td>
    </tr>
    <tr id="recipientList" class="crm-scheduleReminder-form-block-recipientListing">
        <td class="right">{$form.recipient_listing.label}</td><td colspan="3">{$form.recipient_listing.html}</td>
    </tr>
    <tr id="recipientManual" class="crm-scheduleReminder-form-block-recipient_manual_id">
        <td class="label">{$form.recipient_manual_id.label}</td>
        <td>{$form.recipient_manual_id.html}{edit}<div class="description">{ts}You can manually send out the reminders to these recipients.{/ts}</div>{/edit}</td>
    </tr>

    <tr id="recipientGroup" class="crm-scheduleReminder-form-block-recipient_group_id">
        <td class="label">{$form.group_id.label}</td>
        <td>{$form.group_id.html}</td>
    </tr>
    <tr id="msgMode" class="crm-scheduleReminder-form-block-mode">
      <td class="label">{$form.mode.label}</td>
      <td>{$form.mode.html}</td>
    </tr>
    <tr class="crm-scheduleReminder-form-block-active">
      <td class="label"></td>
      <td>{$form.is_active.html}&nbsp;{$form.is_active.label}</td>
    </tr>
  </table>
  <fieldset id="email" class="crm-collapsible" style="display: block;">
    <legend class="collapsible-title">{ts}Email Screen{/ts}</legend>
      <div>
       <table id="email-field-table" class="form-layout-compressed">
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
    </div>
    </fieldset>
    {if $sms}
      <fieldset id="sms" class="crm-collapsible"><legend class="collapsible-title">{ts}SMS Screen{/ts}</legend>
        <div>
        <table id="sms-field-table" class="form-layout-compressed">
          <tr id="smsProvider" class="crm-scheduleReminder-form-block-sms_provider_id">
            <td class="label">{$form.sms_provider_id.label}</td>
            <td>{$form.sms_provider_id.html}</td>
          </tr>
          <tr class="crm-scheduleReminder-form-block-sms-template">
            <td class="label">{$form.SMStemplate.label}</td>
            <td>{$form.SMStemplate.html}</td>
          </tr>
        </table>
        {include file="CRM/Contact/Form/Task/SMSCommon.tpl" upload=1 noAttach=1}
    <div>
  </fieldset>
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
    CRM.$(function($) {
      var $form = $('form.{/literal}{$form.formClass}{literal}'),
        recipientMapping = eval({/literal}{$recipientMapping}{literal});

      $('#absolute_date_display', $form).change(function() {
        if($(this).val()) {
          $('#relativeDate, #relativeDateRepeat, #repeatFields', $form).hide();
        } else {
          $('#relativeDate, #relativeDateRepeat', $form).show();
        }
      });
      
      if ($('#absolute_date_display', $form).val()) {
        $('#relativeDate, #relativeDateRepeat, #repeatFields', $form).hide();
      }

      $('#entity_0', $form).change(buildSelects).change(showHideLimitTo);

      loadMsgBox();
      $('#mode', $form).change(loadMsgBox);

      showHideLimitTo();

      $('#recipient', $form).change(populateRecipient);

      var entity = $('#entity_0', $form).val();
      if (!(entity === '2' || entity === '3') || $('#recipient', $form).val() !== '1') {
        $('#recipientList', $form).hide();
      }

      function buildSelects() {
        var mappingID = $('#entity_0', $form).val();

        $('#is_recipient_listing').val('');
        $.getJSON(CRM.url('civicrm/ajax/mapping'), {mappingID: mappingID},
          function (result) {
            CRM.utils.setOptions($('#start_action_date', $form), result.sel4);
            CRM.utils.setOptions($('#end_date', $form), result.sel4);
            CRM.utils.setOptions($('#recipient', $form), result.sel5);
            recipientMapping = result.recipientMapping;
            populateRecipient();
          }
        );
      }
      function populateRecipient() {
        var recipient = $("#recipient", $form).val();

        if (recipientMapping[recipient] == 'Participant Status' || recipientMapping[recipient] == 'participant_role') {
          CRM.api3('participant', 'getoptions', {field: recipientMapping[recipient] == 'participant_role' ? 'role_id' : 'status_id', sequential: 1})
            .done(function(result) {
              CRM.utils.setOptions($('#recipient_listing', $form), result.values);
            });
          $("#recipientList", $form).show();
          $('#is_recipient_listing', $form).val(1);
        } else {
          $("#recipientList", $form).hide();
          $('#is_recipient_listing', $form).val('');
        }
      }
      // CRM-14070 Hide limit-to when entity is activity
      function showHideLimitTo() {
        $('#limit_to', $form).toggle(!($('#entity_0', $form).val() == '1'));
      }
    });

  function loadMsgBox() {
    if (cj('#mode').val() == 'Email' || cj('#mode').val() == 0){
      cj('#sms').hide();
      cj('#email').show();
    }
    else if (cj('#mode').val() == 'SMS'){
      cj('#email').hide();
      cj('#sms').show();
      showSaveUpdateChkBox('SMS');
    }
    else if (cj('#mode').val() == 'User_Preference'){
        cj('#email').show();
        cj('#sms').show();
      showSaveUpdateChkBox('SMS');
      }
  }

 </script>
 {/literal}

{/if}

 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
