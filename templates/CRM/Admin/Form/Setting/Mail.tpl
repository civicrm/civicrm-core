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
{capture assign=docLink}{docURL page="CiviMail Mailer Settings" text="CiviMail Mailer Settings and Optimization" resource="wiki"}{/capture}
<div class="crm-block crm-form-block crm-mail-form-block">
<div class="help">
    {ts 1=$docLink}These settings are used to configure mailer properties for the optional CiviMail component and may allow you to significantly optimize performance. Please read the %1 documentation, and make sure you understand it before modifying default values. (These settings are NOT used for the built-in 'Email - send now' feature).{/ts}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      <table class="form-layout-compressed">
        <tr class="crm-mail-form-block-mailerBatchLimit">
            <td class="label">{$form.mailerBatchLimit.label}</td><td>{$form.mailerBatchLimit.html}<br />
            <span class="description">{ts}Throttle email delivery by setting the maximum number of emails sent during each CiviMail run (0 = unlimited).{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-mailThrottleTime">
          <td class="label">{$form.mailThrottleTime.label}</td><td>{$form.mailThrottleTime.html} <br />
          <span class="description">{ts}The time to sleep in between each e-mail in micro seconds. Setting this above 0 allows you to control the rate at which e-mail messages are sent to the mail server, avoiding filling up the mail queue very quickly. Set to 0 to disable.{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-mailerJobSize">
            <td class="label">{$form.mailerJobSize.label}</td><td>{$form.mailerJobSize.html}<br />
            <span class="description">{ts}If you want to utilize multi-threading enter the size you want your sub jobs to be split into. Recommended values are between 1,000 and 10,000. Use a lower value if your server has multiple cron jobs running simultaneously, but do not use values smaller than 1,000. Enter "0" to disable multi-threading and process mail as one single job - batch limits still apply.{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-mailerJobsMax">
            <td class="label">{$form.mailerJobsMax.label}</td><td>{$form.mailerJobsMax.html}<br />
            <span class="description">{ts}The maximum number of mailer delivery jobs executing simultaneously (0 = allow as many processes to execute as started by cron){/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-verpSeparator">
            <td class="label">{$form.verpSeparator.label}</td><td>{$form.verpSeparator.html}<br />
            <span class="description">{ts}Separator character used when CiviMail generates VERP (variable envelope return path) Mail-From addresses.{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-replyTo">
            <td class="label">{$form.replyTo.label}</td><td>{$form.replyTo.html}<br />
            <span class="description">{ts}Allow CiviMail users to send mailings with a custom Reply-To header.{/ts}</span></td>
        </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
</div>
