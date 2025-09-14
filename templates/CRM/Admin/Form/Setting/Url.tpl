{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  <p>
    {ts}These settings define the URLs used to access CiviCRM resources (CSS files, Javascript files, images, etc.).{/ts}
  </p>
  <p>
    {ts}You may configure these settings using absolute URLs or URL variables.{/ts}
    {help id='id-url_vars'}
  </p>
</div>
<div class="crm-block crm-form-block crm-url-form-block">
  {include file="CRM/Admin/Form/Setting/SettingForm.tpl"}
</div>
