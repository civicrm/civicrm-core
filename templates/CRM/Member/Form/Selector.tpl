{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="top"}
{/if}

{strip}
<table class="selector row-highlight">
<thead class="sticky">
{if ! $single and $context eq 'Search'}
  <th scope="col" title="{ts escape='htmlattribute'}Select rows{/ts}">{$form.toggleSelect.html}</th>
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
  </thead>

  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}
  <tr id='rowid{$row.membership_id}' class="{cycle values="odd-row,even-row"} {*if $row.cancel_date} disabled{/if*} crm-membership_{$row.membership_id}">
     {if ! $single}
       {if $context eq 'Search'}
          {assign var=cbName value=$row.checkbox}
          <td>{$form.$cbName.html}</td>
       {/if}
       <td>{$row.contact_type}</td>
       <td>
            <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts escape='htmlattribute'}View contact record{/ts}">{$row.sort_name}</a>
        </td>
    {/if}
    <td class="crm-membership-type crm-membership-type_{$row.membership_type}">
        {$row.membership_type}
        {if $row.owner_membership_id}<br />({ts}by relationship{/ts}){/if}
    </td>
    <td class="crm-membership-join_date">{$row.membership_join_date|truncate:10:''|crmDate}</td>
    <td class="crm-membership-start_date">{$row.membership_start_date|truncate:10:''|crmDate}</td>
    <td class="crm-membership-end_date">{$row.membership_end_date|truncate:10:''|crmDate}</td>
    <td class="crm-membership-source">{$row.membership_source}</td>
    <td class="crm-membership-status crm-membership-status_{$row.membership_status}">{$row.membership_status}</td>
    <td class="crm-membership-auto_renew">
      {if $row.auto_renew eq 1}
        <i class="crm-i fa-check" title="{ts escape='htmlattribute'}Auto-renew active{/ts}" role="img" aria-hidden="true"></i>
      {elseif $row.auto_renew eq 2}
        <i class="crm-i fa-ban" title="{ts escape='htmlattribute'}Auto-renew error{/ts}" role="img" aria-hidden="true"></i>
      {/if}
    </td>
    <td>
        {$row.action|replace:'xx':$row.membership_id}
        {if $row.owner_membership_id}
            <a href="{crmURL p='civicrm/membership/view' q="reset=1&id=`$row.owner_membership_id`&action=view&context=search"}" title="{ts escape='htmlattribute'}View Primary member record{/ts}" class="action-item">{ts}View Primary{/ts}</a>
        {/if}
    </td>
   </tr>
  {/foreach}
{* Link to "View all memberships" for Contact Summary selector display *}
{if ($context EQ 'membership') AND $pager->_totalItems GT $limit}
  <tr class="even-row">
    <td colspan="7"><a href="{crmURL p='civicrm/contact/view' q="reset=1&force=1&selectedChild=member&cid=$contactId"}"><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}View all memberships for this contact{/ts}...</a></td></tr>
  </tr>
{/if}
{if ($context EQ 'dashboard') AND $pager->_totalItems GT $limit}
  <tr class="even-row">
    <td colspan="10"><a href="{crmURL p='civicrm/member/search' q='reset=1'}"><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}Find more members{/ts}...</a></td></tr>
  </tr>
{/if}
</table>
{/strip}



{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
