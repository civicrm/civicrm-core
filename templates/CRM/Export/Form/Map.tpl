{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  <p>{ts}Select the fields to be exported using the table below. You'll see a live preview of the first few records for each field.{/ts}</p>
  <p>{ts}Your export can include multiple types of contacts, and non-applicable fields will be empty (e.g. <strong>Last Name</strong> will not be populated for an Organization record).{/ts}</p>
  <p>{ts}If you want to use the same export setup in the future, check 'Save Fields' at the bottom of the page before continuing. You will then be able to reload this setup with a single click.{/ts}</p>
</div>
<div class="crm-block crm-form-block crm-export-map-form-block">
  {* Export Wizard - Step 3 (map export data fields) *}
  {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
  {include file="CRM/common/WizardHeader.tpl"}

  <crm-angular-js modules="exportui">
    <crm-export-ui class="crm-export-field-selector-outer"></crm-export-ui>
  </crm-angular-js>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
