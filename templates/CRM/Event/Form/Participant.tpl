{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This template is used for adding/editing offline Event Registrations *}

{* Ajax callback for showing event fee snippet  - to be moved to separate form *}
{if $showFeeBlock}
  {include file="CRM/Event/Form/FeeBlock.tpl"}
{* Main event form template *}
{else}
  <div class="crm-block crm-form-block crm-participant-form-block">
    {if $action EQ 1 AND ($context EQ 'participant' OR $context EQ 'standalone') AND $newCredit AND $participantMode EQ null}
      <div class="action-link css_right crm-link-credit-card-mode">
        {if $contactId}
          {capture assign=ccModeLink}{crmURL p='civicrm/contact/view/participant' q="reset=1&action=add&cid=`$contactId`&context=`$context`&mode=live"}{/capture}
        {else}
          {capture assign=ccModeLink}{crmURL p='civicrm/contact/view/participant' q="reset=1&action=add&context=standalone&mode=live"}{/capture}
        {/if}
        <a class="open-inline-noreturn action-item crm-hover-button" href="{$ccModeLink}"><i class="crm-i fa-credit-card" aria-hidden="true"></i> {ts}submit credit card event registration{/ts}</a>
      </div>
    {/if}
    <div class="view-content">
      {if !empty($participantMode)}
        <div class="help">
          {ts 1=$displayName 2=$participantMode|crmUpper}Use this form to submit an event registration on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
        </div>
      {/if}
      <div id="eventFullMsg" class="messages status no-popup" style="display:none;"></div>

      {if 1} {* If action is other than Delete *}
        <table class="form-layout-compressed">
          {if $context EQ 'standalone' OR $context EQ 'participant' OR $action EQ 2}
            <tr class="crm-participant-form-contact-id">
              <td class="label">{$form.contact_id.label}</td>
              <td>{$form.contact_id.html}</td>
            </tr>
          {/if}
          {if $action EQ 2}
            {if $additionalParticipants} {* Display others registered by this participant *}
              <tr class="crm-participant-form-block-additionalParticipants">
                <td class="label"><label>{ts}Also Registered by this Participant{/ts}</label></td>
                <td>
                  {foreach from=$additionalParticipants key=apName item=apURL}
                    <a href="{$apURL}" title="{ts escape='htmlattribute'}view additional participant{/ts}">{$apName}</a><br />
                  {/foreach}
                </td>
              </tr>
            {/if}
            {if $registered_by_contact_id}
              <tr class="crm-participant-form-block-registered-by">
                <td class="label"><label>{ts}Registered By{/ts}</label></td>
                <td class="view-value">
                  <a href="{crmURL p='civicrm/contact/view/participant' q="reset=1&id=$participant_registered_by_id&cid=$registered_by_contact_id&action=view"}"
                     title="{ts escape='htmlattribute'}view primary participant{/ts}">{$registered_by_display_name}</a>
                </td>
              </tr>
            {/if}
          {/if}
          <tr class="crm-participant-form-block-event_id">
            <td class="label">{$form.event_id.label}</td>
            <td class="view-value">{$form.event_id.html}</td>
          </tr>

        {* CRM-7362 --add campaign *}
        {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignTrClass="crm-participant-form-block-campaign_id"}

          <tr class="crm-participant-form-block-role_id">
            <td class="label">{$form.role_id.label}</td>
            <td>{$form.role_id.html}</td>
          </tr>
          <tr class="crm-participant-form-block-register_date">
            <td class="label">{$form.register_date.label}</td>
            <td>{$form.register_date.html}</td>
          </tr>
          <tr class="crm-participant-form-block-status_id">
            <td class="label">{$form.status_id.label}</td>
            <td>{$form.status_id.html}{if $event_is_test} {ts}(test){/ts}{/if}
              <div id="notify">{$form.is_notify.html}{$form.is_notify.label}</div>
            </td>
          </tr>
          <tr class="crm-participant-form-block-source">
            <td class="label">{$form.source.label}</td><td>{$form.source.html|crmAddClass:huge}</td>
          </tr>
          {if $participantMode}
            <tr class="crm-participant-form-block-payment_processor_id">
              <td class="label nowrap">{$form.payment_processor_id.label}</td>
              <td>{$form.payment_processor_id.html}</td>
            </tr>
          {/if}
        </table>
       {if $participantId and $hasPayment}
        <table class='form-layout'>
          <tr>
            <td class='label'>{ts}Fees{/ts}</td>
            {* this is where the payment info is shown using CRM/Contribute/Page/PaymentInfo.tpl tpl*}
            <td id='payment-info'></td>
          </tr>
         </table>
        {/if}
      {* Fee block (EventFees.tpl) is injected here when an event is selected. *}
        <div class="crm-event-form-fee-block"></div>
        <fieldset>
          <table class="form-layout">
            <tr class="crm-participant-form-block-note">
              <td class="label">{$form.note.label}</td><td>{$form.note.html}</td>
            </tr>
          </table>
        </fieldset>

        <div class="crm-participant-form-block-customData">
          <div id="customData_Participant" class="crm-customData-block"></div>  {* Participant Custom data *}
          <div id="customData_Participant{$eventNameCustomDataTypeID}" class="crm-customData-block"></div> {* Event Custom Data *}
          <div id="customData_Participant{$roleCustomDataTypeID}" class="crm-customData-block"></div> {* Role Custom Data *}
          <div id="customData_Participant{$eventTypeCustomDataTypeID}" class="crm-customData-block"></div> {* Role Custom Data *}
        </div>
      {/if}

      {if $action eq 2 and $accessContribution and array_key_exists(0, $rows) &&  $rows.0.contribution_id}
      {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
      {/if}

      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div>
  </div>
  {* JS block for ADD or UPDATE actions only *}
  {if $action eq 1 or $action eq 2}
    {if $participantId and $hasPayment}
      {include file="CRM/Contribute/Page/PaymentInfo.tpl" show='payments'}
    {/if}

    {*include custom data js file*}
    {include file="CRM/common/customData.tpl" groupID=''}

    <script type="text/javascript">
      {literal}
      CRM.$(function($) {

        var $form = $('form.{/literal}{$form.formClass}{literal}');

        // Handle event selection
        $('#event_id', $form).change(function() {
          var eventId = $(this).val();
          if (!eventId) {
            return;
          }
          var info = $(this).select2('data').extra;

          // Set role from default
          $('select[name^=role_id]', $form).select2('val', [info.default_role_id], true);

          // Set campaign default
          $('#campaign_id', $form).select2('val', info.campaign_id);

          // Event and event-type custom data
          CRM.buildCustomData('Participant', eventId, {/literal}{$eventNameCustomDataTypeID}{literal}, null, null, null, true);
          CRM.buildCustomData('Participant', info.event_type_id, {/literal}{$eventTypeCustomDataTypeID}{literal}, null, null, null, true);

          buildFeeBlock();
        });

        // Handle participant role selection
        $('select[name^=role_id]', $form).change(buildRoleCustomData);
        if ($('select[name^=role_id]', $form).val()) {
          buildRoleCustomData();
        }

        buildFeeBlock();

        //build discount block
        if ($('#discount_id', $form).val()) {
          buildFeeBlock($('#discount_id', $form).val());
        }
        $($form).on('change', '#discount_id', function() {
          buildFeeBlock($(this).val());
        });

        function buildRoleCustomData() {
          var roleId = $('select[name^=role_id]', $form).val() || [];
          // If -1 is passed this will avoid https://lab.civicrm.org/dev/core/-/issues/5253
          // as it is not a valid role ID but it is not 'empty'
          CRM.buildCustomData('Participant', roleId.join() || -1, {/literal}{$roleCustomDataTypeID}{literal});
        }

        //build fee block
        function buildFeeBlock(discountId)  {
          var dataUrl = {/literal}"{crmURL p=$urlPath h=0 q="snippet=4&qfKey=$qfKey"}";

          {if $urlPathVar}
          dataUrl += '&' + '{$urlPathVar}';
          {/if}
          dataUrl += '&' + 'is_backoffice=1';

          {literal}
          var eventId = $('[name=event_id], #event_id', $form).val();

          if (eventId) {
            dataUrl += '&eventId=' + eventId;
          }
          else {
            $('#eventFullMsg', $form).hide( );
            $('.crm-event-form-fee-block', $form).html('');
            return;
          }

          var participantId  = {/literal}{$participantId|@json_encode}{literal};

          if (participantId) {
            dataUrl += '&participantId=' + participantId;
          }

          if (discountId) {
            dataUrl += '&discountId=' + discountId;
          }

          $.ajax({
            url: dataUrl,
            success: function ( html ) {
              $(".crm-event-form-fee-block", $form).html( html ).trigger('crmLoad');
              //show event real full as well as waiting list message.
              if ( $("#hidden_eventFullMsg", $form).val( ) ) {
                $( "#eventFullMsg", $form).show( ).html( $("#hidden_eventFullMsg", $form).val( ) );
              }
              else {
                $( "#eventFullMsg", $form ).hide( );
              }
            }
          });
        }

        {/literal}
        CRM.buildCustomData( '{$customDataType}', null, null );
        {if $eventID}
          CRM.buildCustomData( '{$customDataType}', {$eventID}, {$eventNameCustomDataTypeID} );
        {/if}
        {if $eventTypeID}
          CRM.buildCustomData( '{$customDataType}', {$eventTypeID}, {$eventTypeCustomDataTypeID} );
        {/if}
        {literal}

      });
    </script>
    {/literal}

  {/if}

  {if $action NEQ 8}
  <script type="text/javascript">
    {literal}

    sendNotification();
    function sendNotification() {
      var notificationStatusIds = {/literal}"{$notificationStatusIds}"{literal};
      notificationStatusIds = notificationStatusIds.split(',');
      if (cj.inArray(cj('.crm-participant-form-block-status_id select#status_id option:selected').val(), notificationStatusIds) > -1) {
        cj("#notify").show();
      }
      else {
        cj("#notify").hide();
        cj("#is_notify").prop('checked', false);
      }
    }

    {/literal}
  </script>
  {/if}

{/if} {* end of main event block*}
