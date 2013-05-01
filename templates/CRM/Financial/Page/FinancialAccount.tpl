{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
   {include file="CRM/Financial/Form/FinancialAccount.tpl"}
{else}
  <div id="help">
    {capture assign="typeLink"}{crmURL p="civicrm/admin/financial/financialType" q="reset=1"}{/capture}
    {capture assign="paymentLink"}{crmURL p="civicrm/admin/options/payment_instrument" q="group=payment_instrument&reset=1"}{/capture}
    {capture assign="premiumLink"}{crmURL p="civicrm/admin/contribute/managePremiums" q="reset=1"}{/capture}
    <p>{ts 1=$typeLink 2=$paymentLink 3=$premiumLink}Financial accounts correspond to those in your accounting system.  <a href="%1">Financial types</a>, <a href="%2">payment instruments</a>, and <a href="%3">premiums</a> are associated with financial accounts so that they can result in the proper double-entry transactions to export to your accounting system.{/ts}</p>
  </div>
  {if $action ne 1 and $action ne 2}
    <div class="action-link">
	    <a href="{crmURL q="action=add&reset=1"}" id="newFinancialAccount-top" class="button"><span><div class="icon add-icon"></div>{ts}Add Financial Account{/ts}</span></a>
    </div>
  {/if}

  {if $rows}
    {include file="CRM/common/jsortable.tpl"}
    <div id="ltype">
    <p></p>
      <div class="form-item">
      {strip}
    	{* handle enable/disable actions*}
     	{include file="CRM/common/enableDisable.tpl"}
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
        <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
        <td>{$row.name}</td>	
        <td>{$row.description}</td>
        <td>{$row.accounting_code}</td>
        <td>{$row.financial_account_type_id}{if $row.account_type_code} ({$row.account_type_code}){/if}</td>
        <td>{if $row.is_deductible eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td>{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td>{if $row.is_default eq 1}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" /> {/if}</td>
        <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
      </table>
      {/strip}

      {if $action ne 1 and $action ne 2}
        <div class="action-link">
    	    <a href="{crmURL q="action=add&reset=1"}" id="newFinancialAccount-bottom" class="button"><span><div class="icon add-icon"></div>{ts}Add Financial Account{/ts}</span></a>
        </div>
      {/if}
      </div>
    </div>
  {else}
    <div class="messages status">
      <div class="icon inform-icon"></div>
      {capture assign=crmURL}{crmURL q="action=add&reset=1"}{/capture}
      {ts 1=$crmURL}There are no Financial Account entered. You can <a href='%1'>add one</a>.{/ts}
    </div>    
  {/if}
{/if}
