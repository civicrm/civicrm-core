{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{* template for adding form elements for soft credit form*}

<table class="form-layout-compressed crm-soft-credit-block">
  {section name='i' start=1 loop=$rowCount}
    {assign var='rowNumber' value=$smarty.section.i.index}
    <tr id="soft-credit-row-{$rowNumber}"
        class="crm-contribution-form-block-soft_credit_to {if $rowNumber gte $showSoftCreditRow}hiddenElement{/if}">
      <td class="label">{ts}Select Contact{/ts}</td>
      <td>
        {assign var='createNewStatus' value=true}
        {if !$showCreateNew and $rowNumber lt $showSoftCreditRow}
          {assign var='createNewStatus' value=false}
        {/if}
        {include file="CRM/Contact/Form/NewContact.tpl" noLabel=true skipBreak=true blockNo=$rowNumber
        prefix="soft_credit_" showNewSelect=$createNewStatus}
      </td>
      <td>
        {$form.soft_credit_amount.$rowNumber.label}&nbsp;{$form.soft_credit_amount.$rowNumber.html|crmAddClass:eight}
        &nbsp;<a class="delete-link" row-no={$rowNumber} href="#">{ts}delete{/ts}</a>
      </td>
    </tr>
  {/section}
  <tr>
    <td></td>
    <td>
      <a href="#" id="addMoreSoftCredit">{ts}add another soft credit{/ts}</a>
    </td>
  </tr>
</table>
