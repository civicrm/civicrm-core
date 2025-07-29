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
        <td>{$form.dataSource.html} {help id='data-source-selection'  file='CRM/Contact/Import/Form/DataSource'}</td>
      </tr>
    </table>
  </div>

  {* Data source form pane is injected here when the data source is selected. *}
  <div id="data-source-form-block">
  </div>
  <div id="common-form-controls" class="form-item">
    {if array_key_exists('multipleCustomData', $form) || array_key_exists('userJobTemplate', $form)}<h3>{ts}Import Options{/ts}</h3>{/if}
    <table class="form-layout-compressed">
      {if array_key_exists('multipleCustomData', $form)}
        <tr class="crm-import-uploadfile-form-block-multipleCustomData">
          <td class="label">{$form.multipleCustomData.label}</td>
          <td><span>{$form.multipleCustomData.html}</span> </td>
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

  </script>
  {/literal}
  {if array_key_exists('use_existing_upload', $form)}
    {* If the there is already an uploaded file then check the box when the form loads. This will
    cause it be checked regardless of whether they checked it last time (we assume they want
    to re-use) and also triggers the hide script for the dataSource field *}
  {literal}<script type="text/javascript">CRM.$('#use_existing_upload').prop('checked',true).change();</script>{/literal}
  {/if}
</div>
