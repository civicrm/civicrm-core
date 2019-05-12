{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
<div class="help">
    {ts}You can configure one or more Payment Processors for your CiviCRM installation. You must then assign an active Payment Processor to each <strong>Online Contribution Page</strong> and each paid <strong>Event</strong>.{/ts} {help id='proc-type'}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/PaymentProcessor.tpl"}
{else}

<div class="crm-content-block crm-block">
{if $rows}
<div id="ltype">
        {strip}
        {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
        <table class="selector row-highlight">
        <tr class="columnheader">
            <th>{ts}ID{/ts}</th>
            <th>{ts}Test ID{/ts}</th>
            <th>{ts}Name{/ts}</th>
            <th>{ts}Processor Type{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Financial Account{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>{ts}Default?{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="payment_processor-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td class="crmf-id center">{$row.id}</td>
            <td class="crmf-test_id center">{$row.test_id}</td>
            <td class="crmf-name">{$row.name}</td>
            <td class="crmf-payment_processor_type">{$row.payment_processor_type}</td>
            <td class="crmf-description">{$row.description}</td>
            <td class="crmf-financial_account_id">{$row.financialAccount}</td>
            <td class="crmf-is_active center">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crmf-is_default center">
              {if $row.is_default eq 1}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}"/>{/if}&nbsp;
            </td>
            <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
        <div class="action-link">
          {crmButton q="action=add&reset=1&pp=$defaultPaymentProcessorType" id="newPaymentProcessor"  icon="plus-circle"}{ts}Add Payment Processor{/ts}{/crmButton}
        </div>
        {/if}
</div>
{elseif $action ne 1}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts}There are no Payment Processors entered.{/ts}
     </div>
     <div class="action-link">
       {crmButton p='civicrm/admin/paymentProcessor' q="action=add&reset=1&pp=$defaultPaymentProcessorType" id="newPaymentProcessor"  icon="plus-circle"}{ts}Add Payment Processor{/ts}{/crmButton}
     </div>
{/if}
</div>

{/if}
