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
   {include file="CRM/Financial/Form/FinancialAccount.tpl"}
{else}
  <div class="help">
    {capture assign="typeLink"}{crmURL p="civicrm/admin/financial/financialType" q="reset=1"}{/capture}
    {capture assign="paymentLink"}{crmURL p="civicrm/admin/options/payment_instrument" q="reset=1"}{/capture}
    {capture assign="premiumLink"}{crmURL p="civicrm/admin/contribute/managePremiums" q="reset=1"}{/capture}
    <p>{ts 1=$typeLink 2=$paymentLink 3=$premiumLink}Financial accounts correspond to those in your accounting system.  <a href="%1">Financial types</a>, <a href="%2">payment methods</a>, and <a href="%3">premiums</a> are associated with financial accounts so that they can result in the proper double-entry transactions to export to your accounting system.{/ts}</p>
  </div>

<div class="crm-content-block crm-block">
  {if $action ne 1 and $action ne 2}
    <div class="action-link">
      <a href="{crmURL q="action=add&reset=1"}" id="newFinancialAccount-top" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add Financial Account{/ts}</span></a>
    </div>
  {/if}

  {if $rows}
    {include file="CRM/common/jsortable.tpl"}
    <div id="ltype">
    <p></p>
      <div class="form-item">
      {strip}
      {* handle enable/disable actions*}
       {include file="CRM/common/enableDisableApi.tpl"}
      <table id="crm-financial_accounts" class="display">
        <thead class="sticky">
          <th>{ts}Name{/ts}</th>
          <th>{ts}Description{/ts}</th>
          <th>{ts}Acctg Code{/ts}</th>
          <th id="sortable">{ts}Account Type{/ts}</th>
          <th>{ts}Deductible?{/ts}</th>
          <th>{ts}Reserved?{/ts}</th>
          <th>{ts}Default?{/ts}</th>
          <th>{ts}Enabled?{/ts}</th>
          <th></th>
        </thead>
        {foreach from=$rows item=row}
        <tr id="financial_account-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {if !empty($row.class)}{$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
          <td class="crm-editable" data-field="label">{$row.label}</td>
          <td class="crm-editable" data-field="description" data-type="textarea">{if !empty($row.description)}{$row.description}{/if}</td>
          <td class="crm-editable" data-field="accounting_code">{$row.accounting_code}</td>
          <td>{$row.financial_account_type_id}{if $row.account_type_code} ({$row.account_type_code}){/if}</td>
          <td>{if $row.is_deductible eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{icon condition=$row.is_default}{ts}Default{/ts}{/icon}</td>
          <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
      </table>
      {/strip}

      {if $action ne 1 and $action ne 2}
        <div class="action-link">
          <a href="{crmURL q="action=add&reset=1"}" id="newFinancialAccount-bottom" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add Financial Account{/ts}</span></a>
        </div>
      {/if}
      </div>
    </div>
  {else}
    <div class="messages status">
      {icon icon="fa-info-circle"}{/icon}
      {capture assign=crmURL}{crmURL q="action=add&reset=1"}{/capture}
      {ts 1=$crmURL}There are no Financial Account entered. You can <a href='%1'>add one</a>.{/ts}
    </div>
  {/if}
</div>

{/if}
