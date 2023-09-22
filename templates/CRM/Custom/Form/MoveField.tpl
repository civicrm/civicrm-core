{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-move-field-block">
    <table class="form-layout-compressed">
        <tr><td class="label">{$form.dst_group_id.label}</td>
            <td>{$form.dst_group_id.html}<br />
                <span class="description">{ts}Select a different Custom Data Set for this field.{/ts}
            </td>
        </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
