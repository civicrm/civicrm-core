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
  {include file='CRM/Admin/Form/Setting/SettingForm.tpl'}

  {if $viewsIntegration}
    <div class="spacer"></div>
    <div class="form-item">
      <fieldset>
        <legend>{ts}Views integration settings{/ts}</legend>
        <div>{$viewsIntegration}</div>
      </fieldset>
    </div>
  {/if}
</div>
