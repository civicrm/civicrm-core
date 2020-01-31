{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-case-form-block">
  {*<div class="help">*}
      {*{ts}...{/ts} {docURL page="Debugging for developers" resource="wiki"}*}
  {*</div>*}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout">
    <tr class="crm-case-form-block-civicaseRedactActivityEmail">
      <td class="label">{$form.civicaseRedactActivityEmail.label}</td>
      <td>{$form.civicaseRedactActivityEmail.html}<br />
        <span class="description">{ts}Should activity emails be redacted?{/ts} {ts}(Set "Default" to load setting from the legacy "Settings.xml" file.){/ts}</span>
      </td>
    </tr>
    <tr class="crm-case-form-block-civicaseAllowMultipleClients">
      <td class="label">{$form.civicaseAllowMultipleClients.label}</td>
      <td>{$form.civicaseAllowMultipleClients.html}<br />
        <span class="description">{ts}How many clients may be associated with a given case?{/ts} {ts}(Set "Default" to load setting from the legacy "Settings.xml" file.){/ts}</span>
      </td>
    </tr>
    <tr class="crm-case-form-block-civicaseNaturalActivityTypeSort">
      <td class="label">{$form.civicaseNaturalActivityTypeSort.label}</td>
      <td>{$form.civicaseNaturalActivityTypeSort.html}<br />
        <span class="description">{ts}How to sort activity-types on the "Manage Case" screen? {/ts} {ts}(Set "Default" to load setting from the legacy "Settings.xml" file.){/ts}</span>
      </td>
    </tr>
    <tr class="crm-case-form-block-civicaseActivityRevisions">
      <td class="label">{$form.civicaseActivityRevisions.label}</td>
      <td>{$form.civicaseActivityRevisions.html}<br />
        <span class="description">{ts}Enable embedded tracking to activity revisions within the "civicrm_activity" table. Alternatively, see "Administer => System Settings => Misc => Logging".{/ts}</span>
      </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
