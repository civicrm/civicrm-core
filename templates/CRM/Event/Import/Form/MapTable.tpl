{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Event Import Wizard - Data Mapping table used by MapFields.tpl and Preview.tpl *}
<div class="crm-block crm-form-block crm-event-import-maptable-form-block">
<div id="map-field">
    {strip}
    <table>
    {if $loadedMapping}
        <tr class="columnheader-dark"><th colspan="4">{ts 1=$savedName}Saved Field Mapping: %1{/ts}</td></tr>
    {/if}
        <tr class="columnheader">
            {section name=rows loop=$rowDisplayCount}
       {if $skipColumnHeader }
                   { if $smarty.section.rows.iteration == 1 }
                     <th>{ts}Column Headers{/ts}</th>
                   {else}
                     <th>{ts 1=$smarty.section.rows.iteration}Import Data (row %1){/ts}</th>
                   {/if}
          {else}
                  <th>{ts 1=$smarty.section.rows.iteration}Import Data (row %1){/ts}</th>
                {/if}
            {/section}

            <th>{ts}Matching CiviCRM Field{/ts}</th>
        </tr>

        {*Loop on columns parsed from the import data rows*}
        {section name=cols loop=$columnCount}
            {assign var="i" value=$smarty.section.cols.index}
            <tr style="border-bottom: 1px solid #92B6EC;">

                {section name=rows loop=$rowDisplayCount}
                    {assign var="j" value=$smarty.section.rows.index}
                    <td class="{if $skipColumnHeader AND $smarty.section.rows.iteration == 1}even-row labels{else}odd-row{/if}">{$dataValues[$j][$i]}</td>
                {/section}

                {* Display mapper <select> field for 'Map Fields', and mapper value for 'Preview' *}
                <td class="form-item even-row{if $wizard.currentStepName == 'Preview'} labels{/if}">
                    {if $wizard.currentStepName == 'Preview'}
                        {$mapper[$i]}
                    {else}
                        {$form.mapper[$i].html}
                    {/if}
                </td>

            </tr>
        {/section}

    </table>
  {/strip}

    {if $wizard.currentStepName != 'Preview'}
    <div>

      {if $loadedMapping}
          <span>{$form.updateMapping.html} &nbsp;&nbsp; {$form.updateMapping.label}</span>
      {/if}
      <span>{$form.saveMapping.html} &nbsp;&nbsp; {$form.saveMapping.label}</span>
      <div id="saveDetails" class="form-item">
     <table class="form-layout">
            <tr class="crm-event_map_table-form-block-saveMappingName">
           <td class="label">{$form.saveMappingName.label}</td><td>{$form.saveMappingName.html}</td>
        </tr>
        <tr class="crm-event_map_table-form-block-saveMappingDesc">
           <td class="label">{$form.saveMappingDesc.label}</td><td>{$form.saveMappingDesc.html}</td>
            </tr>
     </table>
      </div>
      <script type="text/javascript">
             {if $mappingDetailsError }
                cj('#saveDetails').show();
             {else}
              cj('#saveDetails').hide();
             {/if}

           {literal}
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
             {/literal}
       {include file="CRM/common/highLightImport.tpl"}
      </script>
    </div>
    {/if}
 </div>
</div>
