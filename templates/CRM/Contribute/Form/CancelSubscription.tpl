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

<div class="crm-block crm-form-block crm-auto-renew-membership-cancellation">
<div class="help">
  <div class="icon inform-icon"></div>&nbsp;
  {if $mode eq 'auto_renew'}
      {ts}Click the button below if you want to cancel the auto-renewal option for your {$membershipType} membership. This will not cancel your membership. However you will need to arrange payment for renewal when your membership expires.{/ts}
  {else}
      <strong>{ts 1=$amount|crmMoney 2=$frequency_interval 3=$frequency_unit}Recurring Contribution Details: %1 every %2 %3{/ts}
      {if $installments}
        {ts 1=$installments}for %1 installments{/ts}.
      {/if}</strong>
      <div class="content">{ts}Click the button below to cancel this commitment and stop future transactions. This does not affect contributions which have already been completed.{/ts}</div>
  {/if}
  {if !$cancelSupported}
    <div class="status-warning">
      {ts}Automatic cancellation is not supported for this payment processor. You or the contributor will need to manually cancel this recurring contribution using the payment processor website.{/ts}
    </div>
  {/if}
</div>
{if !$self_service}
<table class="form-layout">
   <tr>
      <td class="label">{$form.send_cancel_request.label}</td>
      <td class="html-adjust">{$form.send_cancel_request.html}</td>
   </tr>
   <tr>
      <td class="label">{$form.is_notify.label}</td>
      <td class="html-adjust">{$form.is_notify.html}</td>
   </tr>
</table>
{/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
</div>
