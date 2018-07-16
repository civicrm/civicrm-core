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
  <div class="icon inform-icon"></div>
       {ts}There are no records selected for Print.{/ts}
  </div>
{/if}
