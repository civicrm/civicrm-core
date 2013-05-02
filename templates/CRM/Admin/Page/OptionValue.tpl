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
{* Admin page for browsing Option Group value*}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/OptionValue.tpl"}
{else}
<div id="help">
    {ts}The existing option choices for this option group are listed below. You can add, edit or delete them from this screen.{/ts}
</div>
{/if}

{if $rows}

<div id="browseValues">
    {strip}
   {* handle enable/disable actions*}
    {include file="CRM/common/enableDisable.tpl"}
    {include file="CRM/common/jsortable.tpl"}
         <table id="options" class="display">
         <thead>
         <tr>
            <th>{ts}Title{/ts}</th>
            <th>{ts}Value{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Weight{/ts}</th>
           {if $showIsDefault}
            <th>{ts}Default{/ts}</th>
           {/if}
            <th>{ts}Reserved?{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
      <tr id="row_{$row.id}"class="crm-admin-optionValue {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td class="crm-admin-optionValue-label">{$row.label}</td>
            <td class="crm-admin-optionValue-value">{$row.value}</td>
            <td class="crm-admin-optionValue-description">{$row.description}</td>
            <td class="nowrap crm-admin-optionValue-weight">{$row.weight}</td>
           {if $showIsDefault}
            <td class="crm-admin-optionValue-default_value">{$row.default_value}</td>
           {/if}
            <td class="crm-admin-optionValue-is_reserved">{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td id="row_{$row.id}_status" class="crm-admin-optionValue-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
    {/strip}

    {if $action eq 16}
      <div class="action-link">
          <a href="{crmURL q="action=add&reset=1&gid=$gid"}" id="newOptionValue" class="button"><span><div class="icon add-icon"></div>{ts}Add Option Value{/ts}</span></a>
        </div>
    {/if}
</div>
{elseif $action eq 16}
    <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {capture assign=crmURL}{crmURL p='civicrm/admin/optionValue' q="action=add&reset=1&gid=$gid"}{/capture}
        {ts 1=$crmURL}There are no option choices entered for this option group. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
