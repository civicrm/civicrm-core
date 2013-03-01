{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
{* this template is for configuring PDF Page Formats *}
<div class="help">
    {capture assign="messageTemplatesURL"}{crmURL p="civicrm/admin/messageTemplates" q="reset=1"}{/capture}
    {ts 1=$messageTemplatesURL}You can configure one or more PDF Page Formats for your CiviCRM installation. PDF Page Formats may be assigned to <strong><a href="%1">Message Templates</a></strong> to use when creating PDF letters.{/ts}
</div>
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/PdfFormats.tpl"}
{else}

{if $rows}
    <div id="ltype">
        {strip}
        {include file="CRM/common/jsortable.tpl"}
        <table id="pdfFormats" class="display">
        <thead>
        <tr class="columnheader">
            <th  class="sortable">{ts}Name{/ts}</th>
            <th id="nosort">{ts}Description{/ts}</th>
            <th >{ts}Default?{/ts}</th>
            <th id="order" class="sortable">{ts}Order{/ts}</th>
            <th class="hiddenElement"></th>
            <th ></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="crm-pdfFormat {cycle values="odd-row,even-row"} {$row.class}">
            <td class="crm-pdfFormat-name">{$row.name}</td>
            <td class="crm-pdfFormat-description">{$row.description}</td>
            <td class="crm-pdfFormat-is_default">{if $row.is_default eq 1}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>
          <td class="crm-pdfFormat-order nowrap">{$row.order}</td>
          <td class="order hiddenElement">{$row.weight}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}
    </div>
{else}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {capture assign=crmURL}{crmURL p='civicrm/admin/pdfFormats' q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no PDF Page Formats configured. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
    <div class="spacer"></div>
    <div class="action-link">
        <a href="{crmURL q="action=add&reset=1"}" id="newPdfFormat" class="button"><span><div class="icon add-icon"></div>{ts}Add PDF Page Format{/ts}</span></a>
    </div>
{/if}
