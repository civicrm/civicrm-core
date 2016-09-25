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
{* Template for adding/editing a CiviCRM Navigation Menu Item *}
<div class="crm-block crm-form-block crm-navigation-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout-compressed">
    <tr class="crm-navigation-form-block-label">
      <td class="label">{$form.label.label}</td><td>{$form.label.html}</td>
    </tr>
    <tr class="crm-navigation-form-block-url">
      <td class="label">{$form.url.label} {help id="id-menu_url" file="CRM/Admin/Form/Navigation.hlp"}</td>
      <td>{$form.url.html} </td>
    </tr>
    {if $form.parent_id.html}
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
      .change()
      .crmSelect2({
        formatResult: CRM.utils.formatSelect2Result,
        formatSelection: function(row) {return row.label},
        multiple: true
      });
  });
</script>
{/literal}
