{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-miscellaneous-form-block">
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

    <table class="form-layout">
      <tr class="crm-miscellaneous-form-block-checksum_timeout">
        <td class="label">{$form.checksum_timeout.label}</td>
        <td>{$form.checksum_timeout.html}<br />
            <span class="description">{ts}The number of days before a personalized (hashed) link will expire.{/ts}</span></td>
      </tr>
    </table>

    <table class="form-layout">
      <tr class="crm-miscellaneous-form-block-contact_undelete">
        <td class="label">{$form.contact_undelete.label}</td>
        <td>
          {$form.contact_undelete.html}<br />
          <p class="description">{ts}{$contact_undelete_description}{/ts}</p>
        </td>
      </tr>
      <tr class="crm-miscellaneous-form-block-logging">
        <td class="label">{$form.logging.label}</td>
        <td>
          {$form.logging.html}<br />
        {if $validTriggerPermission}
          {if $isMultilingual}
            <p class="description">{ts}Logging is not supported in multilingual environments.{/ts}</p>
          {else}
            <p class="description">{ts}If enabled, all actions will be logged with a complete record of changes.{/ts}</p>
          {/if}
        {else}
          <p class="description">{ts}In order to use this functionality, the installation's database user must have privileges to create triggers (in MySQL 5.0 – and in MySQL 5.1 if binary logging is enabled – this means the SUPER privilege). This install either does not seem to have the required privilege enabled.{/ts}&nbsp;{ts}This functionality cannot be enabled on multilingual installations.{/ts}</p>
        {/if}
        </td>
      </tr>
      <tr class="crm-miscellaneous-form-block-doNotAttachPDFReceipt">
        <td class="label">{$form.doNotAttachPDFReceipt.label}</td>
        <td>{$form.doNotAttachPDFReceipt.html}<br />
          <p class="description">{ts}If enabled, CiviCRM sends PDF receipt as an attachment during event signup or online contribution.{/ts}</p>
        </td>
      </tr>
      <tr class="crm-miscellaneous-form-block-recordGeneratedLetters">
        <td class="label">{$form.recordGeneratedLetters.label}</td>
        <td>{$form.recordGeneratedLetters.html}<br />
          <p class="description">{ts}When generating a letter (PDF/Word) via mail-merge, how should the letter be recorded?{/ts}</p>
        </td>
      </tr>
      <tr class="crm-miscellaneous-form-block-wkhtmltopdfPath">
        <td class="label">{$form.wkhtmltopdfPath.label}</td>
        <td>{$form.wkhtmltopdfPath.html}<br />
          <p class="description">{ts 1="http://wkhtmltopdf.org/"}<a href="%1">wkhtmltopdf is an alternative utility for generating PDF's</a> which may provide better performance especially if you are generating a large number of PDF letters or receipts. Your system administrator will need to download and install this utility, and enter the executable path here.{/ts}</p>
        </td>
      </tr>
      {foreach from=$pure_config_settings item=setting_name}
        <tr class="crm-miscellaneous-form-block-{$setting_name}">
          <td class="label">{$form.$setting_name.label}</td>
          <td>{$form.$setting_name.html}<br />
            <span class="description">{$setting_descriptions.$setting_name}</span>
          </td>
        </tr>
      {/foreach}
      <tr class="crm-miscellaneous-form-block-remote_profile_submissions_allowed">
        <td class="label">{$form.remote_profile_submissions.label}</td>
        <td>{$form.remote_profile_submissions.html}<br />
          <p class="description">{ts}If enabled, CiviCRM will allow users to submit profiles from external sites. This is disabled by default to limit abuse.{/ts}</p>
        </td>
      </tr>
      <tr class="crm-miscellaneous-form-block-allow_alert_autodismissal">
        <td class="label">{$form.allow_alert_autodismissal.label}</td>
        <td>{$form.allow_alert_autodismissal.html}<br />
          <p class="description">{ts}If disabled, CiviCRM will not automatically dismiss any alerts after 10 seconds.{/ts}</p>
        </td>
      </tr>
    </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
