{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Activity Import Wizard - Step 1 (upload data file) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}
<div class="crm-block crm-form-block crm-activity-import-uploadfile-form-block">

 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}

 <div class="help">
    <p>
    {ts}The Activity Import Wizard allows you to easily upload activity from other applications into CiviCRM. Contacts must already exist in your CiviCRM database prior to importing activity.{/ts}
    {help id="id-upload"}
    </p>
 </div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
 <div id="upload-file">
 <h3>{ts}Upload Data File{/ts}</h3>
      <table class="form-layout-compressed">
        <tr class="crm-activity-import-uploadfile-form-block-uploadFile">
           <td class="label">{$form.uploadFile.label}</td>
           <td>{$form.uploadFile.html}<br />
                <span class="description">{ts}File format must be comma-separated-values (CSV).{/ts}</span><br /><span>{ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}</span>
           </td>
        </tr>
        <tr class="crm-activity-import-uploadfile-form-block-skipColumnHeader">
           <td class="label"></td>
           <td>{$form.skipColumnHeader.html}{$form.skipColumnHeader.label}<br />
               <span class="description">{ts}Check this box if the first row of your file consists of field names (Example: 'Contact ID', 'Activity Type', 'Activity Date').{/ts}</span>
           </td>
        </tr>
        <tr class="crm-import-datasource-form-block-fieldSeparator">
          <td class="label">{$form.fieldSeparator.label} {help id='id-fieldSeparator' file='CRM/Contact/Import/Form/DataSource'}</td>
          <td>{$form.fieldSeparator.html}</td>
        </tr>
        <tr>{include file="CRM/Core/Date.tpl"}</tr>
        {if $savedMapping}
        <tr class="crm-activity-import-uploadfile-form-block-savedMapping">
        <td>{if $loadedMapping}{ts}Select a Different Field Mapping{/ts}{else}{ts}Load Saved Field Mapping{/ts}{/if}</td>
           <td>{$form.savedMapping.html}<br />
              <span class="description">{ts}Select Saved Mapping or Leave blank to create a new One.{/ts}</span>
{/if}
           </td>
        </tr>
 </table>
 <div class="spacer"></div>
 </div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
