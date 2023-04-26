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
              {if $showColumnNames}
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
                  {if $showColumnNames}
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
            cj('#updateMapping').change(function() {
              cj('#saveMapping').prop("checked", !this.checked).change();
            });
            cj('#saveMapping').change(function() {
              if (this.checked) {
                cj('#saveDetails').show();
                cj('#updateMapping').prop('checked', false);
                cj('#saveMappingName').removeAttr('disabled')
                cj('#saveMappingDesc').removeAttr('disabled')
              } else {
                cj('#saveDetails').hide();
                cj('#saveMappingName').attr('disabled','disabled');
                cj('#saveMappingDesc').attr('disabled','disabled');
              }
            });
            // Load in update mode if we have already saved the name - ie gone forwards & back.
            if (cj('#saveMappingName').val()) {
              cj('#updateMapping').prop("checked", true).change();
            }
            cj('select[id^="mapper"][id$="[0]"]').addClass('huge');
            {/literal}
            {include file="CRM/common/highLightImport.tpl"}
          </script>
        </div>
      {/if}


  </div>
</div>
