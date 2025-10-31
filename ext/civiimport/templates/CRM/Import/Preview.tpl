{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Import Preview *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}
<div class="crm-block crm-form-block crm-record-import-preview-form-block" id="upload-file">
  {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
  {include file="CRM/common/WizardHeader.tpl"}

  <div class="help">
    <p>
      {ts}The information below previews the results of importing your data in CiviCRM. Review the totals to ensure that they represent your expected results.{/ts}
    </p>

    {if $invalidRowCount}
      <p class="error">
        {ts 1=$invalidRowCount 2=$downloadErrorRecordsUrl}CiviCRM has detected invalid data or formatting errors in %1 records. If you continue, these records will be skipped.  You can review these problem records: <a href='%2' {if $isOpenResultsInNewTab} target="_blank" rel="noopener noreferrer"{/if}>See Errors</a>.  If you wish, you can then correct them in the original import file, cancel this import, and begin again at step 1.{/ts}
      </p>
    {/if}

    <p>{ts}Click 'Import Now' if you are ready to proceed.{/ts}</p>
  </div>

  {* Summary Preview (record counts) *}
  <table id="preview-counts" class="report">
    <tr><td class="label crm-grid-cell">{ts}Total Rows{/ts}</td>
      <td class="data">{$totalRowCount}</td>
      <td class="explanation">{ts}Total rows in uploaded file.{/ts}
        {if $allRowsUrl} <a href="{$allRowsUrl}" target="_blank" rel="noopener noreferrer">{ts}See rows{/ts}</a>{/if}
      </td>
    </tr>

    {if $invalidRowCount}
      <tr class="error"><td class="label crm-grid-cell">{ts}Rows with Errors{/ts}</td>
        <td class="data">{$invalidRowCount}</td>
        <td class="explanation">{ts}Rows with invalid data in one or more fields. These rows will be skipped (not imported).{/ts}
          {if $invalidRowCount}
            <p><a href="{$downloadErrorRecordsUrl|smarty:nodefaults}" {if $isOpenResultsInNewTab} target="_blank" rel="noopener noreferrer"{/if}>{ts}See Errors{/ts}</a></p>
          {/if}
        </td>
      </tr>
    {/if}

    <tr><td class="label crm-grid-cell">{ts}Valid Rows{/ts}</td>
      <td class="data">{$validRowCount}</td>
      <td class="explanation">{ts}Total rows to be imported.{/ts}</td>
    </tr>
  </table>


  {* Table for mapping preview *}
  {include file="CRM/Import/Form/MapTableCommon.tpl"}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
