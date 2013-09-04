{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{* Admin page for browsing Option Group *}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/OptionGroup.tpl"}
{else}
<div id="help">
    {ts}CiviCRM stores configurable choices for various drop-down fields as 'option groups'. You can click <strong>Options</strong> to view the available choices.{/ts}
    <p><div class="icon alert-icon"></div> {ts}WARNING: Many option groups are used programatically and values should be added or modified with caution.{/ts}</p>
</div>
{/if}

{if $rows}

<div id="browseValues">
    {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisable.tpl"}
    <table cellpadding="0" cellspacing="0" border="0">
        <tr class="columnheader">
        <th>{ts}Title{/ts}</th>
        <th>{ts}Name{/ts}</th>
        <th></th>
        </tr>
        {foreach from=$rows item=row}
      <tr id="row_{$row.id}"class="crm-admin-optionGroup {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td class="crm-admin-optionGroup-title">{if $row.title}{$row.title}{else}( {ts}none{/ts} ){/if}</td>
            <td class="crm-admin-optionGroup-name">{$row.name}</td>
            <td><a href="{crmURL p="civicrm/admin/optionValue" q="gid=`$row.id`&reset=1"}" title="{ts}View and Edit Options{/ts}">{ts}Options{/ts}</a></td>
        </tr>
        {/foreach}
    </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
      <div class="action-link">
          <a href="{crmURL q="action=add&reset=1"}" id="newOptionGroup" class="button"><span><div class="icon add-icon"></div>{ts}Add Option Group{/ts}</span></a>
        </div>
    {/if}
</div>
{elseif $action NEQ 1 && $action NEQ 2}
    <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {capture assign=crmURL}{crmURL p='civicrm/admin/optionGroup' q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Option Groups entered. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
