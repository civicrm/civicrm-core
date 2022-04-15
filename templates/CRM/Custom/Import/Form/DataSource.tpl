{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* Import Wizard - Step 1 (choose data source) *}
<div class="crm-block crm-form-block crm-import-datasource-form-block">

  {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
  {include file="CRM/common/WizardHeader.tpl"}
 {if !$fieldGroups}
  <div class="messages warning no-popup">
    {ts}This import screen cannot be used because there are no Multi-value custom data groups.{/ts}
  </div>
 {/if}
  <div class="help">
    {ts 1=$importEntity 2= $importEntities}The %1 Import Wizard allows you to easily upload %2 from other applications into CiviCRM.{/ts}
    {ts}Files to be imported must be in the 'comma-separated-values' format (CSV) and must contain data needed to match an existing contact in your CiviCRM database.{/ts} {help id='upload'}
  </div>
 <div id="upload-file" class="form-item">
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout">
    <tr class="crm-import-uploadfile-form-block-uploadFile">
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
  <tr class="crm-import-uploadfile-form-block-multipleCustomData">
              <td class="label">{$form.multipleCustomData.label}</td>
              <td><span>{$form.multipleCustomData.html}</span> </td>
  </tr>
  <tr class="crm-import-uploadfile-from-block-contactType">
              <td class="label">{$form.contactType.label}</td>
             <td>{$form.contactType.html}</td>
  </tr>

   <tr class="crm-import-datasource-form-block-fieldSeparator">
     <td class="label">{$form.fieldSeparator.label} {help id='id-fieldSeparator' file='CRM/Contact/Import/Form/DataSource'}</td>
     <td>{$form.fieldSeparator.html}</td>
   </tr>
  <tr class="crm-import-uploadfile-form-block-date_format">
            {include file="CRM/Core/Date.tpl"}
  </tr>
  {if $savedMapping}
  <tr class="crm-import-uploadfile-form-block-savedMapping">
              <td class="label">{$form.savedMapping.label}</td>
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
