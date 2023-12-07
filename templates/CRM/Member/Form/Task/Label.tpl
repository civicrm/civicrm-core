{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-contact-task-mailing-label-form-block">
  <div class="messages status no-popup">{include file="CRM/Member/Form/Task.tpl"}</div>
  <table class="form-layout-compressed">
     <tr class="crm-contact-task-mailing-label-form-block-label_name">
        <td class="label">{$form.label_name.label}</td>
        <td>{$form.label_name.html} {help id="id-select-label" file="CRM/Contact/Form/Task/Label.hlp"}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-location_type_id">
        <td class="label">{$form.location_type_id.label}</td>
        <td>{$form.location_type_id.html}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-do_not_mail">
        <td></td> <td>{$form.per_membership.html} {$form.per_membership.label}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-do_not_mail">
        <td></td> <td>{$form.do_not_mail.html} {$form.do_not_mail.label}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-merge_same_address">
        <td></td><td>{$form.merge_same_address.html} {$form.merge_same_address.label}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-merge_same_household">
        <td></td><td>{$form.merge_same_household.html} {$form.merge_same_household.label}</td>
     </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
