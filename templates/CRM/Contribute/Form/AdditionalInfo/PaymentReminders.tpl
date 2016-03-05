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
{* this template is used for adding/editing Payment Reminders Information *}
 <div id="id-paymentReminders" class="section-shown crm-contribution-additionalinfo-payment_reminders-form-block">
      <table class="form-layout-compressed">
        <tr class="crm-contribution-form-block-initial_reminder_day"><td class="label">{$form.initial_reminder_day.label}</td><td>{$form.initial_reminder_day.html} {help id="id-payment-reminders"}</td></tr>
        <tr><td class="label">&nbsp;</td><td class="description">{ts}Days prior to each scheduled payment due date.{/ts}</td></tr>
        <tr class="crm-contribution-form-block-max_reminders"><td class="label">{$form.max_reminders.label}</td><td>{$form.max_reminders.html}</td></tr>
        <tr><td class="label">&nbsp;</td><td class="description">{ts}Reminders for each scheduled payment.{/ts}</td></tr>
        <tr class="crm-contribution-form-block-additional_reminder_day"><td class="label">{$form.additional_reminder_day.label}</td><td>{$form.additional_reminder_day.html}</td></tr>
      <tr><td class="label">&nbsp;</td><td class="description">{ts}Days after the last one sent, up to the maximum number of reminders.{/ts}</td></tr>
      </table>
 </div>
