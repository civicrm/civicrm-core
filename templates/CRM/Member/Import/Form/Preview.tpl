{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-member-import-preview-form-block">
{* Membership Import Wizard - Step 3 (preview import results prior to actual data loading) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}

 <div class="help">
    <p>
    {ts}The information below previews the results of importing your data in CiviCRM. Review the totals to ensure that they represent your expected results.{/ts}
    </p>

    {if $invalidRowCount}
        <p class="error">
        {ts 1=$invalidRowCount 2=$downloadErrorRecordsUrl}CiviCRM has detected invalid data or formatting errors in %1 records. If you continue, these records will be skipped. OR, you can download a file with just these problem records - <a href='%2'>Download Errors</a>. Then correct them in the original import file, cancel this import and begin again at step 1.{/ts}
        </p>
    {/if}

    {if $conflictRowCount}
        <p class="error">
        {ts 1=$conflictRowCount 2=$downloadConflictRecordsUrl}CiviCRM has detected %1 records with conflicting transaction ids within this data file. If you continue, these records will be skipped. OR, you can download a file with just these problem records - <a href='%2'>Download Conflicts</a>. Then correct them in the original import file, cancel this import and begin again at step 1.{/ts}
        </p>
    {/if}


    <p>{ts}Click 'Import Now' if you are ready to proceed.{/ts}</p>
 </div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
 {include file="CRM/common/importProgress.tpl"}
 {* Summary Preview (record counts) *}
 <table id="preview-counts" class="report">
    <tr><td class="label crm-grid-cell">{ts}Total Rows{/ts}</td>
        <td class="data">{$totalRowCount}</td>
        <td class="explanation">{ts}Total rows (membership records) in uploaded file.{/ts}</td>
    </tr>

    {if $invalidRowCount}
    <tr class="error"><td class="label crm-grid-cell">{ts}Rows with Errors{/ts}</td>
        <td class="data">{$invalidRowCount}</td>
        <td class="explanation">{ts}Rows with invalid data in one or more fields. These rows will be skipped (not imported).{/ts}
            {if $invalidRowCount}
                <div class="action-link"><a href="{$downloadErrorRecordsUrl}">&raquo; {ts}Download Errors{/ts}</a></div>
            {/if}
        </td>
    </tr>
    {/if}

    {if $conflictRowCount}
    <tr class="error"><td class="label crm-grid-cell">{ts}Conflicting Rows{/ts}</td>
        <td class="data">{$conflictRowCount}</td>
        <td class="explanation">{ts}Rows with conflicting transaction ids within this file. These rows will be skipped (not imported).{/ts}
            {if $conflictRowCount}
                <div class="action-link"><a href="{$downloadConflictRecordsUrl}">&raquo; {ts}Download Conflicts{/ts}</a></div>
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
 {include file="CRM/Member/Import/Form/MapTable.tpl}


 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
 </div>
