{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=docLink}{docURL page="CiviMail Mailer Settings" text="CiviMail Mailer Settings and Optimization" resource="wiki"}{/capture}
<div class="help">
    {ts 1=$docLink}These settings are used to configure mailer properties for the optional CiviMail component and may allow you to significantly optimize performance. Please read the %1 documentation, and make sure you understand it before modifying default values. (These settings are NOT used for the built-in 'Email - send now' feature).{/ts}
  </div>
<div class="crm-block crm-form-block crm-mail-form-block">
  {include file='CRM/Admin/Form/Setting/SettingForm.tpl'}
</div>
