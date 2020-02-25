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
<p>{ts}Select the fields to be exported using the table below. For each field, first select the contact type that the field belongs to (e.g. select <strong>Individuals</strong> if you are exporting <strong>Last Name</strong>). Then select the actual field to be exported from the drop-down menu which will appear next to the contact type. Your export can include multiple types of contact records, and non-applicable fields will be empty (e.g. <strong>Last Name</strong> will not be populated for an Organization record).{/ts}</p>
<p>{ts}Click <strong>Select more fields...</strong> if you want to export more fields than are initially displayed in the table.{/ts}</p>

{if $savedMapping}
<p>{ts}Click 'Load Saved Field Mapping' to retrieve an export setup that you have previously saved.{/ts}</p>
{/if}

<p>{ts}If you want to use the same export setup in the future, check 'Save this field mapping' at the bottom of the page before continuing. You will then be able to reload this setup with a single click.{/ts}</p>
</div>

<div class="crm-block crm-form-block crm-export-map-form-block">
{* Export Wizard - Step 3 (map export data fields) *}

 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

{* Table for mapping data to CRM fields *}
{include file="CRM/Export/Form/table.tpl"}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
{$initHideBoxes}
</div>
