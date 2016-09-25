{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
  <div class="icon inform-icon"></div>&nbsp;
  {if $mode eq 'auto_renew'}
      {ts}Use this form to update the credit card and billing name and address used with the auto-renewal option for your {$membershipType} membership.{/ts}
  {else}
    <strong>{ts 1=$amount|crmMoney 2=$frequency_interval 3=$frequency_unit}Recurring Contribution Details: %1 every %2 %3{/ts}
    {if $installments}
      {ts 1=$installments}for %1 installments{/ts}.
    {/if}</strong>
    <div class="content">{ts}Use this form to update the credit card and billing name and address used for this recurring contribution.{/ts}</div>
  {/if}
</div>

{include file="CRM/Core/BillingBlockWrapper.tpl"}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
