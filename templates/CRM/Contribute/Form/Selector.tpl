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

{strip}
  <div class="crm-contact-contribute-contributions">
  <table class="selector row-highlight">
    <thead class="sticky">
    <tr>
      <th scope="col"></th>
      {if !$single and $context eq 'Search'}
        <th scope="col" title="{ts escape='htmlattribute'}Select rows{/ts}">{$form.toggleSelect.html}</th>
      {/if}
      {if !$single}
      <th scope="col"></th>
      {/if}
      {foreach from=$columnHeaders item=header}
        <th scope="col">
          {if $header.sort}
            {assign var='key' value=$header.sort}
            {$sort->_response.$key.link}
          {elseif (!empty($header.name))}
            {$header.name}
          {/if}
        </th>
      {/foreach}
    </tr>
    </thead>

    {counter start=0 skip=1 print=false}
    {foreach from=$rows item=row}
      <tr id="rowid{$row.contribution_id}" class="{cycle values="odd-row,even-row"} {if $row.contribution_cancel_date} cancelled{/if} crm-contribution_{$row.contribution_id}">
        <td><a class="nowrap bold crm-expand-row" title="{ts escape='htmlattribute'}view payments{/ts}" href="{crmURL p='civicrm/payment' q="view=transaction&component=contribution&action=browse&cid=`$row.contact_id`&id=`$row.contribution_id`&selector=1"}"></a></td>
        {if !$single}
          {if $context eq 'Search'}
            {assign var=cbName value=$row.checkbox}
            <td>{$form.$cbName.html}</td>
          {/if}
          <td>{$row.contact_type}</td>
          <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td>
        {/if}
        <td class="crm-contribution-amount">
               {$row.total_amount|crmMoney:$row.currency}
          {if $row.amount_level}<br/>({$row.amount_level}){/if}
          {if $row.contribution_recur_id && $row.is_template}
            <br/>{ts}(Recurring Template){/ts}
          {elseif $row.contribution_recur_id}
            <br/>{ts}(Recurring){/ts}
          {/if}
        </td>
      {foreach from=$columnHeaders item=column}
        {assign var='columnName' value=$column.field_name}
        {if !$columnName}{* if field_name has not been set skip, this helps with not changing anything not specifically edited *}
        {elseif $columnName === 'total_amount'}{* rendered above as soft credit columns = confusing *}
        {elseif $column.type === 'actions'}{* rendered below as soft credit column handling = not fixed *}
        {elseif $columnName == 'contribution_status'}
          <td class="crm-contribution-status">
            {$row.contribution_status}<br/>
            {if $row.contribution_cancel_date}
              {$row.contribution_cancel_date|crmDate}
            {/if}
          </td>
        {else}
          {if $column.type == 'date'}
            <td class="crm-contribution-{$columnName}">
              {$row.$columnName|crmDate}
            </td>
          {else}
          <td class="crm-{$columnName} crm-{$columnName}_">
            {$row.$columnName}
          </td>
          {/if}
        {/if}
      {/foreach}
        {if $softCreditColumns}
          <td class="crm-contribution-soft_credit_name">
            <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contribution_soft_credit_contact_id`"}">{$row.contribution_soft_credit_name}</a>
          </td>
          <td class="crm-contribution-soft_credit_type">{$row.contribution_soft_credit_type}</td>
        {/if}
        <td>{$row.action|replace:'xx':$row.contribution_id}</td>
      </tr>
    {/foreach}

  </table>
  </div>
{/strip}

{include file="CRM/common/pager.tpl" location="bottom"}
{crmScript file='js/crm.expandRow.js'}
