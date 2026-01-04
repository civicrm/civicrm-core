{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<tr>
  <td class="crm-event-form-block-event_id">
      {$form.event_id.label}  <br />{$form.event_id.html|crmAddClass:huge}
      <div class="crm-event-form-block-event_include_repeating_events">
        {$form.event_include_repeating_events.label}&nbsp;&nbsp;{$form.event_include_repeating_events.html}
      </div>
  </td>
  <td class="crm-event-form-block-event_type_id"> {$form.event_type_id.label}<br />{$form.event_type_id.html} </td>
</tr>
<tr>
    {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="event" to='' from='' colspan="2" class='' hideRelativeLabel=0}</tr>
<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="participant_register_date" to='' from='' colspan="2" class='' hideRelativeLabel=0}
</tr>
<tr>
  <td class="crm-event-form-block-participant_status"><label>{$form.participant_status_id.label}</label>
    <br />
    {$form.participant_status_id.html}
  </td>
  <td class="crm-event-form-block-participant_role_id"><label>{$form.participant_role_id.label}</label>
    <br />
    {$form.participant_role_id.html}
  </td>
</tr>
<tr>
  <td class="crm-event-form-block-participant_test">
  {$form.participant_test.label} {help id="is_test" file="CRM/Contact/Form/Search/Advanced" title=$form.participant_test.textLabel}
    &nbsp; {$form.participant_test.html}
  </td>
  <td class="crm-event-form-block-participant_pay_later">
  {$form.participant_is_pay_later.label} {$form.participant_is_pay_later.html}
  </td>
</tr>
<tr>
  <td class="crm-event-form-block-participant_fee_id">
    {$form.participant_fee_id.label}<br />{$form.participant_fee_id.html}
  </td>
  <td class="crm-event-form-block-participant_fee_amount">
    <label>{ts}Fee Amount{/ts}</label><br />
    {$form.participant_fee_amount_low.label} &nbsp; {$form.participant_fee_amount_low.html} &nbsp;&nbsp;
    {$form.participant_fee_amount_high.label} &nbsp; {$form.participant_fee_amount_high.html}
  </td>
</tr>
<tr>
  <td colspan="2"><label>{$form.participant_id.label}</label> {$form.participant_id.html}</td>
</tr>

{* campaign in contribution search *}
{include file="CRM/Campaign/Form/addCampaignToSearch.tpl"
campaignTrClass='' campaignTdClass='crm-event-form-block-participant_campaign_id'}

{if $participantGroupTree}
<tr>
  <td colspan="4">
  {include file="CRM/Custom/Form/Search.tpl" groupTree=$participantGroupTree showHideLinks=false}
  </td>
</tr>
{/if}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  var recurringLabel = $('label[for=event_include_repeating_events]').html();
  // Conditional rule for recurring checkbox
  function toggleRecurrigCheckbox() {
    var isRepeating = false;
    if ($(this).val()) {
      // Workaround: In some cases this code gets called before the select2 initialization.
      if (!$(this).data('select2')) {
        $(this).crmEntityRef();
      }
      // allow repeat checkbox to be shown for first event selected
      if (!$.isEmptyObject($(this).select2('data')[0])) {
        isRepeating = $(this).select2('data')[0].extra.is_recur;
      }
    }
    if (isRepeating) {
      $('.crm-event-form-block-event_include_repeating_events').show();
      $('label[for=event_include_repeating_events]').html(recurringLabel.replace('%1', $(this).select2('data')[0].label));
    } else {
      $('.crm-event-form-block-event_include_repeating_events').hide().find('input').prop('checked', false);
    }
  }
  $('#event_id').each(toggleRecurrigCheckbox).change(toggleRecurrigCheckbox);
});
</script>
{/literal}
