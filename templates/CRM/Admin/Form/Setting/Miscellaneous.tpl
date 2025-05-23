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
    <table class="form-layout">
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
      <tr class="crm-miscellaneous-form-block-weasyprint_path">
        <td class="label">{$form.weasyprint_path.label}</td>
        <td>{$form.weasyprint_path.html}<br />
          <p class="description">{ts 1="https://weasyprint.org/"}<a href="%1">weasyprint is an alternative utility for generating PDFs</a> which is a successor to the discontinued wkhtmltopdf. Your system administrator will need to download and install this utility, and enter the executable path here.{/ts}</p>
        </td>
      </tr>
      <tr class="crm-miscellaneous-form-block-wkhtmltopdfPath">
        <td class="label">{$form.wkhtmltopdfPath.label}</td>
        <td>{$form.wkhtmltopdfPath.html}<br />
          <p class="description">{ts 1="http://wkhtmltopdf.org/"}<a href="%1">wkhtmltopdf is an alternative utility for generating PDFs</a> which may provide better performance especially if you are generating a large number of PDF letters or receipts. Your system administrator will need to download and install this utility, and enter the executable path here.{/ts}</p>
        </td>
      </tr>
      {include file="CRM/Admin/Form/Setting/SettingForm.tpl"}
     </table>
</div>
