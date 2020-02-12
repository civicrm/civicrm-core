{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Export Wizard - Data Mapping table used by MapFields.tpl and Preview.tpl *}
 <div id="map-field">
    {strip}
    <table>
        {if $loadedMapping}
            <tr class="columnheader-dark"><th colspan="4">{ts 1=$savedName}Using Field Mapping: %1{/ts}</td></tr>
        {/if}
        <tr class="columnheader">
            <th>{ts}Fields to Include in Export File{/ts}</th>
        </tr>
        {*section name=cols loop=$columnCount*}
        {section name=cols loop=$columnCount.1}
            {assign var="i" value=$smarty.section.cols.index}
            <tr>
                <td class="form-item even-row">
                   {$form.mapper.1[$i].html}
                </td>
            </tr>
        {/section}

        <tr>
           <td class="form-item even-row underline-effect">
               {$form.addMore.1.html}
           </td>
        </tr>
    </table>
    {/strip}


    <div>
  {if $loadedMapping}
            <span>{$form.updateMapping.html}{$form.updateMapping.label}&nbsp;&nbsp;&nbsp;</span>
  {/if}
  <span>{$form.saveMapping.html}{$form.saveMapping.label}</span>
    <div id="saveDetails" class="form-item">
      <table class="form-layout-compressed">
         <tr><td class="label">{$form.saveMappingName.label}</td><td>{$form.saveMappingName.html}</td></tr>
         <tr><td class="label">{$form.saveMappingDesc.label}</td><td>{$form.saveMappingDesc.html}</td></tr>
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
    cj('Select[id^="mapper[1]"][id$="[1]"]').addClass('huge');
  </script>
    </div>

 </div>
