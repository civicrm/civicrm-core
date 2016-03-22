{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

{include file="CRM/common/pagerAToZ.tpl"}
<a href="#" class="crm-selection-reset crm-hover-button"><i class="crm-i fa-times-circle-o"></i> {ts}Reset all selections{/ts}</a>

<table summary="{ts}Search results listings.{/ts}" class="selector row-highlight">
  <thead class="sticky">
    <tr>
      <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
      {if $context eq 'smog'}
          <th scope="col">
            {ts}Status{/ts}
          </th>
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

  {counter start=0 skip=1 print=false}

  { if $id }
      {foreach from=$rows item=row}
        <tr id='rowid{$row.contact_id}' class="{cycle values='odd-row,even-row'}">
            {assign var=cbName value=$row.checkbox}
            <td>{$form.$cbName.html}</td>
            {if $context eq 'smog'}
              {if $row.status eq 'Pending'}<td class="status-pending"}>
              {elseif $row.status eq 'Removed'}<td class="status-removed">
              {else}<td>{/if}
              {$row.status}</td>
            {/if}
            <td>{$row.contact_type}</td>
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`&context=`$context`"}">{$row.sort_name}</a></td>
            {foreach from=$row item=value key=key}
               {if ($key neq "checkbox") and ($key neq "action") and ($key neq "contact_type") and ($key neq "contact_type_orig") and ($key neq "status") and ($key neq "sort_name") and ($key neq "contact_id")and ($key neq "contact_sub_type")}
              <td>
                {if $key EQ "household_income_total" }
                    {$value|crmMoney}
                {elseif strpos( $key, '_date' ) !== false }
                    {$value|crmDate}
                {else}
                    {$value}
                {/if}
                     &nbsp;
              </td>
               {/if}
            {/foreach}
            <td>{$row.action|replace:'xx':$row.contact_id}</td>
        </tr>
     {/foreach}
  {else}
      {foreach from=$rows item=row}
         <tr id="rowid{$row.contact_id}" class="{cycle values='odd-row,even-row'}">
            {assign var=cbName value=$row.checkbox}
            <td>{$form.$cbName.html}</td>
            {if $context eq 'smog'}
                {if $row.status eq 'Pending'}<td class="status-pending"}>
                {elseif $row.status eq 'Removed'}<td class="status-removed">
                {else}<td>{/if}
                {$row.status}</td>
            {/if}
            <td>{$row.contact_type}</td>
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`&context=`$context`"}">{if $row.is_deleted}<del>{/if}{$row.sort_name}{if $row.is_deleted}</del>{/if}</a></td>
            {if $action eq 512 or $action eq 256}
              {if !empty($columnHeaders.street_address)}
          <td><span title="{$row.street_address}">{$row.street_address|mb_truncate:22:"...":true}{if $row.do_not_mail} <span class="icon privacy-flag do-not-mail"></span>{/if}</span></td>
        {/if}
        {if !empty($columnHeaders.city)}
                <td>{$row.city}</td>
        {/if}
        {if !empty($columnHeaders.state_province)}
                <td>{$row.state_province}</td>
              {/if}
              {if !empty($columnHeaders.postal_code)}
                <td>{$row.postal_code}</td>
              {/if}
        {if !empty($columnHeaders.country)}
                <td>{$row.country}</td>
              {/if}
              <td>
                {if $row.email}
                    <span title="{$row.email}">
                        {$row.email|mb_truncate:17:"...":true}
                        {if $row.on_hold}
                          (On Hold)<span class="status-hold" title="{ts}This email is on hold (probably due to bouncing).{/ts}"></span>
                        {elseif $row.do_not_email}
                          <span class="icon privacy-flag do-not-email" title="{ts}Do Not Email{/ts}"></span>
                        {/if}
                    </span>
                {/if}
              </td>
              <td>
                {if $row.phone}
                  {$row.phone}
                  {if $row.do_not_phone}
                    <span class="icon privacy-flag do-not-phone" title="{ts}Do Not Phone{/ts}" ></span>
                  {/if}
                {/if}
              </td>
           {else}
              {foreach from=$row item=value key=key}
                {if ($key neq "checkbox") and ($key neq "action") and ($key neq "contact_type") and ($key neq "contact_sub_type") and ($key neq "status") and ($key neq "sort_name") and ($key neq "contact_id") and ($key neq "contact_type_orig")}
                 <td>{$value}&nbsp;</td>
                {/if}
              {/foreach}
            {/if}
            <td style='width:125px;'>{$row.action|replace:'xx':$row.contact_id}</td>
         </tr>
    {/foreach}
  {/if}
</table>

<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    // Clear any old selection that may be lingering in quickform
    $("input.select-row, input.select-rows", 'form.crm-search-form').prop('checked', false).closest('tr').removeClass('crm-row-selected');
    // Retrieve stored checkboxes
    var cids = {/literal}{$selectedContactIds|@json_encode}{literal};
    if (cids.length > 0) {
      $('#mark_x_' + cids.join(',#mark_x_') + ',input[name=radio_ts][value=ts_sel]').prop('checked', true);
    }
  });
{/literal}
</script>
{include file="CRM/common/pager.tpl" location="bottom"}
