{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{strip}
  <table class="selector row-highlight">
    <tr class="columnheader">
      <th scope="col">{ts}Amount{/ts}</th>
      <th scope="col">{ts}Frequency{/ts}</th>
      <th scope="col">{ts}Start Date{/ts}</th>
      <th scope="col">{ts}Installments{/ts}</th>
      <th scope="col">{ts}Status{/ts}</th>
      <th scope="col"></th>
    </tr>

    {foreach from=$recurRows item=row}
      {assign var=id value=$row.id}
      <tr id="contribution_recur-{$row.id}" data-action="cancel" class="crm-entity {cycle values="even-row,odd-row"}{if NOT $row.is_active} disabled{/if}">
        <td>{$row.amount|crmMoney:$row.currency}{if $row.is_test} ({ts}test{/ts}){/if}</td>
        <td>{ts}Every{/ts} {$row.frequency_interval} {$row.frequency_unit} </td>
        <td>{$row.start_date|crmDate}</td>
        <td>{$row.installments}</td>
        <td>{$row.contribution_status}</td>
        <td>{$row.action|replace:'xx':$row.recurId}</td>
      </tr>
    {/foreach}
  </table>
{/strip}
