{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for merging tags (admin)  *}
<div class="crm-block crm-form-block crm-tag-form-block">
  <div class="status">
    {ts 1=$tags|@count}You are about to combine the following %1 tags into a single tag:{/ts}<br />
    {', '|implode:$tags}
  </div>
  <table class="form-layout-compressed">
    <tr class="crm-tag-form-block-label">
      <td class="label">{$form.name.label}</td>
      <td>{$form.name.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
