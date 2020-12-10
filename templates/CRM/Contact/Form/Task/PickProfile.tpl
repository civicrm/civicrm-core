{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*Update multiple contacts*}
<div class="crm-block crm-form-block crm-contact-task-pickprofile-form-block">
 <table class="form-layout-compressed">
    <tr class="crm-contact-task-pickprofile-form-block-uf_group_id">
       <td class="label">{$form.uf_group_id.label}</td>
       <td>{$form.uf_group_id.html}</td>
    </tr>
    <tr>
        <td class="label"></td>
        <td>
            {include file="CRM/Contact/Form/Task.tpl"}
        </td>
    </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>

