{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

{include file="CRM/common/enableDisableApi.tpl"}

{if $action eq 4} {* when action is view *}
    {if $recur}
        <h3>{ts}View Recurring Payment{/ts}</h3>
        <div class="crm-block crm-content-block crm-recurcontrib-view-block">
          <table class="crm-info-panel">
            <tr><td class="label">{ts}Amount{/ts}</td><td>{$recur.amount|crmMoney:$recur.currency}{if $is_test} ({ts}test{/ts}){/if}</td></tr>
            <tr><td class="label">{ts}Frequency{/ts}</td><td>every {$recur.frequency_interval} {$recur.frequency_unit}</td></tr>
            <tr><td class="label">{ts}Installments{/ts}</td><td>{$recur.installments}</td></tr>
            <tr><td class="label">{ts}Status{/ts}</td><td>{$recur.contribution_status}</td></tr>
            <tr><td class="label">{ts}Start Date{/ts}</td><td>{$recur.start_date|crmDate}</td></tr>
            <tr><td class="label">{ts}Created Date{/ts}</td><td>{$recur.create_date|crmDate}</td></tr>
            {if $recur.modified_date}<tr><td class="label">{ts}Modified Date{/ts}</td><td>{$recur.modified_date|crmDate}</td></tr>{/if}
            {if $recur.cancel_date}<tr><td class="label">{ts}Cancelled Date{/ts}</td><td>{$recur.cancel_date|crmDate}</td></tr>{/if}
            {if $recur.cancel_date}<tr><td class="label">{ts}End Date{/ts}</td><td>{$recur.end_date|crmDate}</td></tr>{/if}
            {if $recur.processor_id}<tr><td class="label">{ts}Processor ID{/ts}</td><td>{$recur.processor_id}</td></tr>{/if}
            <tr><td class="label">{ts}Transaction ID{/ts}</td><td>{$recur.trxn_id}</td></tr>
            {if $recur.invoice_id}<tr><td class="label">{ts}Invoice ID{/ts}</td><td>{$recur.invoice_id}</td></tr>{/if}
            <tr><td class="label">{ts}Cycle Day{/ts}</td><td>{$recur.cycle_day}</td></tr>
            {if $recur.contribution_status_id neq 3}<tr><td class="label">{ts}Next Contribution{/ts}</td><td>{$recur.next_sched_contribution_date|crmDate}</td></tr>{/if}
            <tr><td class="label">{ts}Failure Count{/ts}</td><td>{$recur.failure_count}</td></tr>
            {if $recur.invoice_id}<tr><td class="label">{ts}Failure Retry Date{/ts}</td><td>{$recur.next_sched_contribution_date|crmDate}</td></tr>{/if}
            <tr><td class="label">{ts}Auto Renew?{/ts}</td><td>{if $recur.auto_renew}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td></tr>
            {if $recur.payment_processor}<tr><td class="label">{ts}Payment Processor{/ts}</td><td>{$recur.payment_processor}</td></tr>{/if}
            {if $recur.financial_type}<tr><td class="label">{ts}Financial Type{/ts}</td><td>{$recur.financial_type}</td></tr>{/if}
            {if $recur.campaign}<tr><td class="label">{ts}Campaign{/ts}</td><td>{$recur.campaign}</td></tr>{/if}
          </table>
          <div class="crm-submit-buttons"><a class="button cancel crm-form-submit" href="{crmURL p='civicrm/contact/view' q='action=browse&selectedChild=contribute'}">{ts}Done{/ts}</a></div>
        </div>
    {/if}
{/if}
{if $recurRows}
    {strip}
    <table class="selector row-highlight">
        <tr class="columnheader">
            <th scope="col">{ts}Amount{/ts}</th>
            <th scope="col">{ts}Frequency{/ts}</th>
            <th scope="col">{ts}Start Date{/ts}</th>
            <th scope="col">{ts}Installments{/ts}</th>
            <th scope="col">{ts}Status{/ts}</th>
            <th scope="col">&nbsp;</th>
        </tr>

        {foreach from=$recurRows item=row}
            {assign var=id value=$row.id}
            <tr id="contribution_recur-{$row.id}" data-action="cancel" class="crm-entity {cycle values="even-row,odd-row"}{if NOT $row.is_active} disabled{/if}">
                <td>{$row.amount|crmMoney:$row.currency}{if $row.is_test} ({ts}test{/ts}){/if}</td>
                <td>{ts}Every{/ts} {$row.frequency_interval} {$row.frequency_unit} </td>
                <td>{$row.start_date|crmDate}</td>
                <td>{$row.installments}</td>
                <td>{$row.contribution_status}</td>
                <td>
                    {$row.action|replace:'xx':$row.recurId}
                </td>
            </tr>
        {/foreach}
    </table>
    {/strip}
{/if}
