{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-import-summary-form-block">

{* Import Wizard - Step 4 (summary of import results AFTER actual data loading) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}
 <div class="help">
   <p>
     <strong>{ts 1=$statusLabel}Import Status: %1{/ts}</strong>
     {if $statusName === 'draft' && $searchDisplayLink}
       <br><a href="{$searchDisplayLink}">{ts}Continue Entering Data{/ts}</a>
     {/if}
   </p>
   {if $templateURL}
     <p>
       {ts 1=$templateURL|smarty:nodefaults}You can re-use this import configuration <a href="%1">here</a>{/ts}</p>
   {/if}

   {if $unMatchCount}
        <p class="error">
        {ts count=$unMatchCount plural='CiviCRM has detected mismatched contact IDs. These records have not been updated.'}CiviCRM has detected mismatched contact ID. This record has not been updated.{/ts}
        </p>
        <p class="error">
        {ts 1=$downloadMismatchRecordsUrl}You can <a href='%1'>Download Mismatched Contacts</a>. You may then correct them, and import the new file with the corrected data.{/ts}
        </p>
    {/if}

    {if $invalidRowCount }
        <p class="error">
        {ts count=$invalidRowCount plural='CiviCRM has detected invalid data and/or formatting errors in %count records. These records have not been imported.'}CiviCRM has detected invalid data and/or formatting errors in one record. This record has not been imported.{/ts}
        </p>
        <p class="error">
        {ts 1=$downloadErrorRecordsUrl|smarty:nodefaults}You can <a href='%1'>See the errors</a>. You may then correct them, and re-import with the corrected data.{/ts}
        </p>
    {/if}

    {if $duplicateRowCount}
        <p {if $dupeError}class="error"{/if}>
        {ts count=$duplicateRowCount plural='CiviCRM has detected %count records which are duplicates of existing CiviCRM contact records.'}CiviCRM has detected one record which is a duplicate of existing CiviCRM contact record.{/ts} {$dupeActionString}
        </p>
        <p {if $dupeError}class="error"{/if}>
        {ts 1=$downloadDuplicateRecordsUrl}You can <a href='%1'>Download Duplicates</a>. You may then review these records to determine if they are actually duplicates, and correct the email address for those that are not.{/ts}
        </p>
    {/if}

    {if $unparsedAddressCount}
        <p class="error">{ts}Records imported successfully but unable to parse some of the street addresses{/ts}</p>
        <p class="error">
        {ts 1=$downloadAddressRecordsUrl}You can <a href='%1'>Download Street Address Records </a>. You may then edit those contact records and update the street address accordingly.{/ts}
        </p>
    {/if}
  </div>
  {if !$outputUnavailable}
  {* Summary of Import Results (record counts) *}
  <table id="summary-counts" class="report">
    <tr><td class="label crm-grid-cell">{ts}Total Rows{/ts}</td>
      <td class="data">{if $allRowsUrl} <a href="{$allRowsUrl}" target="_blank" rel="noopener noreferrer">{$totalRowCount}</a>{else}{$totalRowCount}{/if}</td>
      <td class="explanation">{ts}Total number of rows in the imported data.{/ts}</td>
    </tr>
    {if $unprocessedRowCount}
      <tr><td class="label crm-grid-cell">{ts}Rows Still processing{/ts}</td>
        <td class="data">{$unprocessedRowCount}</td>
        <td class="explanation">{ts}Rows still being processed.{/ts}</td>
      </tr>
    {/if}

    {if $invalidRowCount}
      <tr class="error"><td class="label crm-grid-cell">{ts}Invalid Rows (skipped){/ts}</td>
        <td class="data">{$invalidRowCount}</td>
        <td class="explanation">{ts}Rows with invalid data in one or more fields (for example, invalid email address formatting). These rows will be skipped (not imported).{/ts}
          <div class="action-link"><a href="{$downloadErrorRecordsUrl|smarty:nodefaults}"><i class="crm-i fa-download" aria-hidden="true"></i> {ts}See Errors{/ts}</a></div>
        </td>
      </tr>
    {/if}

    {if $unMatchCount}
      <tr class="error"><td class="label crm-grid-cell">{ts}Mismatched Rows (skipped){/ts}</td>
        <td class="data">{$unMatchCount}</td>
        <td class="explanation">{ts}Rows with mismatched contact IDs... (NOT updated).{/ts}
          <div class="action-link"><a href="{$downloadMismatchRecordsUrl}"><i class="crm-i fa-download" aria-hidden="true"></i> {ts}Download Mismatched Contacts{/ts}</a></div>
        </td>
      </tr>
    {/if}

    {if $duplicateRowCount && $dupeError}
      <tr class="error"><td class="label crm-grid-cell">{ts}Duplicate Rows{/ts}</td>
        <td class="data">{$duplicateRowCount}</td>
        <td class="explanation">{ts}Rows which are duplicates of existing CiviCRM contact records.{/ts} {$dupeActionString}
          <div class="action-link"><a href="{$downloadDuplicateRecordsUrl}"><i class="crm-i fa-download" aria-hidden="true"></i> {ts}Download Duplicates{/ts}</a></div>
        </td>
    </tr>
    {/if}

    <tr>
      <td class="label crm-grid-cell">{ts}Total Rows Imported{/ts}</td>
      <td class="data">{if $importedRowsUrl} <a href="{$importedRowsUrl}" target="_blank" rel="noopener noreferrer">{$importedRowCount}</a>{else}{$importedRowCount}{/if}</td>
      <td class="explanation">{ts}Total number of primary records created or modified during the import.{/ts}</td>
    </tr>
    {foreach from=$trackingSummary item="summaryRow"}
      <tr>
        <td class="label crm-grid-cell"></td>
        <td class="data">{$summaryRow.value}</td>
        <td class="explanation">{$summaryRow.description}</td>
      </tr>
    {/foreach}

    {if $groupAdditions}
    <tr><td class="label crm-grid-cell">{ts}Import to Groups{/ts}</td>
        <td colspan="2" class="explanation">
            {foreach from=$groupAdditions item="group"}
                <label><a href="{$group.url}">{$group.name}</a></label>:
                {if $group.new}
                    {ts count=$group.added plural='%count contacts added to this new group.'}One contact added to this new group.{/ts}
                {else}
                    {ts count=$group.added plural='%count contacts added to this existing group.'}One contact added to this existing group.{/ts}
                {/if}
                {if $group.notAdded}{ts count=$group.notAdded plural='%count contacts NOT added (already in this group).'}One contact NOT added (already in this group).{/ts}{/if}<br />
            {/foreach}
        </td>
    </tr>
    {/if}

    {if $tagAdditions}
    <tr><td class="label crm-grid-cell">{ts}Tagged Imported Contacts{/ts}</td>
        <td colspan="2" class="explanation">
            {foreach from=$tagAdditions item="tag"}
                <label>{$tag.name}</label>:
                {ts count=$tag.added plural='%count contacts are tagged with this tag.'}One contact is tagged with this tag.{/ts}
                {if $tag.notAdded}{ts count=$tag.notAdded plural='%count contacts NOT tagged (already tagged to this tag).'}One contact NOT tagged (already tagged to this tag).{/ts}{/if}<br />
            {/foreach}
        </td>
    </tr>
    {/if}

 </table>
  {/if}

</div>
