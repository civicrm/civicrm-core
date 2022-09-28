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
{include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="pledge_payment_scheduled_date" to='' from='' colspan="2" class='' hideRelativeLabel=0}
</tr>
<tr>
  <td colspan="2">
    <label>{ts}Pledge Payment Status{/ts}</label>
      <br />{$form.pledge_payment_status_id.html}
  </td>
</tr>
<tr>
  <td>
    <label>{ts}Pledge Amounts{/ts}</label>
      <br />
    {$form.pledge_amount_low.label} {$form.pledge_amount_low.html} &nbsp;&nbsp; {$form.pledge_amount_high.label} {$form.pledge_amount_high.html}
  </td>
  <td>
    <label>{ts}Pledge Status{/ts}</label>
      <br />{$form.pledge_status_id.html}
  </td>
</tr>
<tr>
{include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="pledge_create_date" to='' from='' colspan="2" class='' hideRelativeLabel=0}
</tr>
<tr>
{include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="pledge_start_date" to='' from='' colspan="2" class='' hideRelativeLabel=0}
</tr>
<tr>
{include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="pledge_end_date" to='' from='' colspan="2" class='' hideRelativeLabel=0}
</tr>
<tr>
  <td>
    <label>{ts}Financial Type{/ts}</label>
    <br />{$form.pledge_financial_type_id.html}
  </td>
  <td>
    <label>{ts}Contribution Page{/ts}</label>
    <br />{$form.pledge_contribution_page_id.html}
  </td>
</tr>
<tr>
  <td>
  <br />
  {$form.pledge_test.label} {help id="is-test" file="CRM/Contact/Form/Search/Advanced"} &nbsp; {$form.pledge_test.html}
  </td>
</tr>
<tr>
  <td colspan="2">
  {$form.pledge_frequency_unit.label}
    <br /> {$form.pledge_frequency_interval.label} &nbsp; {$form.pledge_frequency_interval.html} &nbsp;
  {$form.pledge_frequency_unit.html}
  </td>
</tr>
<tr>
  <td colspan="2">
    <label>{ts}Number of Installments{/ts}</label>
    <br />
    {$form.pledge_installments_low.label} {$form.pledge_installments_low.html}
    &nbsp;&nbsp; {$form.pledge_installments_high.label} {$form.pledge_installments_high.html}
  </td>
</tr>

<tr>
  <td colspan="2">
    {$form.pledge_acknowledge_date_is_not_null.label} &nbsp; {$form.pledge_acknowledge_date_is_not_null.html}
    &nbsp;
  </td>
</tr>

{* campaign in pledge search *}
{include file="CRM/Campaign/Form/addCampaignToSearch.tpl"
campaignTrClass='' campaignTdClass=''}

{if !empty($pledgeGroupTree)}
<tr>
  <td colspan="2">
  {include file="CRM/Custom/Form/Search.tpl" groupTree=$pledgeGroupTree showHideLinks=false}
  </td>
</tr>
{/if}
