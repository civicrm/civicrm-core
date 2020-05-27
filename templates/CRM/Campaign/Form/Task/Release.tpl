{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<h3>
{ts}Release Respondents{/ts}
</h3>
<div class="crm-form-block crm-block crm-campaign-task-release-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="form-layout-compressed">
  <tr class="crm-campaign-task-release-form-block-surveytitle">
    <td colspan=2>
      <div class="status">
        <div class="icon inform-icon"></div>&nbsp;{ts 1=$surveyTitle}Do you want to release respondents for '%1' ?{/ts}
      </div>
    </td>
  </tr>

    <tr><td colspan=2>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
</table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
