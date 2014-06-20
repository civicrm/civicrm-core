{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Contribution Import Wizard - Step 1 (upload data file) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}
 <div class="crm-block crm-form-block  crm-contribution-import-uploadfile-form-block id="upload-file">
 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {ts}The Contribution Import Wizard allows you to easily upload contributions from other applications into CiviCRM.{/ts}
    {ts}Files to be imported must be in the 'comma-separated-values' format (CSV) and must contain data needed to match the contribution to an existing contact in your CiviCRM database.{/ts} {help id='upload'}
 </div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout-compressed">
    <tr><td class="label">{$form.uploadFile.label}</td><td class="html-adjust"> {$form.uploadFile.html}<br />
            <span class="description">{ts}File format must be comma-separated-values (CSV).{/ts}</span></td></tr>
        <tr><td class="label"></td><td>{ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}</td></tr>
        <tr><td class="label"></td><td>{$form.skipColumnHeader.html}{$form.skipColumnHeader.label}<br />
                <span class="description">
                    {ts}Check this box if the first row of your file consists of field names (Example: 'Contact ID', 'Amount').{/ts} </span></td></tr>
        <tr><td class="{$form.contactType.name} label">{$form.contactType.label}</td><td class="{$form.contactType.name}">{$form.contactType.html}<br />
            <span class="description">
                {ts}Select 'Individual' if you are importing contributions made by individual persons.{/ts}
                {ts}Select 'Organization' or 'Household' if you are importing contributions made by contacts of that type. (NOTE: Some built-in contact types may not be enabled for your site.){/ts}</span></td></tr>
        <tr><td class="label">{$form.onDuplicate.label}</td><td>{$form.onDuplicate.html} {help id="id-onDuplicate"}</td></tr>
        <tr>{include file="CRM/Core/Date.tpl"}</tr>
{if $savedMapping}
      <tr> <td class="label">{if $loadedMapping}{ts}Select a Different Field Mapping{/ts}{else}{ts}Load Saved Field Mapping{/ts}{/if}</td><td>{$form.savedMapping.html}<br /> <span class="description">{ts}Select a saved field mapping if this file format matches a previous import.{/ts}</span></tr>
{/if}
    </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
 </div>
