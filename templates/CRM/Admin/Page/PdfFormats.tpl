{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/PdfFormats.tpl"}
{else}
  <div class="help">
    {capture assign="messageTemplatesURL"}{crmURL p="civicrm/admin/messageTemplates" q="reset=1"}{/capture}
    {ts 1=$messageTemplatesURL}You can configure one or more PDF Page Formats for your CiviCRM installation. PDF Page Formats may be assigned to <strong><a href="%1">Message Templates</a></strong> to use when creating PDF letters.{/ts}
  </div>
{if $rows}
    <div id="ltype">
        {strip}
        <table id="pdfFormats" class="row-highlight">
        <thead>
        <tr class="columnheader">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th >{ts}Default?{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th ></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="crm-pdfFormat {cycle values="odd-row,even-row"} {$row.class}">
            <td class="crm-pdfFormat-name">{$row.name}</td>
            <td class="crm-pdfFormat-description">{$row.description}</td>
            <td class="crm-pdfFormat-is_default">{if $row.is_default eq 1}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>
          <td class="crm-pdfFormat-order nowrap">{$row.weight}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}
    </div>
{else}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {ts}None found.{/ts}
    </div>
{/if}
    <div class="spacer"></div>
    <div class="action-link">
      {crmButton q="action=add&reset=1" id="newPdfFormat"  icon="plus-circle"}{ts}Add PDF Page Format{/ts}{/crmButton}
      {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>
{/if}
