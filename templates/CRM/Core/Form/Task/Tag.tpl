{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form-block crm-block crm-task-addtotag-form-block">
  <table class="form-layout-compressed">
    <tr class="crm-task-addtotag-form-block-tag">
        <td>
          {$form.tag.label}
          {$form.tag.html}
        </td>
    </tr>
    <tr>
        <td>
            {include file="CRM/common/Tagset.tpl"}
        </td>
    </tr>
    <tr><td>{include file=$parentTemplate}</td></tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
