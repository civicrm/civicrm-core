{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !empty($settingPage.intro_text)}
  <div class="help">
    {$settingPage.intro_text|escape}
    {if !empty($settingPage.doc_url)}
      {docURL params=$settingPage.doc_url}
    {/if}
  </div>
{/if}
<div class="crm-block crm-form-block crm-setting-form-block-{$settingPageName}">
  {foreach from=$settingSections key="sectionName" item="section"}
    <div class="crm-setting-section crm-setting-section-{$sectionName}">
      {if !empty($section.title)}
        <h3>
          {if !empty($section.icon)}<i class="crm-i {$section.icon}" role="img" aria-hidden="true"></i>&nbsp;{/if}
          {$section.title|escape}
        </h3>
      {/if}
      {if !empty($section.description)}
        <div class="description">
          {$section.description|escape}
        </div>
      {/if}
      <table class="form-layout-compressed">
        {foreach from=$section.fields key="setting_name" item="fieldSpec"}
          {include file="CRM/Admin/Form/Setting/SettingField.tpl"}
        {/foreach}
      </table>
    </div>
  {/foreach}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
