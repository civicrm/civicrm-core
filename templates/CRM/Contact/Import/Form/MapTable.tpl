{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-import-maptable-form-block">

{* Import Wizard - Data Mapping table used by MapFields.tpl and Preview.tpl *}
  <div id="map-field">
    {strip}
    <table class="selector">
    {if $savedMappingName}
      <tr class="columnheader-dark"><th colspan="4">{ts 1=$savedMappingName}Saved Field Mapping: %1{/ts}</td></tr>
    {/if}

    {* Header row - has column for column names if they have been supplied *}
    <tr class="columnheader">
      {if $columnNames}
        <td>{ts}Column Names{/ts}</td>
      {/if}
      {foreach from=$dataValues item=row key=index}
        {math equation="x + y" x=$index y=1 assign="rowNumber"}
        <td>{ts 1=$rowNumber}Import Data (row %1){/ts}</td>
      {/foreach}
      <td>{ts}Matching CiviCRM Field{/ts}</td>
    </tr>

    {*Loop on columns parsed from the import data rows*}
    {foreach from=$mapper key=i item=mapperField}
      <tr style="border: 1px solid #DDDDDD;">
        {if array_key_exists($i, $columnNames)}
          <td class="even-row labels">{$columnNames[$i]}</td>
        {/if}
        {foreach from=$dataValues item=row key=index}
          <td class="odd-row">{$row[$i]|escape}</td>
        {/foreach}

        {* Display mapper <select> field for 'Map Fields', and mapper value for 'Preview' *}
        <td class="form-item even-row{if $wizard.currentStepName == 'Preview'} labels{/if}">
          {if $wizard.currentStepName == 'Preview'}
            {$mapperField}
          {else}
            {$mapperField.html|smarty:nodefaults}
          {/if}
        </td>
      </tr>
    {/foreach}
  </table>
  {/strip}

  {if $wizard.currentStepName != 'Preview'}
    <div>
      {if $savedMappingName}
        <span>{$form.updateMapping.html} &nbsp;&nbsp; {$form.updateMapping.label}</span>
      {/if}
      <span>{$form.saveMapping.html} &nbsp;&nbsp; {$form.saveMapping.label}</span>
      <div id="saveDetails" class="form-item">
        <table class="form-layout-compressed">
          <tr class="crm-import-maptable-form-block-saveMappingName">
            <td class="label">{$form.saveMappingName.label}</td>
            <td>{$form.saveMappingName.html}</td>
          </tr>
          <tr class="crm-import-maptable-form-block-saveMappingName">
            <td class="label">{$form.saveMappingDesc.label}</td>
            <td>{$form.saveMappingDesc.html}</td>
          </tr>
        </table>
      </div>
        {literal}
        <script type="text/javascript">
          if (cj('#saveMapping').prop('checked')) {
            cj('#saveDetails').show();
          } else {
            cj('#saveDetails').hide();
          }

          function showSaveDetails(chkbox) {
            if (chkbox.checked) {
              document.getElementById("saveDetails").style.display = "block";
              document.getElementById("saveMappingName").disabled = false;
              document.getElementById("saveMappingDesc").disabled = false;
            } else {
              document.getElementById("saveDetails").style.display = "none";
              document.getElementById("saveMappingName").disabled = true;
              document.getElementById("saveMappingDesc").disabled = true;
             }
          }
          cj('select[id^="mapper"][id$="[0]"]').addClass('huge');
        {/literal}
        {include file="CRM/common/highLightImport.tpl" relationship=true}

        {* // Set default location type *}
        {literal}
        CRM.$(function($) {
          var defaultLocationType = "{/literal}{$defaultLocationType}{literal}";
          if (defaultLocationType.length) {
            $('#map-field').on('change', 'select[id^="mapper"][id$="_0"]', function() {
              var select = $(this).next();
              $('option', select).each(function() {
              if ($(this).attr('value') == defaultLocationType
                && $(this).text() == {/literal}{$defaultLocationTypeLabel|@json_encode}{literal}) {
                select.val(defaultLocationType);
              }
            });
          });
        }
      });
      {/literal}
      </script>
    </div>
    {/if}
  </div>
</div>
