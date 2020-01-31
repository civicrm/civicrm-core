{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="form-item crm-block crm-form-block crm-contribution-form-block">
<div class="help">
    {ts}Use this form to record received payments for 'pay later' online contributions, membership signups and event registrations. You can use the Transaction ID field to record account+check number, bank transfer identifier, or other unique payment identifier.{/ts}
</div>
<fieldset>
    <legend>{ts}Update Contribution Status{/ts}</legend>
     <table class="form-layout-compressed">
     <tr class="crm-contribution-form-block-contribution_status_id"><td class="label">{$form.contribution_status_id.label}</td><td class="html-adjust">{$form.contribution_status_id.html}<br />
            <span class="description">{ts}Assign the selected status to all contributions listed below.{/ts}</td></tr>
     </table>
<table>
<tr class="columnheader">
    <th>{ts}Name{/ts}</th>
    <th class="right">{ts}Amount{/ts}&nbsp;&nbsp;</th>
    <th>{ts}Source{/ts}</th>
    <th>{ts}Fee Amount{/ts}</th>
    <th>{ts}Payment Method{/ts}</th>
    <th>{ts}Check{/ts} #</th>
    <th>{ts}Transaction ID{/ts}</th>
    <th>{ts}Transaction Date{/ts}</th>
</tr>

{foreach from=$rows item=row}
<tr class="{cycle values="odd-row,even-row"}">
    <td>{$row.display_name}</td>
    <td class="right nowrap">{$row.amount|crmMoney}&nbsp;&nbsp;</td>
    <td>{$row.source}</td>
    {assign var="element_name" value="fee_amount_"|cat:$row.contribution_id}
    <td>{$form.$element_name.html}</td>
    {assign var="element_name" value="payment_instrument_id_"|cat:$row.contribution_id}
    <td class="form-text four">{$form.$element_name.html}</td>
    {assign var="element_name" value="check_number_"|cat:$row.contribution_id}
    <td class="form-text four">{$form.$element_name.html|crmAddClass:four}</td>
    {assign var="element_name" value="trxn_id_"|cat:$row.contribution_id}
    <td>{$form.$element_name.html|crmAddClass:eight}</td>
    {assign var="element_name" value="trxn_date_"|cat:$row.contribution_id}
    <td>{$form.$element_name.html}</td>
</tr>
{/foreach}
</table>
  <div class="crm-submit-buttons">{$form.buttons.html}</div>
</fieldset>
</div>
