{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
{include file="CRM/common/pager.tpl" location="top"}

{strip}
  <table class="selector row-highlight">
    <thead class="sticky">
    <tr>
      {if !$single and $context eq 'Search' }
        <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th>
      {/if}
      {foreach from=$columnHeaders item=header}
        <th scope="col">
          {if $header.sort}
            {assign var='key' value=$header.sort}
            {$sort->_response.$key.link}
          {else}
            {$header.name}
          {/if}
        </th>
      {/foreach}
    </tr>
    </thead>

    <p class="description">
      {ts}Click arrow to view contribution details.{/ts}
    </p>
    {counter start=0 skip=1 print=false}
    {foreach from=$rows item=row}
      <tr id="rowid{$row.id}" class="{cycle values="odd-row,even-row"}">
        {if !$single }
          {if $context eq 'Search' }
            {assign var=cbName value=$row.checkbox}
            <td>{$form.$cbName.html}</td>
          {/if}
          <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td>
        {/if}
      {foreach from=$columnHeaders item=column}
        {assign var='columnName' value=$column.field_name}
        {if !$columnName}{* if field_name has not been set skip, this helps with not changing anything not specifically edited *}
        {else}
          {if $column.type == 'date'}
            <td class="crm-contribution-{$columnName}">
              {$row.$columnName|crmDate}
            </td>
          {elseif $column.field_name == 'financial_trxn_total_amount'}
            <td class="crm-{$columnName} crm-{$columnName}_{$row.columnName}">
            <a class="nowrap bold crm-expand-row" title="{ts}view contribution{/ts}" href="{crmURL p='civicrm/contact/view/contribution'
              q="reset=1&id=`$row.contribution_id`&cid=`$row.contact_id`&action=view&context=contribution&selectedChild=contribute"}">
              &nbsp; {$row.financial_trxn_total_amount|crmMoney:$row.financial_trxn_currency}
            </a>
          </td>
          {else}
          <td class="crm-{$columnName} crm-{$columnName}_{$row.columnName}">
            {$row.$columnName}
          </td>
          {/if}
        {/if}
      {/foreach}
        <td>{$row.action}</td>
      </tr>
    {/foreach}

  </table>
{/strip}

{include file="CRM/common/pager.tpl" location="bottom"}
{crmScript file='js/crm.expandRow.js'}
