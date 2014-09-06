{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
{* this template is for configuring label formats *}
<div class="help">
  {ts}You can configure one or more Label Formats for your CiviCRM installation. Label Formats are used when creating mailing labels.{/ts}
</div>
{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 16384}
  {include file="CRM/Admin/Form/LabelFormats.tpl"}
{else}

  {if $rows}
    <div id="ltype">
      {strip}
        <table id="labelFormats" class="row-highlight">
          <thead>
          <tr class="columnheader">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Used for{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Grouping{/ts}</th>
            <th>{ts}Default?{/ts}</th>
            <th>{ts}Reserved?{/ts}</th>
            <th></th>
          </tr>
          </thead>
          {foreach from=$rows item=row}
            <tr id="row_{$row.id}" class="crm-labelFormat {cycle values="odd-row,even-row"} {$row.class}">
              <td class="crm-labelFormat-name">{$row.label}</td>
              <td class="crm-labelFormat-name">{$row.groupName}</td>
              <td class="crm-labelFormat-order nowrap">{$row.weight}</td>
              <td class="crm-labelFormat-description">{$row.grouping}</td>
              <td class="crm-labelFormat-is_default">{if $row.is_default eq 1}<img
                src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}"/>{/if}&nbsp;</td>
              <td class="crm-labelFormat-is_reserved">{if $row.is_reserved eq 1}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}
                &nbsp;</td>
              <td>{$row.action|replace:'xx':$row.id}</td>
            </tr>
          {/foreach}
        </table>
      {/strip}

      <div class="action-link">
        <a href="{crmURL q="action=add&reset=1"}" id="newLabelFormat" class="button"><span><div
              class="icon add-icon"></div>{ts}Add Label Format{/ts}</span></a>
      </div>
    </div>
  {else}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {capture assign=crmURL}{crmURL p='civicrm/admin/labelFormats' q="action=add&reset=1"}{/capture}
      {ts 1=$crmURL}There are no Label Formats configured. You can<a href='%1'>add one</a>.{/ts}
    </div>
  {/if}
{/if}
