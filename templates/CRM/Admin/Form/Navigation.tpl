{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Template for adding/editing a CiviCRM Navigation Menu Item *}
<div class="crm-block crm-form-block crm-navigation-form-block">
  <table class="form-layout-compressed">
    <tr class="crm-navigation-form-block-label">
      <td class="label">{$form.label.label}</td><td>{$form.label.html}</td>
    </tr>
    <tr class="crm-navigation-form-block-url">
      <td class="label">{$form.url.label} {help id="id-menu_url" file="CRM/Admin/Form/Navigation.hlp"}</td>
      <td>{$form.url.html} </td>
    </tr>
    <tr class="crm-navigation-form-block-icon">
      <td class="label">{$form.icon.label} {help id="id-menu_icon" file="CRM/Admin/Form/Navigation.hlp"}</td>
      <td>{$form.icon.html} </td>
    </tr>
    {if !empty($form.parent_id.html)}
      <tr class="crm-navigation-form-block-parent_id">
        <td class="label">{$form.parent_id.label} {help id="id-parent" file="CRM/Admin/Form/Navigation.hlp"}</td>
        <td>{$form.parent_id.html}</td>
      </tr>
    {/if}
    <tr class="crm-navigation-form-block-has_separator">
      <td class="label">{$form.has_separator.label} {help id="id-has_separator" file="CRM/Admin/Form/Navigation.hlp"}</td>
      <td>{$form.has_separator.html} </td>
    </tr>
    <tr class="crm-navigation-form-block-permission">
      <td class="label">{$form.permission.label} {help id="id-menu_permission" file="CRM/Admin/Form/Navigation.hlp"}</td>
      <td>{$form.permission.html} <span class="permission_operator_wrapper">{$form.permission_operator.html}  {help id="id-permission_operator" file="CRM/Admin/Form/Navigation.hlp"}</span></td>
    </tr>
    <tr class="crm-navigation-form-block-is_active">
      <td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}');
    $('input[name=permission]', $form)
      .on('change', function() {
        $('span.permission_operator_wrapper').toggle(CRM._.includes($(this).val(), ','));
      })
      .change();
  });
</script>
{/literal}
