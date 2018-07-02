{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
