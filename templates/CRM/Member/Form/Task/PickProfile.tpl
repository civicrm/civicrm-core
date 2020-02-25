{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form crm-form-block crm-member-task-pickprofile-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout-compressed">
     <tr class="crm-member-task-pickprofile-form-block-uf_group_id">
       <td class="label">{$form.uf_group_id.label}</td><td>{$form.uf_group_id.html}</td>
     </tr>
     <tr>
       <td></td><td>{include file="CRM/Member/Form/Task.tpl"}</td>
     </tr>
 </table>
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
