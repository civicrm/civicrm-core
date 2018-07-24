{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="top"}
{/if}

{strip}
<table class="selector row-highlight">
<thead class="sticky">
{if ! $single and $context eq 'Search' }
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
  </thead>

  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}
  <tr id='rowid{$row.membership_id}' class="{cycle values="odd-row,even-row"} {*if $row.cancel_date} disabled{/if*} crm-membership_{$row.membership_id}">
     {if ! $single }
       {if $context eq 'Search' }
          {assign var=cbName value=$row.checkbox}
          <td>{$form.$cbName.html}</td>
       {/if}
       <td>{$row.contact_type}</td>
       <td>
            <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact record{/ts}">{$row.sort_name}</a>
        </td>
    {/if}
    <td class="crm-membership-type crm-membership-type_{$row.membership_type}">
        {$row.membership_type}{if $row.is_test} ({ts}test{/ts}){/if}
        {if $row.owner_membership_id}<br />({ts}by relationship{/ts}){/if}
    </td>
    <td class="crm-membership-join_date">{$row.join_date|truncate:10:''|crmDate}</td>
    <td class="crm-membership-start_date">{$row.membership_start_date|truncate:10:''|crmDate}</td>
    <td class="crm-membership-end_date">{$row.membership_end_date|truncate:10:''|crmDate}</td>
    <td class="crm-membership-source">{$row.membership_source}</td>
    <td class="crm-membership-status crm-membership-status_{$row.membership_status}">{$row.membership_status}</td>
    <td class="crm-membership-auto_renew">
      {if $row.auto_renew eq 1}
        <i class="crm-i fa-check" aria-hidden="true" title="{ts}Auto-renew active{/ts}"></i>
      {elseif $row.auto_renew eq 2}
        <i class="crm-i fa-ban" aria-hidden="true" title="{ts}Auto-renew error{/ts}"></i>
      {/if}
    </td>
    <td>
        {$row.action|replace:'xx':$row.membership_id}
        {if $row.owner_membership_id}
            <a href="{crmURL p='civicrm/membership/view' q="reset=1&id=`$row.owner_membership_id`&action=view&context=search"}" title="{ts}View Primary member record{/ts}" class="action-item">{ts}View Primary{/ts}</a>
        {/if}
    </td>
   </tr>
  {/foreach}
{* Link to "View all memberships" for Contact Summary selector display *}
{if ($context EQ 'membership') AND $pager->_totalItems GT $limit}
  <tr class="even-row">
    <td colspan="7"><a href="{crmURL p='civicrm/contact/view' q="reset=1&force=1&selectedChild=member&cid=$contactId"}">&raquo; {ts}View all memberships for this contact{/ts}...</a></td></tr>
  </tr>
{/if}
{if ($context EQ 'dashboard') AND $pager->_totalItems GT $limit}
  <tr class="even-row">
    <td colspan="9"><a href="{crmURL p='civicrm/member/search' q='reset=1'}">&raquo; {ts}Find more members{/ts}...</a></td></tr>
  </tr>
{/if}
</table>
{/strip}



{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
