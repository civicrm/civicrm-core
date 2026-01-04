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
<table class="caseSelector row-highlight">
  <tr class="columnheader">

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
  </tr>

  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}

  <tr id='rowid{$row.case_id}' class="{cycle values="odd-row,even-row"} crm-case crm-case-status_{$row.case_status_id} crm-case-type_{$row.case_type_id}">
    {if $context eq 'Search' && !$single}
        {assign var=cbName value=$row.checkbox}
        <td>{$form.$cbName.html}</td>
    {/if}
        <td class="crm-case-id crm-case-id_{$row.case_id}">
          <a title="{ts escape='htmlattribute'}Activities{/ts}" class="crm-expand-row" href="{crmURL p='civicrm/case/details' q="caseId=`$row.case_id`&cid=`$row.contact_id`"}"></a>
        </td>

    {if !$single}
      <td class="crm-case-id crm-case-id_{$row.case_id}"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts escape='htmlattribute'}View Contact Details{/ts}">{$row.sort_name}</a>{if $row.phone}<br /><span class="description">{$row.phone}</span>{/if}<br /><span class="description">{ts}Case ID{/ts}: {$row.case_id}</span></td>
    {/if}

    <td class="crm-case-subject">{$row.case_subject}</td>
    <td class="{$row.class} crm-case-status_{$row.case_status}">{$row.case_status}</td>
    <td class="crm-case-case_type">{$row.case_type}</td>
    <td class="crm-case-case_role">{if $row.case_role}{$row.case_role}{else}---{/if}</td>
    <td class="crm-case-case_manager">{$row.casemanager}</td>
    <td class="crm-case-case_recent_activity_type">{if $row.case_recent_activity_type}
  {$row.case_recent_activity_type}<br />{$row.case_recent_activity_date|crmDate}{else}---{/if}</td>
    <td class="crm-case-case_scheduled_activity_type">{if $row.case_scheduled_activity_type}
  {$row.case_scheduled_activity_type}<br />{$row.case_scheduled_activity_date|crmDate}{else}---{/if}</td>
    <td>{$row.action|replace:'xx':$row.case_id}</td>
  {/foreach}

    {* Dashboard only lists 10 most recent cases. *}
    {if $context EQ 'dashboard' and $limit and $pager->_totalItems GT $limit}
      <tr class="even-row">
        <td colspan="10"><a href="{crmURL p='civicrm/case/search' q='reset=1'}"><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}Find more cases{/ts}... </a></td>
      </tr>
    {/if}

</table>
{/strip}

{include file="CRM/common/pager.tpl" location="bottom"}
{crmScript file='js/crm.expandRow.js'}
