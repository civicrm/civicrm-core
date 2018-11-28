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
{* Admin page for browsing Option Group *}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/OptionGroup.tpl"}
{else}
<div class="help">
    {ts}CiviCRM stores configurable choices for various drop-down fields as 'option groups'. You can click <strong>Options</strong> to view the available choices.{/ts}
    <p><i class="crm-i fa-exclamation-triangle"></i> {ts}WARNING: Many option groups are used programatically and values should be added or modified with caution.{/ts}</p>
</div>
{/if}

{if $rows}

<div id="browseValues">
  {if $action ne 1 and $action ne 2}
    <div class="action-link">
      {crmButton q="action=add&reset=1" id="newOptionGroup"  icon="plus-circle"}{ts}Add Option Group{/ts}{/crmButton}
      {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>
  {/if}

  {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
    <table cellpadding="0" cellspacing="0" border="0">
        <tr class="columnheader">
          <th>{ts}Title{/ts}</th>
          <th>{ts}Name{/ts}</th>
          <th>{ts}Reserved{/ts}</th>
          <th>{ts}Enabled?{/ts}</th>
          <th></th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="optionGroup-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td class="crm-admin-optionGroup-title">{if $row.title}{$row.title}{else}( {ts}none{/ts} ){/if}</td>
          <td class="crm-admin-optionGroup-name">{$row.name}</td>
          <td class="crm-admin-optionGroup-is_reserved">{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="crm-admin-optionGroup-is_active" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>
            <a href="{crmURL p="civicrm/admin/options" q="id=`$row.id`&action=update&reset=1"}" class="action-item crm-hover-button" title="{ts}OptionGroup settings{/ts}">{ts}Settings{/ts}</a>
            <a href="{crmURL p="civicrm/admin/options" q="gid=`$row.id`&reset=1"}" class="action-item crm-hover-button" title="{ts}View and Edit Options{/ts}">{ts}Edit Options{/ts}</a>
          </td>
        </tr>
        {/foreach}
    </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
      <div class="action-link">
          {crmButton q="action=add&reset=1" id="newOptionGroup"  icon="plus-circle"}{ts}Add Option Group{/ts}{/crmButton}
          {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
      </div>
    {/if}
</div>
{elseif $action ne 1 and $action ne 2}
    <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {capture assign=crmURL}{crmURL p='civicrm/admin/optionGroup' q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Option Groups entered. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
