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
   {include file="CRM/Financial/Form/FinancialTypeAccount.tpl"}
{else}
{if $rows}
<div id="ltype">
  <div class="form-item">
    {if $action ne 1 and $action ne 2}
      <div class="action-link">
      {crmButton q="action=add&reset=1&aid=$aid" id="newfinancialTypeAccount"  icon="plus-circle"}{ts}Assign Account{/ts}{/crmButton}
  {crmButton p="civicrm/admin/financial/financialType/edit" q="action=update&id=`$aid`&reset=1" icon="pencil"}{ts}Edit Financial Type{/ts}{/crmButton}
      </div>
    {/if}
    {strip}
    {* handle enable/disable actions*}
     {include file="CRM/common/enableDisableApi.tpl"}
      <table cellpadding="0" cellspacing="0" border="0">
        <thead class="sticky">
          <th>{ts}Relationship{/ts}</th>
          <th>{ts}Financial Account{/ts}</th>
          <th>{ts}Accounting Code{/ts}</th>
          <th>{ts}Account Type (Code){/ts}</th>
          <th>{ts}Owned By{/ts}</th>
          <th>{ts}Is Active?{/ts}</th>
          <th></th>
        </thead>
        {foreach from=$rows item=row}
        <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
          <td>{$row.account_relationship}</td>
          <td>{$row.financial_account}</td>
          <td>{$row.accounting_code}</td>
          <td>{$row.financial_account_type}{if $row.account_type_code} ({$row.account_type_code}){/if}</td>
          <td>{$row.owned_by}</td>
          <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
      </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
      <div class="action-link">
      {crmButton q="action=add&reset=1&aid=$aid" id="newfinancialTypeAccount"  icon="plus-circle"}{ts}Assign Account{/ts}{/crmButton}
  {crmButton p="civicrm/admin/financial/financialType/edit" q="action=update&id=`$aid`&reset=1" icon="pencil"}{ts}Edit Financial Type{/ts}{/crmButton}
      </div>
    {/if}
    </div>
  </div>
  {else}
      <div class="messages status no-popup">
          {icon icon="fa-info-circle"}{/icon}
          {capture assign=crmURL}{crmURL q="action=add&reset=1&aid=$aid"}{/capture}
          {ts 1=$crmURL}There are no financial accounts assigned to this financial type. You can <a href='%1'>assign one</a>.{/ts}
      </div>
  {/if}
{/if}
