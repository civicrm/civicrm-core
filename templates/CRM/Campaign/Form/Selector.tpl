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
  <tr>
    {if !$single and $context eq 'Search'}
        <th scope="col" title="{ts}Select rows{/ts}">{$form.toggleSelect.html}</th>
    {/if}
    <th scope="col"></th>
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

  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}
  <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"} crm-campaign">
    {if !$single}
        {if $context eq 'Search'}
          {assign var=cbName value=$row.checkbox}
          <td>{$form.$cbName.html}</td>
   {/if}
    <td>{$row.contact_type}</td>
    <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td>
    <td>{$row.street_number}</td>
    <td>{$row.street_name}</td>
    <td>{$row.street_address}</td>
    <td>{$row.city}</td>
    <td>{$row.postal_code}</td>
    <td>{$row.state_province}</td>
    <td>{$row.country}</td>
    <td>{$row.email}</td>
    <td>{$row.phone}</td>
    {/if}
  </tr>
  {/foreach}

</table>
{/strip}

{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
