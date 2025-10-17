{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-setting-block crm-setting-block-{$settingPageName}">
{crmRegion name="crm-setting-form-$settingPageName-top"}
  {if !empty($readOnlyFields)}
    <div class="description">
      <i class="crm-i fa-lock" role="img" aria-hidden="true"></i>
      {ts}Some fields are loaded as 'readonly' as they have been set (overridden) in civicrm.settings.php.{/ts}
    </div>
  {/if}
{/crmRegion}
  {foreach from=$settingSections key="sectionName" item="section"}
    <div class="crm-setting-section crm-setting-section-{$sectionName}">
      {crmRegion name="crm-setting-$settingPageName-section-$sectionName"}
        {if !empty($section.title)}
          <h3>
            {if !empty($section.icon)}<i class="crm-i {$section.icon}" role="img" aria-hidden="true"></i>&nbsp;{/if}
            {$section.title|escape}
          </h3>
        {/if}
        {if !empty($section.description) || !empty($section.doc_url)}
          <div class="description">
            {if !empty($section.description)}{$section.description|escape}{/if}
            {if !empty($section.doc_url)}{docURL params=$section.doc_url}{/if}
          </div>
        {/if}
        <table class="form-layout-compressed">
          {foreach from=$section.fields key="setting_name" item="fieldSpec"}
            {if !empty($fieldSpec.template)}
              {include file=$fieldSpec.template}
            {else}
              {include file="CRM/Admin/Form/Setting/SettingField.tpl"}
            {/if}
          {/foreach}
        </table>
      {/crmRegion}
    </div>
  {/foreach}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
