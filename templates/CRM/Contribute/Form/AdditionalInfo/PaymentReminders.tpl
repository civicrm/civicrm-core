{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
