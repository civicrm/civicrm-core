{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* This template is used for adding/editing/deleting offline Event Registrations *}

{* Ajax callback for showing event fee snippet *}
{if $showFeeBlock}
  {if $priceSet}
  <div id='validate_pricefield' class='messages crm-error hiddenElement'></div>
    {literal}
    <script type="text/javascript">

    var fieldOptionsFull = [];
    {/literal}
    {foreach from=$priceSet.fields item=fldElement key=fldId}
      {if $fldElement.options}
        {foreach from=$fldElement.options item=fldOptions key=opId}
          {if $fldOptions.is_full}
            {literal}
              fieldOptionsFull[{/literal}{$fldId}{literal}] = [];
            fieldOptionsFull[{/literal}{$fldId}{literal}][{/literal}{$opId}{literal}] = 1;
          {/literal}
          {/if}
        {/foreach}
      {/if}
    {/foreach}
    {literal}

    if ( fieldOptionsFull.length > 0 ) {
      CRM.$(function($) {
        $("input,#priceset select,#priceset").each(function () {
          if ( $(this).attr('price') ) {
            switch( $(this).attr('type') ) {
              case 'checkbox':
              case 'radio':
                $(this).click( function() {
                  validatePriceField(this);
                });
                break;

              case 'select-one':
                $(this).change( function() {
                  validatePriceField(this);
                });
                break;
              case 'text':
                $(this).bind( 'keyup', function() { validatePriceField(this) });
                break;
            }
          }
        });
      });

      function validatePriceField( obj ) {
        var namePart =  cj(obj).attr('name').split('_');
        var fldVal  =  cj(obj).val();
        if ( cj(obj).attr('type') == 'checkbox') {
          var eleIdpart = namePart[1].split('[');
          var eleId = eleIdpart[0];
        }
        else {
          var eleId  = namePart[1];
        }
        var showError = false;

        switch( cj(obj).attr('type') ) {
          case 'text':
            if ( fieldOptionsFull[eleId] && fldVal ) {
              showError = true;
              cj(obj).parent( ).parent( ).children('.label').addClass('crm-error');
            }
            else {
              cj(obj).parent( ).parent( ).children('.label').removeClass('crm-error');
              cj('#validate_pricefield').hide( ).html('');
            }
            break;

          case 'checkbox':
            var checkBoxValue = eleIdpart[1].split(']');
            if ( cj(obj).prop("checked") == true &&
              fieldOptionsFull[eleId] &&
              fieldOptionsFull[eleId][checkBoxValue[0]]) {
              showError = true;
              cj(obj).parent( ).addClass('crm-error');
            }
            else {
              cj(obj).parent( ).removeClass('crm-error');
            }
            break;

          default:
            if ( fieldOptionsFull[eleId] &&
              fieldOptionsFull[eleId][fldVal]  ) {
              showError = true;
              cj(obj).parent( ).addClass('crm-error');
            }
            else {
              cj(obj).parent( ).removeClass('crm-error');
            }
          }

        if ( showError ) {
          cj('#validate_pricefield').show().html('<i class="crm-i fa-exclamation-triangle crm-i-red"></i>{/literal} {ts escape='js'}This Option is already full for this event.{/ts}{literal}');
        }
        else {
          cj('#validate_pricefield').hide( ).html('');
        }
      }
    }

  // change the status to default 'partially paid' for partial payments
  var feeAmount, userModifiedAmount;
  var partiallyPaidStatusId = {/literal}{$partiallyPaidStatusId}{literal};

  cj('#total_amount')
   .focus(
     function() {
       feeAmount = cj(this).val();
       feeAmount = parseInt(feeAmount);
     }
   )
   .change(
    function() {
      userModifiedAmount = cj(this).val();
      userModifiedAmount = parseInt(userModifiedAmount);
      if (userModifiedAmount < feeAmount) {
        cj('#status_id').val(partiallyPaidStatusId).change();
      }
    }
  );

  cj('form[name=Participant]').on("click", '.validate',
    function(e) {
      var userSubmittedStatus = cj('#status_id').val();
      var statusLabel = cj('#status_id option:selected').text();
      if (userModifiedAmount < feeAmount && userSubmittedStatus != partiallyPaidStatusId) {
        var msg = "{/literal}{ts escape="js" 1="%1"}Payment amount is less than the amount owed. Expected participant status is 'Partially paid'. Are you sure you want to set the participant status to %1? Click OK to continue, Cancel to change your entries.{/ts}{literal}";
        var result = confirm(ts(msg, {1: statusLabel}));
        if (result == false) {
          return false;
        }
      }
    }
  );
  </script>
  {/literal}
  {/if}

  {include file="CRM/Event/Form/EventFees.tpl"}

{* Main event form template *}
{else}
  {if $participantMode == 'test' }
    {assign var=registerMode value="TEST"}
    {elseif $participantMode == 'live'}
    {assign var=registerMode value="LIVE"}
  {/if}
  <div class="crm-block crm-form-block crm-participant-form-block">
    <div class="view-content">
      {if $participantMode}
        <div class="help">
          {ts 1=$displayName 2=$registerMode}Use this form to submit an event registration on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
        </div>
      {/if}
      <div id="eventFullMsg" class="messages status no-popup" style="display:none;"></div>


      {if $action eq 1 AND $paid}
        <div class="help">
          {ts}If you are accepting offline payment from this participant, check <strong>Record Payment</strong>. You will be able to fill in the payment information, and optionally send a receipt.{/ts}
        </div>
      {/if}

      {if $action eq 8} {* If action is Delete *}
        <div class="crm-participant-form-block-delete messages status no-popup">
          <div class="crm-content">
            <div class="icon inform-icon"></div> &nbsp;
            {ts}WARNING: Deleting this registration will result in the loss of related payment records (if any).{/ts} {ts}Do you want to continue?{/ts}
          </div>
          {if $additionalParticipant}
            <div class="crm-content">
              {ts 1=$additionalParticipant} There are %1 more Participant(s) registered by this participant.{/ts}
            </div>
          {/if}
        </div>
        {if $additionalParticipant}
          {$form.delete_participant.html}
        {/if}
        {else} {* If action is other than Delete *}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
          {if $single and $context neq 'standalone'}
            <tr class="crm-participant-form-block-displayName">
              <td class="label font-size12pt"><label>{ts}Participant{/ts}</label></td>
              <td class="font-size12pt view-value">{$displayName}&nbsp;</td>
            </tr>
            {else}
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
                    <a href="{$apURL}" title="{ts}view additional participant{/ts}">{$apName}</a><br />
                  {/foreach}
                </td>
              </tr>
            {/if}
            {if $registered_by_contact_id}
              <tr class="crm-participant-form-block-registered-by">
                <td class="label"><label>{ts}Registered By{/ts}</label></td>
                <td class="view-value">
                  <a href="{crmURL p='civicrm/contact/view/participant' q="reset=1&id=$participant_registered_by_id&cid=$registered_by_contact_id&action=view"}"
                     title="{ts}view primary participant{/ts}">{$registered_by_display_name}</a>
                </td>
              </tr>
            {/if}
          {/if}
          {if $participantMode}
            <tr class="crm-participant-form-block-payment_processor_id">
              <td class="label nowrap">{$form.payment_processor_id.label}</td>
              <td>{$form.payment_processor_id.html}</td>
            </tr>
          {/if}
          <tr class="crm-participant-form-block-event_id">
            <td class="label">{$form.event_id.label}</td>
            <td class="view-value">
              {$form.event_id.html}
              {if $is_test}
                {ts}(test){/ts}
              {/if}
            </td>
          </tr>

        {* CRM-7362 --add campaign *}
        {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignTrClass="crm-participant-form-block-campaign_id"}

          <tr class="crm-participant-form-block-role_id">
            <td class="label">{$form.role_id.label}</td>
            <td>{$form.role_id.html}</td>
          </tr>
          <tr class="crm-participant-form-block-register_date">
            <td class="label">{$form.register_date.label}</td>
            <td>
              {if $hideCalendar neq true}
                    {include file="CRM/common/jcalendar.tpl" elementName=register_date}
                  {else}
                    {$form.register_date.value|crmDate}
                  {/if}
            </td>
          </tr>
          <tr class="crm-participant-form-block-status_id">
            <td class="label">{$form.status_id.label}</td>
            <td>{$form.status_id.html}{if $event_is_test} {ts}(test){/ts}{/if}
              <div id="notify">{$form.is_notify.html}{$form.is_notify.label}</div>
            </td>
          </tr>
          <tr class="crm-participant-form-block-source">
            <td class="label">{$form.source.label}</td><td>{$form.source.html|crmAddClass:huge}<br />
            <span class="description">{ts}Source for this registration (if applicable).{/ts}</span></td>
          </tr>
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
          <div id="customData" class="crm-customData-block"></div>  {* Participant Custom data *}
          <div id="customData{$eventNameCustomDataTypeID}" class="crm-customData-block"></div> {* Event Custom Data *}
          <div id="customData{$roleCustomDataTypeID}" class="crm-customData-block"></div> {* Role Custom Data *}
          <div id="customData{$eventTypeCustomDataTypeID}" class="crm-customData-block"></div> {* Role Custom Data *}
        </div>
      {/if}

      {if $accessContribution and $action eq 2 and $rows.0.contribution_id}
      {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
      {/if}

      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div>
  </div>
  {* JS block for ADD or UPDATE actions only *}
  {if $action eq 1 or $action eq 2}
    {if $participantId and $hasPayment}
      {include file="CRM/Contribute/Page/PaymentInfo.tpl" show='event-payment'}
    {/if}

    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}

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
          CRM.buildCustomData('Participant', eventId, {/literal}{$eventNameCustomDataTypeID}{literal});
          CRM.buildCustomData('Participant', info.event_type_id, {/literal}{$eventTypeCustomDataTypeID}{literal});

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
          CRM.buildCustomData('Participant', roleId.join(), {/literal}{$roleCustomDataTypeID}{literal});
        }

        //build fee block
        function buildFeeBlock(discountId)  {
          var dataUrl = {/literal}"{crmURL p=$urlPath h=0 q="snippet=4&qfKey=$qfKey"}";

          {if $urlPathVar}
          dataUrl += '&' + '{$urlPathVar}';
          {/if}

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

          var participantId  = "{/literal}{$participantId}{literal}";

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


<script type="text/javascript">
  {literal}

  sendNotification();
  function sendNotification() {
    var notificationStatusIds = {/literal}"{$notificationStatusIds}"{literal};
    notificationStatusIds = notificationStatusIds.split(',');
    if (cj.inArray(cj('select#status_id option:selected').val(), notificationStatusIds) > -1) {
      cj("#notify").show();
      cj("#is_notify").prop('checked', true);
    }
    else {
      cj("#notify").hide();
      cj("#is_notify").prop('checked', false);
    }
  }

  {/literal}
</script>

{/if} {* end of main event block*}
