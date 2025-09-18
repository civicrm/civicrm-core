{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
    {include file="CRM/common/pager.tpl" location="top"}

<p class="description">
  {ts}Click arrow to view pledge payments.{/ts}
</p>
{strip}
<table class="selector row-highlight">
    <thead class="sticky">
        {if ! $single and $context eq 'Search'}
            <th scope="col" title="{ts escape='htmlattribute'}Select rows{/ts}">{$form.toggleSelect.html}</th>
        {/if}
            <th></th>
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
    </thead>
    {counter start=0 skip=1 print=false}
    {foreach from=$rows item=row}
        {cycle values="odd-row,even-row" assign=rowClass}
        <tr id='rowid{$row.pledge_id}' class='{$rowClass} {if $row.pledge_status_name eq 'Overdue'} status-overdue{/if}'>
            {if $context eq 'Search'}
                {assign var=cbName value=$row.checkbox}
                <td>{$form.$cbName.html}</td>
            {/if}
            <td>
                <a class="crm-expand-row" title="{ts escape='htmlattribute'}view payments{/ts}" href="{crmURL p='civicrm/pledge/payment' q="action=browse&context=`$context`&pledgeId=`$row.pledge_id`&cid=`$row.contact_id`"}"></a>
            </td>
            {if ! $single}
                <td>{$row.contact_type}</td>
                <td>
                    <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a>
                </td>
            {/if}
            <td class="right">{$row.pledge_amount|crmMoney:$row.pledge_currency}</td>
            <td class="right">{$row.pledge_total_paid|crmMoney:$row.pledge_currency}</td>
            <td class="right">{$row.pledge_balance_amount|crmMoney:$row.pledge_currency}</td>
            <td>{$row.pledge_financial_type}</td>
            <td>{$row.pledge_create_date|truncate:10:''|crmDate}</td>
            <td>{$row.pledge_next_pay_date|truncate:10:''|crmDate}</td>
            <td class="right">{$row.pledge_next_pay_amount|crmMoney:$row.pledge_currency}</td>
            <td>{$row.pledge_status}</td>
            <td>{$row.action|replace:'xx':$row.pledge_id}</td>
        </tr>
    {/foreach}

    {* Dashboard only lists 10 most recent pledges. *}
    {if $context EQ 'dashboard' and $limit and $pager->_totalItems GT $limit}
        <tr class="even-row">
            <td colspan="10"><a href="{crmURL p='civicrm/pledge/search' q='reset=1'}"><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}Find more pledges{/ts}... </a></td>
        </tr>
    {/if}

</table>
{/strip}

    {include file="CRM/common/pager.tpl" location="bottom"}

{crmScript file='js/crm.expandRow.js'}
