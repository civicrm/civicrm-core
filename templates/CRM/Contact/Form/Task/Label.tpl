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
  <div class="messages status no-popup">{include file="CRM/Contact/Form/Task.tpl"}</div>
  <table class="form-layout-compressed">
     <tr class="crm-contact-task-mailing-label-form-block-label_name">
        <td class="label">{$form.label_name.label nofilter}</td>
        <td>{$form.label_name.html nofilter} {help id="label_name" file="CRM/Contact/Form/Task/Label.hlp"}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-location_type_id">
        <td class="label">{$form.location_type_id.label nofilter}</td>
        <td>{$form.location_type_id.html nofilter}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-do_not_mail">
        <td></td> <td>{$form.do_not_mail.html nofilter} {$form.do_not_mail.label nofilter}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-merge_same_address">
        <td></td><td>{$form.merge_same_address.html nofilter} {$form.merge_same_address.label nofilter}</td>
     </tr>
     <tr class="crm-contact-task-mailing-label-form-block-merge_same_household">
        <td></td><td>{$form.merge_same_household.html nofilter} {$form.merge_same_household.label nofilter}</td>
     </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
