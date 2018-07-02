{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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

<div class="crm-block crm-form-block crm-import-datasource-form-block">
{if $showOnlyDataSourceFormPane}
  {include file=$dataSourceFormTemplateFile}
{else}
  {* Import Wizard - Step 1 (choose data source) *}
  {* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

  {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
  {include file="CRM/common/WizardHeader.tpl"}
   <div class="help">
      {ts}The Import Wizard allows you to easily import contact records from other applications into CiviCRM. For example, if your organization has contacts in MS Access&reg; or Excel&reg;, and you want to start using CiviCRM to store these contacts, you can 'import' them here.{/ts} {help id='choose-data-source-intro'}
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <div id="choose-data-source" class="form-item">
      <h3>{ts}Choose Data Source{/ts}</h3>
      <table class="form-layout">
        <tr class="crm-import-datasource-form-block-dataSource">
            <td class="label">{$form.dataSource.label}</td>
            <td>{$form.dataSource.html} {help id='data-source-selection'}</td>
        </tr>
      </table>
  </div>

  {* Data source form pane is injected here when the data source is selected. *}
  <div id="data-source-form-block">
    {if $dataSourceFormTemplateFile}
      {include file=$dataSourceFormTemplateFile}
    {/if}
  </div>

  <div id="common-form-controls" class="form-item">
      <h3>{ts}Import Options{/ts}</h3>
      <table class="form-layout-compressed">
         <tr class="crm-import-datasource-form-block-contactType">
       <td class="label">{$form.contactType.label}</td>
             <td>{$form.contactType.html} {help id='contact-type'}&nbsp;&nbsp;&nbsp;
               <span id="contact-subtype">{$form.subType.label}&nbsp;&nbsp;&nbsp;{$form.subType.html} {help id='contact-sub-type'}</span></td>
         </tr>
         <tr class="crm-import-datasource-form-block-onDuplicate">
             <td class="label">{$form.onDuplicate.label}</td>
             <td>{$form.onDuplicate.html} {help id='dupes'}</td>
         </tr>
         <tr class="crm-import-datasource-form-block-dedupe">
             <td class="label">{$form.dedupe.label}</td>
             <td><span id="contact-dedupe">{$form.dedupe.html}</span> {help id='id-dedupe_rule'}</td>
         </tr>
         <tr class="crm-import-datasource-form-block-fieldSeparator">
             <td class="label">{$form.fieldSeparator.label}</td>
             <td>{$form.fieldSeparator.html} {help id='id-fieldSeparator'}</td>
         </tr>
         <tr>{include file="CRM/Core/Date.tpl"}</tr>
         <tr>
             <td></td><td class="description">{ts}Select the format that is used for date fields in your import data.{/ts}</td>
         </tr>

        {if $geoCode}
         <tr class="crm-import-datasource-form-block-doGeocodeAddress">
             <td class="label"></td>
             <td>{$form.doGeocodeAddress.html} {$form.doGeocodeAddress.label}<br />
               <span class="description">
                {ts}This option is not recommended for large imports. Use the command-line geocoding script instead.{/ts}
               </span>
               {docURL page="Managing Scheduled Jobs" resource="wiki"}
            </td>
         </tr>
        {/if}

        {if $savedMapping}
         <tr  class="crm-import-datasource-form-block-savedMapping">
              <td class="label"><label for="savedMapping">{if $loadedMapping}{ts}Select a Different Field Mapping{/ts}{else}{ts}Load Saved Field Mapping{/ts}{/if}</label></td>
              <td>{$form.savedMapping.html}<br />
      &nbsp;&nbsp;&nbsp;<span class="description">{ts}Select Saved Mapping or Leave blank to create a new One.{/ts}</span></td>
         </tr>
        { /if}

        {if $form.disableUSPS}
         <tr  class="crm-import-datasource-form-block-disableUSPS">
              <td class="label"></td>
              <td>{$form.disableUSPS.html} <label for="disableUSPS">{$form.disableUSPS.label}</label></td>
         </tr>

        {/if}
 </table>
  </div>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"} </div>

  {literal}
    <script type="text/javascript">
      CRM.$(function($) {
         //build data source form block
         buildDataSourceFormBlock();
         buildSubTypes();
         buildDedupeRules();
      });

      function buildDataSourceFormBlock(dataSource)
      {
        var dataUrl = {/literal}"{crmURL p=$urlPath h=0 q=$urlPathVar}"{literal};

        if (!dataSource ) {
          var dataSource = cj("#dataSource").val();
        }

        if ( dataSource ) {
          dataUrl = dataUrl + '&dataSource=' + dataSource;
        } else {
          cj("#data-source-form-block").html( '' );
          return;
        }

        cj("#data-source-form-block").load( dataUrl );
      }

      function buildSubTypes( )
      {
        element = cj('input[name="contactType"]:checked').val( );
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/subtype' h=0 }"{literal};
        var param = 'parentId='+ element;
        cj.ajax({ type: "POST", url: postUrl, data: param, async: false, dataType: 'json',

                        success: function(subtype){
                                                   if ( subtype.length == 0 ) {
                                                      cj("#subType").empty();
                                                      cj("#contact-subtype").hide();
                                                   } else {
                                                       cj("#contact-subtype").show();
                                                       cj("#subType").empty();

                                                       cj("#subType").append("<option value=''>- {/literal}{ts escape='js'}select{/ts}{literal} -</option>");
                                                       for ( var key in  subtype ) {
                                                           // stick these new options in the subtype select
                                                           cj("#subType").append("<option value="+key+">"+subtype[key]+" </option>");
                                                       }
                                                   }


                                                 }
  });

      }

      function buildDedupeRules( )
      {
        element = cj("input[name=contactType]:checked").val();
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/dedupeRules' h=0 }"{literal};
        var param = 'parentId='+ element;
        cj.ajax({ type: "POST", url: postUrl, data: param, async: false, dataType: 'json',

                        success: function(dedupe){
                                                   if ( dedupe.length == 0 ) {
                                                      cj("#dedupe").empty();
                                                      cj("#contact-dedupe").hide();
                                                   } else {
                                                       cj("#contact-dedupe").show();
                                                       cj("#dedupe").empty();

                                                       cj("#dedupe").append("<option value=''>- {/literal}{ts escape='js'}select{/ts}{literal} -</option>");
                                                       for ( var key in  dedupe ) {
                                                           // stick these new options in the dedupe select
                                                           cj("#dedupe").append("<option value="+key+">"+dedupe[key]+" </option>");
                                                       }
                                                   }


                                                 }
  });

      }

    </script>
  {/literal}
{/if}
</div>
