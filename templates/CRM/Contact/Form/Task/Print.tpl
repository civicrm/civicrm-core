{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $rows}
<div class="crm-submit-buttons element-right">
     {include file="CRM/common/formButtons.tpl" location="top"}
</div>
<div class="spacer"></div>
<div>
<br />
<table>
  <tr class="columnheader">
{if $id OR $customSearchID}
  {foreach from=$columnHeaders item=header}
     <th>{$header}</th>
  {/foreach}
{else}
    <td>{ts}Name{/ts}</td>
    {if !empty($columnHeaders.street_address)}
      <td>{ts}Address{/ts}</td>
    {/if}
    {if !empty($columnHeaders.city)}
      <td>{ts}City{/ts}</td>
    {/if}
    {if !empty($columnHeaders.state_province)}
      <td>{ts}State{/ts}</td>
    {/if}
    {if !empty($columnHeaders.postal_code)}
      <td>{ts}Postal{/ts}</td>
    {/if}
    {if !empty($columnHeaders.country)}
      <td>{ts}Country{/ts}</td>
    {/if}
    <td>{ts}Email{/ts}</td>
    <td>{ts}Phone{/ts}</td>
    <td>{ts}Do Not Email{/ts}</td>
    <td>{ts}Do Not Phone{/ts}</td>
    <td>{ts}Do Not mail{/ts}</td>
{/if}
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
{if $id}
  <td>{$row.sort_name}</td>
  {foreach from=$row item=value key=key}
    {if ($key neq "checkbox") and ($key neq "action") and ($key neq "contact_type") and ($key neq "status") and ($key neq "contact_id") and ($key neq "sort_name")}
      <td>{$value}</td>
    {/if}
  {/foreach}
{elseif $customSearchID}
  {foreach from=$columnHeaders item=header key=name}
    <td>{$row.$name}</td>
  {/foreach}
{else}
        <td>{$row.sort_name}</td>
        {if !empty($columnHeaders.street_address)}
          <td>{$row.street_address}</td>
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
        <td>{$row.email}</td>
        <td>{$row.phone}</td>
        {if $row.do_not_email == 1}
          <td>{$row.do_not_email}</td>
        {else}
          <td>&nbsp;</td>
        {/if}
        {if $row.do_not_phone == 1}
          <td>{$row.do_not_phone}</td>
        {else}
          <td>&nbsp;</td>
        {/if}
        {if $row.do_not_mail == 1}
          <td>{$row.do_not_mail}</td>
        {else}
          <td>&nbsp;</td>
        {/if}
{/if}
    </tr>
{/foreach}
</table>
</div>

<div class="crm-submit-buttons element-right">
     {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{else}
   <div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
       {ts}There are no records selected for Print.{/ts}
  </div>
{/if}
