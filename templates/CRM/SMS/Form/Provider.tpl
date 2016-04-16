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
{* This template is used for adding/configuring SMS Providers  *}
<div class="crm-block crm-form-block crm-job-form-block">
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

{if $action eq 8}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts}Do you want to continue?{/ts}
  </div>
{elseif $action eq 128}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
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
