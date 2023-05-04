{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{include file="CRM/common/enableDisableApi.tpl"}
{strip}
  <table class="selector row-highlight">
    <tr class="columnheader">
      <th scope="col">{ts}Amount{/ts}</th>
      <th scope="col">{ts}Frequency{/ts}</th>
      <th scope="col">
        {if $recurType EQ 'active'}{ts}Next Scheduled Date{/ts}
        {elseif $recurType EQ 'inactive'}{ts}End or Modified Date{/ts}
        {else}{ts}Start Date{/ts}
        {/if}
      </th>
      <th scope="col">{ts}Installments{/ts}</th>
      <th scope="col">{ts}Payment Processor{/ts}</th>
      <th scope="col">{ts}Status{/ts}</th>
      <th scope="col"></th>
    </tr>

    {foreach from=$recurRows item=row}
      {assign var=id value=$row.id}
      <tr id="contribution_recur-{$row.id}" data-action="cancel" class="crm-entity {cycle values="even-row,odd-row"}{if NOT $row.is_active} disabled{/if}">
        <td>{$row.amount|crmMoney:$row.currency}{if $row.is_test} ({ts}test{/ts}){/if}</td>
        <td>{ts}Every{/ts} {$row.frequency_interval} {$row.frequency_unit} </td>
        <td>
          {if $recurType EQ 'active'}{$row.next_sched_contribution_date|crmDate}
          {elseif $recurType EQ 'inactive'}
            {if $row.cancel_date}{$row.cancel_date|crmDate}
            {elseif $row.end_date}{$row.end_date|crmDate}
            {else}{$row.modified_date|crmDate}
            {/if}
          {else}{$row.start_date|crmDate}
          {/if}
        </td>
        <td>{$row.installments}</td>
        <td>{$row.payment_processor}</td>
        <td>{$row.contribution_status}</td>
        <td>{$row.action|replace:'xx':$row.recurId}</td>
      </tr>
    {/foreach}
  </table>
{/strip}
