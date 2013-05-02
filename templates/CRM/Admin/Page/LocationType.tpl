{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<div id="help">
    {ts}Location types provide convenient labels to differentiate contacts' location(s). Administrators may define as many additional types as appropriate for your constituents (examples might be Main Office, School, Vacation Home...).{/ts}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/LocationType.tpl"}
{else}

{if $rows}
<div id="ltype">
    {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisable.tpl"}
    {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
    <thead>
    <tr>
        <th id="sortable">{ts}Name{/ts}</th>
        <th>{ts}Display Name{/ts}</th>
        <th>{ts}vCard{/ts}</th>
        <th id="nosort">{ts}Description{/ts}</th>
        <th>{ts}Enabled?{/ts}</th>
        <th>{ts}Default?{/ts}</th>
        <th></th>
    </tr>
    </thead>
    {foreach from=$rows item=row}
    <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class} crm-locationType {if NOT $row.is_active} disabled{/if}">
        <td class="crm-locationType-name">{$row.name}</td>
        <td class="crm-locationType-display_name">{$row.display_name}</td>
        <td class="crm-locationType-vcard_name">{$row.vcard_name}</td>
        <td class="crm-locationType-description">{$row.description}</td>
        <td id="row_{$row.id}_status" class="crm-locationType-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td class="crm-locationType-is_default" >{if $row.is_default eq 1}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
    </tr>
    {/foreach}
    </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
    <div class="action-link">
  <a href="{crmURL q="action=add&reset=1"}" id="newLocationType" class="button"><span>&raquo; {ts}New Location Type{/ts}</span></a>
    </div>
    {/if}
</div>
{else}
    <div class="messages status no-popup">
          <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {capture assign=crmURL}{crmURL p='civicrm/admin/locationType' q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Location Types entered for this Contact. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
{/if}
