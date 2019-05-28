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
