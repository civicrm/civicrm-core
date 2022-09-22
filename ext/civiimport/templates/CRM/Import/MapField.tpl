{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Import Wizard - Step 2 (map incoming data fields) *}
<div class="crm-block crm-form-block crm-import-form-block" id="upload-file">
  {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
  {include file="CRM/common/WizardHeader.tpl"}

  <crm-angular-js modules="crmCiviimport">
    <crm-import-ui class="crm-import-field-selector-outer"></crm-import-ui>
  </crm-angular-js>
</div>
