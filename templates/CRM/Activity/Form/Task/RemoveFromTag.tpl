{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template to remove tags from activity  *}
<div class="crm-form-block crm-block crm-activity-task-removefromtag-form-block">
<h3>{ts}Tag Activities (Remove){/ts}</h3>
<table class="form-layout-compressed">
    <tr class="crm-activity-task-removefromtag-form-block-tag">
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
    <tr><td>{include file="CRM/Activity/Form/Task.tpl"}</td></tr>
</table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
