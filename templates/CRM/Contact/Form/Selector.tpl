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
{include file="CRM/common/pagerAToZ.tpl"}
<table summary="{ts escape='htmlattribute'}Search results listings.{/ts}" class="selector row-highlight">
  <thead class="sticky">
    <tr>
      <th scope="col" title="{ts escape='htmlattribute'}Select rows{/ts}">{$form.toggleSelect.html}</th>
      {if $context eq 'smog'}
          <th scope="col">
            {ts}Status{/ts}
          </th>
      {/if}
      {foreach from=$columnHeaders item=header}
        <th scope="col">
        {if !empty($header.sort)}
          {assign var='key' value=$header.sort}
          {$sort->_response.$key.link}
        {elseif !empty($header.name)}
          {$header.name}
        {/if}
        </th>
      {/foreach}
    </tr>
  </thead>

  {counter start=0 skip=1 print=false}

  {if $id}
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
               {if ($key neq "checkbox") and ($key neq "action") and ($key neq "contact_type") and ($key neq "contact_type_orig") and ($key neq "status") and ($key neq "sort_name") and ($key neq "contact_id")}
              <td>
                {if $key EQ "household_income_total"}
                    {$value|crmMoney}
                {elseif strpos( $key, '_date' ) !== false}
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
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`&context=`$context`"}">{if $row.contact_is_deleted}<del>{/if}{$row.sort_name}{if $row.contact_is_deleted}</del>{/if}</a></td>
            {if $action eq 512 or $action eq 256}
              {if !empty($columnHeaders.street_address)}
          <td><span title="{$row.street_address|escape}">{$row.street_address|mb_truncate:22:"...":true}{privacyFlag field=do_not_mail condition=$row.do_not_mail}</span></td>
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
                    <span title="{$row.email|escape}">
                        {$row.email|mb_truncate:17:"...":true}
                        {privacyFlag field=do_not_email condition=$row.do_not_email}
                        {privacyFlag field=on_hold condition=$row.on_hold}
                    </span>
                {/if}
              </td>
              <td>
                {if $row.phone}
                  {$row.phone}
                  {privacyFlag field=do_not_phone condition=$row.do_not_phone}
                  {privacyFlag field=do_not_sms condition=$row.do_not_sms}
                {/if}
              </td>
           {else}
              {foreach from=$row item=value key=key}
                {if ($key neq "checkbox") and ($key neq "action") and ($key neq "contact_type") and ($key neq "status") and ($key neq "sort_name") and ($key neq "contact_id") and ($key neq "contact_type_orig")}
                 <td>{$value}&nbsp;</td>
                {/if}
              {/foreach}
            {/if}
            <td style='width:125px;'>{$row.action|replace:'xx':$row.contact_id}</td>
         </tr>
    {/foreach}
  {/if}
</table>
{include file="CRM/common/pager.tpl" location="bottom"}
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
