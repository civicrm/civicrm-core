{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

{* Multi-value Custom Data Import Wizard - Step 1 (upload data file) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

<div class="crm-block crm-form-block crm-custom-import-uploadfile-form-block">
 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}

 <div class="help">
    {ts}The Multi-value Custom Data Import Wizard allows you to easily upload data to populate multi-value custom data records (such as employment or education history) for existing contacts.{/ts}
    {ts}Files to be imported must be in the 'comma-separated-values' format (CSV) and must contain data needed to match the incoming data to an existing contact record in your CiviCRM database.{/ts} {help id='upload'}
 </div>
 <div id="upload-file" class="form-item">
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout">
     <tr class="crm-custom-import-entity-form-block-entity">
       <td class="label">{$form.entity.label}</td>
       <td>{$form.entity.html}</td>
     </tr>
    <tr class="crm-custom-import-uploadfile-form-block-uploadFile">
      <td class="label">{$form.uploadFile.label}</td>
      <td>{$form.uploadFile.html}<br />
      <span class="description">
        {ts}File format must be comma-separated-values (CSV).{/ts}
      </span>
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
      <td>{ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}</td>
        </tr>
  <tr class="crm-import-form-block-skipColumnHeader">
            <td>&nbsp;</td>
            <td>{$form.skipColumnHeader.html} {$form.skipColumnHeader.label}<br />
                <span class="description">
                    {ts}Check this box if the first row of your file consists of field names (Example: "Contact ID", "Participant Role").{/ts}
                </span>
            </td>
  </tr>
  <tr class="crm-custom-import-uploadfile-form-block-multipleCustomData">
              <td class="label">{$form.multipleCustomData.label}</dt>
              <td><span>{$form.multipleCustomData.html}</span> </td>
  </tr>
  <tr class="crm-custom-import-uploadfile-from-block-contactType">
              <td class="label">{$form.contactType.label}</td>
             <td>{$form.contactType.html}<br />
                <span class="description">
                {ts}Select 'Individual' if you are importing custom data for individual persons.{/ts}
                {ts}Select 'Organization' or 'Household' if you are importing custom data . (NOTE: Some built-in contact types may not be enabled for your site.){/ts}
                </span>
              </td>
  </tr>

   <tr class="crm-import-datasource-form-block-fieldSeparator">
     <td class="label">{$form.fieldSeparator.label} {help id='id-fieldSeparator' file='CRM/Contact/Import/Form/DataSource'}</td>
     <td>{$form.fieldSeparator.html}</td>
   </tr>
  <tr class="crm-custom-import-uploadfile-form-block-date_format">
            {include file="CRM/Core/Date.tpl"}
  </tr>
  {if $savedMapping}
  <tr class="crm-custom-import-uploadfile-form-block-savedMapping">
              <td class="label">{if $loadedMapping}{ts}Select a Different Field Mapping{/ts}{else}{ts}Load Saved Field Mapping{/ts}{/if}</dt>
              <td><span>{$form.savedMapping.html}</span> </td>
  </tr>
  <tr>
            <td>&nbsp;</td>
            <td class="description">{ts}Select Saved Mapping, or leave blank to create a new mapping.{/ts}</td>
        {/if}
        </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>
 </div>
