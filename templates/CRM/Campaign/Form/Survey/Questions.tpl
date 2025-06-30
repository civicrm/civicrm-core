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
  <div class="help">
    <p>{ts}Select the Profiles to include on the survey. The contact information are questions that are associated with the contact record. If a contact responds to multiple surveys, the contact information will be updated. The other questions should be a profile of activity fields. Every new survey response will create a new activity.{/ts}</p>
    <p>{ts}To create new questions, go to to <a href="{crmURL p="civicrm/admin/custom/group" q="reset=1"}" target="_blank">Administer Custom Fields</a>.{/ts} {ts}Custom fields must then be included in profiles.{/ts} {ts}To create a new profile, go to <a href="{crmURL p="civicrm/admin/uf/group" q="reset=1"}" target="_blank">Administer Profiles</a>.{/ts}</p>
  </div>
  <table class="form-layout-compressed">
    <tr class="crm-campaign-survey-questions-form-block-contact_profile_id">
      <td class="label">{$form.contact_profile_id.label}</td>
      <td class="view-value">{$form.contact_profile_id.html}
        <a href="#" class="crm-button crm-popup">{icon icon="fa-list-alt"}{/icon} {ts}Fields{/ts}</a>
      </td>
    </tr>
    <tr class="crm-campaign-survey-questions-form-block-activity_profile_id">
      <td class="label">{$form.activity_profile_id.label}</td>
      <td class="view-value">{$form.activity_profile_id.html}
        <a href="#" class="crm-button crm-popup">{icon icon="fa-list-alt"}{/icon} {ts}Fields{/ts}</a>
      </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
