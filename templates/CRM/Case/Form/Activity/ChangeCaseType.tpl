{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Template for "Change Case Type" activities *}
   <div class="crm-block crm-form-block crm-case-changecasetype-form-block">
    <tr class="crm-case-changecasetype-form-block-case_type_id">
      <td class="label">{$form.case_type_id.label}</td>
  <td>{$form.case_type_id.html}</td>
    </tr>
    <tr class="crm-case-changecasetype-form-block-is_reset_timeline">
  <td class="label">{$form.is_reset_timeline.label}</td>
  <td>{$form.is_reset_timeline.html}</td>
    </tr>
    <tr class="crm-case-changecasetype-form-block-reset_date_time">
        <td class="label">{$form.reset_date_time.label} <span class="crm-marker">*</span></td>
        <td>{$form.reset_date_time.html}</td>
    </tr>
  </div>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      var $form = $('form.{/literal}{$form.formClass}{literal}');
      $('input[name=is_reset_timeline]', $form).click(function() {
        $('.crm-case-changecasetype-form-block-reset_date_time').toggle($(this).val() === '1');
      })
    })
  </script>
{/literal}
