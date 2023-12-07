{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-campaign-survey-questions-form-block">
  <table class="form-layout-compressed">
    <tr class="crm-campaign-survey-questions-form-block-contact_profile_id">
      <td class="label">{$form.contact_profile_id.label}</td>
      <td class="view-value">{$form.contact_profile_id.html}</td>
    </tr>
    <tr class="crm-campaign-survey-questions-form-block-activity_profile_id">
      <td class="label">{$form.activity_profile_id.label}</td>
      <td class="view-value">{$form.activity_profile_id.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
