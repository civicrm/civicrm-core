{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-campaign-survey-delete-form-block">
  <table class="form-layout">
    <tr>
      <td colspan="2">
        <div class="status">{icon icon="fa-info-circle"}{/icon}{ts 1=$surveyTitle}Are you sure you want to delete the %1 survey?{/ts}</div>
      </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
