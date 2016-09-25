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
  <td colspan="2"><label>{ts}Event Dates{/ts}</label></td>
</tr>
<tr>
{include file="CRM/Core/DateRange.tpl" fieldName="event" from='_start_date_low' to='_end_date_high'}
</tr>
<tr>
  <td><label>{ts}Registration Date{/ts}</label></td>
</tr>
<tr>
{include file="CRM/Core/DateRange.tpl" fieldName="participant" from='_register_date_low' to='_register_date_high'}
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
  {$form.participant_test.label} {help id="is-test" file="CRM/Contact/Form/Search/Advanced"}
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

{* campaign in contribution search *}
{include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignContext="componentSearch"
campaignTrClass='' campaignTdClass='crm-event-form-block-participant_campaign_id'}

{if $participantGroupTree }
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
