{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* Import Wizard - Step 1 (choose data source) *}
<div class="crm-block crm-form-block crm-import-datasource-form-block">

  {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
  {include file="CRM/common/WizardHeader.tpl"}
  {if $errorMessage}
    <div class="messages warning no-popup">
      {$errorMessage}
    </div>
  {/if}
  <div class="help">
    {ts 1=$importEntity 2=$importEntities}The %1 Import Wizard allows you to easily upload %2 from other applications into CiviCRM.{/ts}
  </div>
  <div id="choose-data-source" class="form-item">
    <h3>{ts}Choose Data Source{/ts}</h3>
    <table class="form-layout">
      {if array_key_exists('use_existing_upload', $form)}
        <tr class="crm-import-datasource-form-block-use_existing_upload">
          <td class="label">{$form.use_existing_upload.label}</td>
          <td>{$form.use_existing_upload.html}</td>
          {* If the there is already an uploaded file then check the box when the form loads. This will
          cause it be checked regardless of whether they checked it last time (we assume they want
          to re-use) and also triggers the hide script for the dataSource field *}
          {literal}<script type="text/javascript">CRM.$('#use_existing_upload').prop('checked',true).change();</script>{/literal}
        </tr>
      {/if}
      <tr class="crm-import-datasource-form-block-dataSource">
        <td class="label">{$form.dataSource.label}</td>
        <td>{$form.dataSource.html} {help id='dataSource' file='CRM/Contact/Import/Form/DataSource'}</td>
      </tr>
    </table>
  </div>

    {* Data source form pane is injected here when the data source is selected. *}
  <div id="data-source-form-block">
  </div>
  <div id="common-form-controls" class="form-item">
    <h3>{ts}Import Options{/ts}</h3>
    <table class="form-layout-compressed">
      {if array_key_exists('contactType', $form)}
        <tr class="crm-import-uploadfile-from-block-contactType">
          <td class="label">{$form.contactType.label}</td>
          <td>{$form.contactType.html} {help id='contactType' file='CRM/Contact/Import/Form/DataSource'}<br />
            {if $importEntity !== 'Contact'}
              <span class="description">
                {ts 1=$importEntities}Select 'Individual' if you are importing %1 made by individual persons.{/ts}
                {ts 1=$importEntities}Select 'Organization' or 'Household' if you are importing %1 to contacts of that type.{/ts}
              </span>
            {/if}
          </td>
        </tr>
      {/if}
      {if array_key_exists('contactSubType', $form)}
        <tr>
          <td class="label">{$form.contactSubType.label}</td>
          <td><span id="contact-subtype">{$form.contactSubType.html} {help id='contactSubType' file="CRM/Contact/Import/Form/DataSource"}</span></td>
        </tr>
      {/if}

      {if array_key_exists('onDuplicate', $form)}
        <tr class="crm-import-uploadfile-from-block-onDuplicate">
          <td class="label">{$form.onDuplicate.label}</td>
          <td>{$form.onDuplicate.html} {help id="onDuplicate" file="CRM/Contact/Import/Form/DataSource"}</td>
        </tr>
      {/if}
      {if array_key_exists('dedupe_rule_id', $form)}
        <tr class="crm-import-datasource-form-block-dedupe">
          <td class="label">{$form.dedupe_rule_id.label}</td>
          <td><span id="contact-dedupe_rule_id">{$form.dedupe_rule_id.html}</span> {help id='dedupe_rule_id' file="CRM/Contact/Import/Form/DataSource"}</td>
        </tr>
      {/if}
      {if array_key_exists('multipleCustomData', $form)}
        <tr class="crm-import-uploadfile-form-block-multipleCustomData">
          <td class="label">{$form.multipleCustomData.label}</td>
          <td><span>{$form.multipleCustomData.html}</span> </td>
        </tr>
      {/if}
      <tr class="crm-import-uploadfile-form-block-date">{include file="CRM/Core/Date.tpl"}</tr>

      {if array_key_exists('doGeocodeAddress', $form)}
          <tr class="crm-import-datasource-form-block-doGeocodeAddress">
            <td class="label"></td>
            <td>{$form.doGeocodeAddress.html} {$form.doGeocodeAddress.label}<br />
              <span class="description">
              {ts}This option is not recommended for large imports. Use the command-line geocoding script instead.{/ts}
            </span>
                {docURL page="user/initial-set-up/scheduled-jobs"}
            </td>
          </tr>
        {/if}
        {if array_key_exists('disableUSPS', $form)}
          <tr class="crm-import-datasource-form-block-disableUSPS">
            <td class="label"></td>
            <td>{$form.disableUSPS.html} <label for="disableUSPS">{$form.disableUSPS.label}</label><br />
              &nbsp;&nbsp;&nbsp; <span class="description">{ts}Uncheck at your own risk as batch processing violates USPS API TOS.{/ts}</span>
            </td>
          </tr>
        {/if}
       {if array_key_exists('savedMapping', $form)}
         <tr class="crm-import-uploadfile-form-block-savedMapping">
           <td class="label"><label for="savedMapping">{$form.savedMapping.label}</label></td>
           <td>{$form.savedMapping.html}</td>
         </tr>
       {/if}
      {if array_key_exists('userJobTemplate', $form)}
        <tr class="crm-import-uploadfile-form-block-userJobTemplate">
          <td class="label"><label for="userJobTemplate">{$form.userJobTemplate.label}</label></td>
          <td>{$form.userJobTemplate.html}</td>
        </tr>
      {/if}
    </table>
  </div>
  <div class="spacer"></div>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      // build data source form block
      buildDataSourceFormBlock();
      buildSubTypes();
      buildDedupeRules();
    });

    function buildDataSourceFormBlock(dataSource) {
      var dataUrl = {/literal}"{crmURL p=$urlPath h=0 q=$urlPathVar|smarty:nodefaults}"{literal};

      if (!dataSource) {
        var dataSource = CRM.$("#dataSource").val();
      }

      if (dataSource) {
        dataUrl = dataUrl + '&dataSource=' + dataSource;
      } else {
        CRM.$("#data-source-form-block").html('');
        return;
      }

      CRM.$("#data-source-form-block").load(dataUrl);
    }

    function buildSubTypes( )
    {
      const element = cj('input[name="contactType"]:checked');
      if (!element.length) {
        // There are no contact fields on some import forms (e.g. import of activities)
        return;
      }

      const elementVal = element.val( );
      const postUrl = {/literal}"{crmURL p='civicrm/ajax/subtype' h=0}"{literal};
      const param = 'parentId='+ elementVal;
      cj.ajax({ type: "POST", url: postUrl, data: param, async: false, dataType: 'json',
        success: function(subtype)
        {
          if ( subtype.length === 0 ) {
            cj("#contactSubType").empty();
            cj("#contact-subtype").hide();
          }
          else {
            cj("#contact-subtype").show();
            cj("#contactSubType").empty();
            cj("#contactSubType").append("<option value=''>- {/literal}{ts escape='js'}select{/ts}{literal} -</option>");
            for ( var key in  subtype ) {
              // stick these new options in the subtype select
              cj("#contactSubType").append("<option value="+key+">"+subtype[key]+" </option>");
            }
          }
        }
      });
    }

    function buildDedupeRules( )
    {
      element = cj("input[name=contactType]:checked").val();
      var postUrl = {/literal}"{crmURL p='civicrm/ajax/dedupeRules' h=0}"{literal};
      var param = 'parentId='+ element;
      cj.ajax({ type: "POST", url: postUrl, data: param, async: false, dataType: 'json',
        success: function(dedupe){
          if ( dedupe.length === 0 ) {
            cj("#dedupe_rule_id").empty();
            cj("#contact-dedupe").hide();
          } else {
            cj("#contact-dedupe").show();
            cj("#dedupe_rule_id").empty();

            cj("#dedupe_rule_id").append("<option value=''>- {/literal}{ts escape='js'}select{/ts}{literal} -</option>");
            for ( var key in  dedupe ) {
              // stick these new options in the dedupe select
              cj("#dedupe_rule_id").append("<option value="+key+">"+dedupe[key]+" </option>");
            }
          }
        }
      });
    }
  </script>
{/literal}
  {if array_key_exists('use_existing_upload', $form)}
    {* If the there is already an uploaded file then check the box when the form loads. This will
    cause it be checked regardless of whether they checked it last time (we assume they want
    to re-use) and also triggers the hide script for the dataSource field *}
    {literal}<script type="text/javascript">CRM.$('#use_existing_upload').prop('checked',true).change();</script>{/literal}
  {/if}
</div>
