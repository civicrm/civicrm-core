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
    {ts}Payment Processor configurations for all payment processors that can be used in this install of CiviCRM.{/ts}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/PaymentProcessorType.tpl"}
{else}

{if $rows}
<div id="ltype">
<p></p>
    <div class="form-item">
        {strip}
  {include file="CRM/common/enableDisable.tpl"}
        <table cellpadding="0" cellspacing="0" border="0">
        <tr class="columnheader">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Title{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
          <th>{ts}Default?{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class} crm-paymentProcessorType {if NOT $row.is_active} disabled{/if}">
          <td class="crm-paymentProcessorType-name">{$row.name}</td>
          <td class="crm-paymentProcessorType-title">{$row.title}</td>
            <td class="crm-paymentProcessorType-description">{$row.description}</td>
          <td id="row_{$row.id}_status" class="crm-paymentProcessorType-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-paymentProcessorType-is_default">{if $row.is_default eq 1}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>
          <td>{$row.action}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
      <div class="action-link">
      <a href="{crmURL q="action=add&reset=1"}" id="newPaymentProcessor">&raquo; {ts}New Payment Processor{/ts}</a>
        </div>
        {/if}
    </div>
</div>
{elseif $action ne 1}
    <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {capture assign=crmURL}{crmURL p='civicrm/admin/paymentProcessorType' q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Payment Processors entered. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
{/if}