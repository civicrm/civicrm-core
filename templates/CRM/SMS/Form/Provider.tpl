{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This template is used for adding/configuring SMS Providers  *}
<div class="crm-block crm-form-block crm-job-form-block">
{if $action eq 8}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}Do you want to continue?{/ts}
  </div>
{elseif $action eq 128}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}Are you sure you would like to execute this job?{/ts}
  </div>
{else}
  <table class="form-layout-compressed">
    <tr class="crm-job-form-block-name">
        <td class="label">{$form.name.label}</td><td>{$form.name.html}</td>
    </tr>
    <tr class="crm-job-form-block-title">
        <td class="label">{$form.title.label}</td><td>{$form.title.html}</td>
    </tr>
    <tr class="crm-job-form-block-username">
        <td class="label">{$form.username.label}</td><td>{$form.username.html}</td>
    </tr>
    <tr class="crm-job-form-block-password">
        <td class="label">{$form.password.label}</td><td>{$form.password.html}</td>
    </tr>
    <tr class="crm-job-form-block-api_type">
        <td class="label">{$form.api_type.label}</td><td>{$form.api_type.html}</td>
    </tr>
    <tr class="crm-job-form-block-api_url">
        <td class="label">{$form.api_url.label}</td><td>{$form.api_url.html}</td>
    </tr>
    <tr class="crm-job-form-block-api_params">
        <td class="label">{$form.api_params.label}</td><td>{$form.api_params.html}</td>
    </tr>
    <tr class="crm-job-form-block-is_active">
        <td></td><td>{$form.is_active.html}&nbsp;{$form.is_active.label}</td>
    </tr>
   <tr class="crm-job-form-block-is_active">
        <td></td><td>{$form.is_default.html}&nbsp;{$form.is_default.label}</td>
   </tr>
  </table>
{/if}
</table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </fieldset>
</div>

{if $action eq 1  or $action eq 2}
<script type="text/javascript" >
{literal}
  CRM.$(function($) {
    var $form = $("form.{/literal}{$form.formClass}{literal}");
    $('select[name=name]', $form).change(function() {
      var url = {/literal}"{$refreshURL}"{literal} + "&key=" + this.value;
      $(this).closest('.crm-ajax-container, #crm-main-content-wrapper').crmSnippet({url: url}).crmSnippet('refresh');
    });
  });
{/literal}
</script>
{/if}
