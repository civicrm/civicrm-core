{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Financial/Form/FinancialTypeAccount.tpl"}
{else}
{if $rows}
<div id="ltype">
  <div class="form-item">
    {if $action ne 1 and $action ne 2}
      <div class="action-link">
      <a href="{crmURL q="action=add&reset=1&aid=$aid"}" id="newfinancialTypeAccount" class="button"><span><div class="icon add-icon"></div>{ts}Assign Account{/ts}</span></a>
  <a href="{crmURL p="civicrm/admin/financial/financialType" q="action=update&id=`$aid`&reset=1"}" class="button"><span><div class="icon edit-icon"></div>{ts}Edit Financial Type{/ts}</span></a>
      </div>
    {/if}
    {strip}
    {* handle enable/disable actions*}
     {include file="CRM/common/enableDisableApi.tpl"}
     {include file="CRM/common/crmeditable.tpl"}
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
        <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td>{$row.account_relationship}</td>
          <td>{$row.financial_account}</td>
          <td>{$row.accounting_code}</td>
          <td>{$row.financial_account_type}{if $row.account_type_code} ({$row.account_type_code}){/if}</td>
          <td>{$row.owned_by}</td>
          <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
      </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
      <div class="action-link">
      <a href="{crmURL q="action=add&reset=1&aid=$aid"}" id="newfinancialTypeAccount" class="button"><span><div class="icon add-icon"></div>{ts}Assign Account{/ts}</span></a>
  <a href="{crmURL p="civicrm/admin/financial/financialType" q="action=update&id=`$aid`&reset=1"}" class="button"><span><div class="icon edit-icon"></div>{ts}Edit Financial Type{/ts}</span></a>
      </div>
    {/if}
    </div>
  </div>
  {else}
      <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          {capture assign=crmURL}{crmURL q="action=add&reset=1&aid=$aid"}{/capture}
          {ts 1=$crmURL}There are no financial accounts assigned to this financial type. You can <a href='%1'>assign one</a>.{/ts}
      </div>
  {/if}
{/if}
