{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
   {include file="CRM/Financial/Form/FinancialType.tpl"}
{else}
    <div class="help">
      {capture assign="premiumLink"}{crmURL p="civicrm/admin/contribute/managePremiums" q="reset=1"}{/capture}
      <p>{ts 1=$premiumLink}Financial types are used to categorize contributions for reporting and accounting purposes. You may set up as many as needed, including commonly used types such as Donation, Campaign Contribution or Membership Dues.  Additionally, financial types can account for the inventory and expense of <a href="%1">premiums</a>.{/ts}</p>
      {capture assign="acctLink"}{crmURL p="civicrm/admin/financial/financialAccount" q="reset=1&action=browse"}{/capture}
      <p>{ts 1=$acctLink}Each financial type relates to a number of <a href="%1">financial accounts</a> to track income, accounts receivable, and fees.</p>{/ts}
    </div>

{if $rows}
<div id="ltype">
<p></p>
    <div class="form-item">
        {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
   {include file="CRM/common/jsortable.tpl"}
        <table cellpadding="0" cellspacing="0" border="0">
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
{/if}
