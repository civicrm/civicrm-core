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
    {ts}These settings define the CMS variables that are used with CiviCRM.{/ts}
</div>
<div class="crm-block crm-form-block crm-uf-form-block">
      <table class="form-layout-compressed">
        {if $userFrameworkUsersTableNameEnabled}
        <tr class="crm-uf-form-block-userFrameworkUsersTableName">
            <td class="label">{$form.userFrameworkUsersTableName.label}</td>
            <td>{$form.userFrameworkUsersTableName.html}</td>
        </tr>
        {/if}
        {if $wpBasePageEnabled}
        <tr class="crm-uf-form-block-wpBasePage">
            <td class="label">{$form.wpBasePage.label}</td>
            <td>{$config->userFrameworkBaseURL}{$form.wpBasePage.html}
            <p class="description">{ts 1=$config->userFrameworkBaseURL}By default, CiviCRM will generate front-facing pages using the home page at %1 as its base. If you want to use a different template for CiviCRM pages, set the path here.{/ts}</p>
            </td>
        </tr>
        {/if}
        <tr class="crm-uf-form-block-syncCMSEmail">
           <td class="label">{$form.syncCMSEmail.label}</td>
           <td>{$form.syncCMSEmail.html}</td>
       </tr>
        </table>
            <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
{if $viewsIntegration}
<div class="form-item">
<fieldset>
    <legend>{ts}Views integration settings{/ts}</legend>
    <div>{$viewsIntegration}</div>
</fieldset>
</div>
{/if}
</div>
