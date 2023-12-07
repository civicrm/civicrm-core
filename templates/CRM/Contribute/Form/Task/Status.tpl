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
  <h3>{ts}Record payments for contributions{/ts}</h3>
  <div class="help">
    <p>{ts}Use this form to record received payments for "pay later" online contributions, membership signups and event registrations. You can use the Transaction ID field to record account+check number, bank transfer identifier, or other unique payment identifier.{/ts}</p>
    <p>{ts}The contribution status will be updated as appropriate.  To update contribution statuses directly, return to the search results and select "Update multiple contributions".{/ts}</p>
  </div>

  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-is_email_receipt">
      <td class="label">{$form.is_email_receipt.label}</td>
      <td class="html-adjust">{$form.is_email_receipt.html}<br />
        <span class="description">{ts}When checked CiviCRM will send an e-mail receipt to the donor. Leave unchecked when you don't want to send an e-mail.{/ts}</span>
      </td>
    </tr>
  </table>
  <table>
    <tr class="columnheader">
      <th>{ts}Name{/ts}</th>
      <th class="right">{ts}Amount{/ts}&nbsp;&nbsp;</th>
      <th>{ts}Contribution Source{/ts}</th>
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
</div>
