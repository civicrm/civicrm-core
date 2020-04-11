{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-event-badge-form-block">
<div class="crm-section crm-task-count">
    {include file="CRM/Event/Form/Task.tpl"}
</div>
<table class="form-layout-compressed">
     <tr class="crm-event-badge-form-block-label_id">
        <td class="label">{$form.badge_id.label}</td>
        <td>{$form.badge_id.html}</td>
     </tr>
     <tr></tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
