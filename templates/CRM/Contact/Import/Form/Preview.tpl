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
<div class="crm-block crm-form-block crm-import-preview-form-block">

{literal}
<script type="text/javascript">
function setIntermediate( ) {
  var dataUrl = "{/literal}{$statusUrl}{literal}";
  cj.getJSON( dataUrl, function( response ) {

     var dataStr = response.toString();
     var result  = dataStr.split(",");
     cj("#intermediate").html( result[1] );
           if( result[0] < 100 ){
          cj("#importProgressBar .ui-progressbar-value").animate({width: result[0]+"%"}, 500);
    cj("#status").text( result[0]+"% Completed");
             }
   });
}

function pollLoop( ){
  setIntermediate( );
  window.setTimeout( pollLoop, 10*1000 ); // 10 sec
}

function verify( ) {
    if (! confirm('Backing up your database before importing is recommended, as there is no Undo for this. {/literal}{ts escape='js'}Are you sure you want to Import now{/ts}{literal}?') ) {
        return false;
    }

  cj("#id-processing").show( ).dialog({
    modal         : true,
    width         : 350,
    height        : 160,
    resizable     : false,
    draggable     : true,
    closeOnEscape : false,
    open          : function ( ) {
        cj("#id-processing").dialog().parents(".ui-dialog").find(".ui-dialog-titlebar").remove();
    }
  });
  cj("#importProgressBar" ).progressbar({value:0});
      cj("#importProgressBar").show( );
  pollLoop( );
}
</script>
{/literal}

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
        {ts 1=$invalidRowCount 2=$downloadErrorRecordsUrl}CiviCRM has detected invalid data or formatting errors in %1 records. If you continue, these records will be skipped. OR, you can download a file with just these problem records - <a href='%2'>Download Errors</a>. Then correct them in the original import file, cancel this import and begin again at step 1.{/ts}
        </p>
    {/if}

    {if $conflictRowCount}
        <p class="error">
        {ts 1=$conflictRowCount 2=$downloadConflictRecordsUrl}CiviCRM has detected %1 records with conflicting email addresses within this data file. If you continue, these records will be skipped. OR, you can download a file with just these problem records - <a href='%2'>Download Conflicts</a>. Then correct them in the original import file, cancel this import and begin again at step 1.{/ts}
        </p>
    {/if}

    <p>{ts}Click 'Import Now' if you are ready to proceed.{/ts}</p>
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{* Import Progress Bar and Info *}
<div id="id-processing" class="hiddenElement">
  <h3>Importing records...</h3><br />
       <div id="status" style="margin-left:6px;"></div>
  <div class="progressBar" id="importProgressBar" style="margin-left:6px;display:none;"></div>
  <div id="intermediate"></div>
  <div id="error_status"></div>
</div>

<div id="preview-info">
 {* Summary Preview (record counts) *}
 <table id="preview-counts" class="report">
    <tr><td class="label">{ts}Total Rows{/ts}</td>
        <td class="data">{$totalRowCount}</td>
        <td class="explanation">{ts}Total number of rows in the imported data.{/ts}</td>
    </tr>

    {if $invalidRowCount}
    <tr class="error"><td class="label">{ts}Rows with Errors{/ts}</td>
        <td class="data">{$invalidRowCount}</td>
        <td class="explanation">{ts}Rows with invalid data in one or more fields (for example, invalid email address formatting). These rows will be skipped (not imported).{/ts}
            {if $invalidRowCount}
                <div class="action-link"><a href="{$downloadErrorRecordsUrl}">&raquo; {ts}Download Errors{/ts}</a></div>
            {/if}
        </td>
    </tr>
    {/if}

    {if $conflictRowCount}
    <tr class="error"><td class="label">{ts}Conflicting Rows{/ts}</td>
        <td class="data">{$conflictRowCount}</td>
        <td class="explanation">{ts}Rows with conflicting email addresses within this file. These rows will be skipped (not imported).{/ts}
            {if $conflictRowCount}
                <div class="action-link"><a href="{$downloadConflictRecordsUrl}">&raquo; {ts}Download Conflicts{/ts}</a></div>
            {/if}
        </td>
    </tr>
    {/if}

    <tr>
    <td class="label">{ts}Valid Rows{/ts}</td>
        <td class="data">{$validRowCount}</td>
        <td class="explanation">{ts}Total rows to be imported.{/ts}</td>
    </tr>
 </table>

 {* Table for mapping preview *}
 {include file="CRM/Contact/Import/Form/MapTable.tpl"}

 {* Group options *}
 {* New Group *}
<div id="new-group" class="crm-accordion-wrapper collapsed">
 <div class="crm-accordion-header">
    {ts}Add imported records to a new group{/ts}
 </div><!-- /.crm-accordion-header -->
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
            </table>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->


      {* Existing Group *}

<div id="existing-groups" class="crm-accordion-wrapper crm-existing_group-accordion {if $form.groups} {else}collapsed{/if}">
 <div class="crm-accordion-header">
  {$form.groups.label}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">

        <div class="form-item">
        <table><tr><td style="width: 14em;"></td><td>{$form.groups.html}</td></tr></table>
        </div>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

    {* Tag options *}
    {* New Tag *}
<div id="new-tag" class="crm-accordion-wrapper collapsed">
 <div class="crm-accordion-header">
  {ts}Create a new tag and assign it to imported records{/ts}
 </div><!-- /.crm-accordion-header -->
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
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
    {* Existing Tag Imported Contact *}

<div id="existing-tags" class="crm-accordion-wrapper collapsed">
 <div class="crm-accordion-header">
  {ts}Tag imported records{/ts}
</div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">

        <table class="form-layout-compressed">
            <tr><td style="width: 14em;"></td>
             <td class="listing-box" style="margin-bottom: 0em; width: 15em;">
        {foreach from=$form.tag item="tag_val"}
          <div>{$tag_val.html}</div>
        {/foreach}
            </td>
          </tr>
        </table>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div> {* End of preview-info div. We hide this on form submit. *}

<div class="crm-submit-buttons">
   {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>

{literal}
<script type="text/javascript">

{/literal}{if $invalidGroupName}{literal}
cj("#new-group.collapsed").crmAccordionToggle();
{/literal}{/if}{literal}

{/literal}{if $invalidTagName}{literal}
cj("#new-tag.collapsed").crmAccordionToggle();
{/literal}{/if}{literal}

</script>
{/literal}
