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
  {if $errorMessage}
    <div class="messages warning no-popup">
      {$errorMessage}
    </div>
  {/if}
  <div class="help">
    {ts 1=$importEntity 2= $importEntities}The %1 Import Wizard allows you to easily upload %2 from other applications into CiviCRM.{/ts}
    {ts}Files to be imported must be in the 'comma-separated-values' format (CSV) and must contain data needed to match an existing contact in your CiviCRM database.{/ts} {help id='upload'}
  </div>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout-compressed">
      <tr class="crm-import-uploadfile-from-block-uploadFile">
        <td class="label">{$form.uploadFile.label}</td>
        <td class="html-adjust"> {$form.uploadFile.html}<br />
          <span class="description">{ts}File format must be comma-separated-values (CSV).{/ts}</span>
          <br /><span>{ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}</span>
        </td>
      </tr>
       <tr class="crm-import-uploadfile-from-block-skipColumnHeader">
         <td class="label"></td><td>{$form.skipColumnHeader.html}{$form.skipColumnHeader.label}<br />
           <span class="description">
             {ts}Check this box if the first row of your file consists of field names (Example: 'Contact ID', 'Amount').{/ts}
           </span>
         </td>
       </tr>
      {if array_key_exists('contactType', $form)}
        <tr class="crm-import-uploadfile-from-block-contactType">
          <td class="label">{$form.contactType.label}</td>
          <td>{$form.contactType.html}<br />
            <span class="description">
              {ts 1=$importEntities}Select 'Individual' if you are importing %1 made by individual persons.{/ts}
              {ts 1=$importEntities}Select 'Organization' or 'Household' if you are importing %1 to contacts of that type.{/ts}
            </span>
          </td>
        </tr>
      {/if}
      {if array_key_exists('onDuplicate', $form)}
        <tr class="crm-import-uploadfile-from-block-onDuplicate">
          <td class="label">{$form.onDuplicate.label}</td>
          <td>{$form.onDuplicate.html} {help id="id-onDuplicate"}</td>
        </tr>
      {/if}
      {if array_key_exists('multipleCustomData', $form)}
        <tr class="crm-import-uploadfile-form-block-multipleCustomData">
          <td class="label">{$form.multipleCustomData.label}</td>
          <td><span>{$form.multipleCustomData.html}</span> </td>
        </tr>
      {/if}
        <tr class="crm-import-datasource-form-block-fieldSeparator">
          <td class="label">{$form.fieldSeparator.label} {help id='id-fieldSeparator' file='CRM/Contact/Import/Form/DataSource'}</td>
          <td>{$form.fieldSeparator.html}</td>
        </tr>
       <tr class="crm-import-uploadfile-form-block-date">{include file="CRM/Core/Date.tpl"}</tr>
       {if $savedMapping}
         <tr class="crm-import-uploadfile-form-block-savedMapping">
           <td>{$form.savedMapping.label}</td>
           <td>{$form.savedMapping.html}<br />
             <span class="description">{ts}If you want to use a previously saved import field mapping - select it here.{/ts}</span>
           </td>
         </tr>
       {/if}
    </table>
    <div class="spacer"></div>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

