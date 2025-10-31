{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-import-preview-form-block">
{* Import Wizard - Step 3 (preview import results prior to actual data loading) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}
<div class="help">
    <p>
    {ts}The information below previews the results of importing your data in CiviCRM. Review the totals to ensure that they represent your expected results.{/ts}
    </p>

    {if $invalidRowCount}
        <p class="error">
        {ts 1=$invalidRowCount 2=$downloadErrorRecordsUrl|smarty:nodefaults}CiviCRM has detected invalid data or formatting errors in %1 records. If you continue, these records will be skipped. OR, you can download a file with just these problem records - <a href='%2'>Download Errors</a>. Then correct them in the original import file, cancel this import and begin again at step 1.{/ts}
        </p>
    {/if}

    <p>{ts}Click 'Import Now' if you are ready to proceed.{/ts}</p>
</div>

<div id="preview-info">
 {* Summary Preview (record counts) *}
 <table id="preview-counts" class="report">
    <tr><td class="label crm-grid-cell">{ts}Total Rows{/ts}</td>
        <td class="data">{$totalRowCount}</td>
        <td class="explanation">{ts}Total number of rows in the imported data.{/ts}</td>
    </tr>

    {if $invalidRowCount}
      <tr class="error"><td class="label crm-grid-cell">{ts}Rows with Errors{/ts}</td>
        <td class="data">{$invalidRowCount}</td>
        <td class="explanation">{ts}Rows with invalid data in one or more fields (for example, invalid email address formatting). These rows will be skipped (not imported).{/ts}
          <div class="action-link"><a href="{$downloadErrorRecordsUrl|smarty:nodefaults}"><i class="crm-i fa-download" role="img" aria-hidden="true"></i> {ts}Download Errors{/ts}</a></div>
        </td>
      </tr>
    {/if}

    <tr>
    <td class="label crm-grid-cell">{ts}Valid Rows{/ts}</td>
        <td class="data">{$validRowCount}</td>
        <td class="explanation">{ts}Total rows to be imported.{/ts}</td>
    </tr>
 </table>

 {* Table for mapping preview *}
 {include file="CRM/Contact/Import/Form/MapTable.tpl"}

 {* Group options *}
 {* New Group *}
<details id="new-group" class="crm-accordion-bold">
 <summary>
    {ts}Add imported records to a new group{/ts}
 </summary>
 <div class="crm-accordion-body">
            <table class="form-layout-compressed">
             <tr>
               <td class="description label">{$form.newGroupName.label}</td>
               <td>{$form.newGroupName.html}</td>
             </tr>
             <tr>
               <td class="description label">{$form.newGroupDesc.label}</td>
               <td>{$form.newGroupDesc.html}</td>
             </tr>
             <tr>
               <td class="description label">{$form.newGroupType.label}</td>
               <td>{$form.newGroupType.html}</td>
             </tr>
            </table>
 </div>
</details>


      {* Existing Group *}

<details id="existing-groups" class="crm-accordion-bold crm-existing_group-accordion" {if !empty($form.groups)}open{/if}>
 <summary>
  {$form.groups.label}
 </summary>
 <div class="crm-accordion-body">

        <div class="form-item">
        <table><tr><td style="width: 14em;"></td><td>{$form.groups.html}</td></tr></table>
        </div>
 </div>
</details>

    {* Tag options *}
    {* New Tag *}
<details id="new-tag" class="crm-accordion-bold">
 <summary>
  {ts}Create a new tag and assign it to imported records{/ts}
 </summary>
 <div class="crm-accordion-body">

  <div class="form-item">
  <table class="form-layout-compressed">
           <tr>
               <td class="description label">{$form.newTagName.label}</td>
              <td>{$form.newTagName.html}</td>
           </tr>
           <tr>
        <td class="description label">{$form.newTagDesc.label}</td>
              <td>{$form.newTagDesc.html}</td>
           </tr>
        </table>
    </div>
 </div>
</details>
    {* Existing Tag Imported Contact *}

<details id="existing-tags" class="crm-accordion-bold">
 <summary>
  {ts}Tag imported records{/ts}
</summary>
 <div class="crm-accordion-body">

        <table class="form-layout-compressed">
            <tr><td style="width: 14em;"></td>
             <td class="listing-box" style="margin-bottom: 0em; width: 15em;">
               {$form.tag.html}
            </td>
          </tr>
        </table>
 </div>
</details>
</div> {* End of preview-info div. We hide this on form submit. *}

<div class="crm-submit-buttons">
   {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>
