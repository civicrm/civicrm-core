{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Financial/Form/FinancialType.tpl"}
{else}
    <div class="help">
      {capture assign="premiumLink"}{crmURL p="civicrm/admin/contribute/managePremiums" q="reset=1"}{/capture}
      <p>{ts 1=$premiumLink}Financial types are used to categorize contributions for reporting and accounting purposes. You may set up as many as needed, including commonly used types such as Donation, Campaign Contribution or Membership Dues.  Additionally, financial types can account for the inventory and expense of <a href="%1">premiums</a>.{/ts}</p>
      {capture assign="acctLink"}{crmURL p="civicrm/admin/financial/financialAccount" q="reset=1&action=browse"}{/capture}
      <p>{ts 1=$acctLink}Each financial type relates to a number of <a href="%1">financial accounts</a> to track income, accounts receivable, and fees.</p>{/ts}
    </div>

<div class="crm-content-block crm-block">
{if $rows}
<div id="ltype">
<p></p>
    <div class="form-item">
        {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
   {include file="CRM/common/jsortable.tpl"}
        <table cellpadding="0" cellspacing="0" border="0" class="row-highlight">
          <thead class="sticky">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Financial Accounts{/ts}</th>
            <th>{ts}Deductible?{/ts}</th>
            <th>{ts}Reserved?{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
          </thead>
         {foreach from=$rows item=row}
        <tr id="financial_type-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td class="crm-editable" data-field="name">{$row.name}</td>
          <td class="crm-editable" data-field="description" data-type="textarea">{$row.description}</td>
          <td>{$row.financial_account}</td>
          <td class="crm-editable" data-field="is_deductible" data-type="boolean">{if $row.is_deductible eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
         </table>
        {/strip}
    </div>
</div>
{else}
    <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
      {ts}None found.{/ts}
    </div>
{/if}
  {if $action ne 1 and $action ne 2}
    <div class="action-link">
      {crmButton q="action=add&reset=1" id="newFinancialType"  icon="plus-circle"}{ts}Add Financial Type{/ts}{/crmButton}
      {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>
  {/if}
</div>
{/if}
