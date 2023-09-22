{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-import-mapfield-form-block">
    {* Import Wizard - Step 2 (map incoming data fields) *}
    {* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

    {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
    {include file="CRM/common/WizardHeader.tpl"}

  <div class="help">
    <p>{ts}Review the values shown below from the first 2 rows of your import file and select the matching CiviCRM database fields from the drop-down lists in the right-hand column. Select '- do not import -' for any columns in the import file that you want ignored.{/ts}</p>
    <p>{ts}If you think you may be importing additional data from the same data source, check 'Save this field mapping' at the bottom of the page before continuing. The saved mapping can then be easily reused the next time data is imported.{/ts}</p>
  </div>
  {* Table for mapping data to CRM fields *}
  {include file="CRM/Import/Form/MapTableCommon.tpl" mapper=$form.mapper}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    {$initHideBoxes|smarty:nodefaults}
    {literal}
      <script type="text/javascript" >
        if ( document.getElementsByName("saveMapping")[0].checked ) {
          document.getElementsByName("updateMapping")[0].checked = true;
          document.getElementsByName("saveMapping")[0].checked = false;
        }
      </script>
    {/literal}
</div>
