{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing options *}
<div class="crm-block crm-form-block crm-admin-options-form-block">
  {if $action eq 8}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {$deleteMessage|escape}
    </div>
  {else}
    <table class="form-layout-compressed">
        {if $gName eq 'custom_search'}
           <tr class="crm-admin-options-form-block-custom_search_path">
             <td class="label">{ts}Custom Search Path{/ts}</td>
             <td>{$form.label.html}<br />
                <span class="description">{ts}Enter the "class path" for this custom search here.{/ts}
             </td>
           </tr>
        {elseif $gName eq 'redaction_rule'}
           <tr class="crm-admin-options-form-block-expression">
             <td class="label">{ts}Match Value or Expression{/ts} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</td>
             <td>{$form.label.html}<br />
                <span class="description">{ts}A "string value" or regular expression to be redacted (replaced).{/ts}</span>
             </td>
           </tr>
        {elseif !empty($form.label)}
           <tr class="crm-admin-options-form-block-label">
             <td class="label">{$form.label.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</td>
             <td class="html-adjust">{$form.label.html}<br />
               <span class="description">{ts}The option label is displayed to users.{/ts}</span>
             </td>
           </tr>
     {if !empty($form.financial_account_id.html)}
             <tr class="crm-admin-options-form-block-grouping">
               <td class="label">{$form.financial_account_id.label}</td>
               <td>{$form.financial_account_id.html}</td>
             </tr>
     {/if}
        {/if}

      {if !empty($form.value.html) && $gName neq 'redaction_rule'}
        <tr class="crm-admin-options-form-block-value">
          <td class="label">{$form.value.label}</td>
          <td>{$form.value.html}<br />
            {if $action == 2}
              <span class="description"><i class="crm-i fa-exclamation-triangle" aria-hidden="true"></i> {ts}Changing the Value field will unlink records which have been marked with this option. This change can not be undone except by restoring the previous value.{/ts}</span>
            {/if}
          </td>
        </tr>
      {/if}

        {if $gName eq 'custom_search'}
           <tr class="crm-admin-options-form-block-search_title">
             <td class="label">{ts}Search Title{/ts}</td>
             <td>{$form.description.html}<br />
               <span class="description">{ts}This title is displayed to users in the Custom Search listings.{/ts}</span>
             </td>
           </tr>
        {else}
          {if $gName eq 'redaction_rule'}
            <tr class="crm-admin-options-form-block-replacement">
               <td class="label">{ts}Replacement (prefix){/ts}</td>
               <td>{$form.value.html}<br />
                 <span class="description">{ts}Matched values are replaced with this prefix plus a unique code. EX: If replacement prefix for &quot;Vancouver&quot; is <em>city_</em>, occurrences will be replaced with <em>city_39121</em>.{/ts}</span>
               </td>
            </tr>
          {/if}
            {if !empty($form.name.html)} {* Get the name value also *}
              <tr class="crm-admin-options-form-block-name">
                <td class="label">{$form.name.label}</td>
                <td>{$form.name.html}<br />
                   <span class="description">{ts}The class name which implements this functionality.{/ts}</span>
                </td>
              </tr>
            {/if}
            {if !empty($form.filter.html)} {* Filter property is only exposed for some option groups. *}
              <tr class="crm-admin-options-form-block-filter">
                <td class="label">{$form.filter.label}</td>
                <td>{$form.filter.html}</td>
              </tr>
            {/if}
              <tr class="crm-admin-options-form-block-desciption">
                <td class="label">{$form.description.label}</td>
                <td>{$form.description.html}<br />
            {if $gName eq 'activity_type'}
               <span class="description">{ts}Description is included at the top of the activity edit and view pages for this type of activity.{/ts}</span>
            {elseif $gName eq 'email_greeting' || $gName eq 'postal_greeting' || $gName eq  'addressee'}
                <span class="description">{ts}Description will be appended to processed greeting.{/ts}</span>
            {/if}
                </td>
              </tr>
        {/if}
        {if $gName eq 'participant_status'}
              <tr class="crm-admin-options-form-block-visibility_id">
                <td class="label">{$form.visibility_id.label}</td>
                <td>{$form.visibility_id.html}</td>
              </tr>
        {/if}
        {if !empty($form.grouping.html)}
          <tr class="crm-admin-options-form-block-grouping">
            <td class="label">{$form.grouping.label}</td>
            <td>{$form.grouping.html}</td>
          </tr>
        {/if}
        {if !empty($form.weight)}
              <tr class="crm-admin-options-form-block-weight">
                <td class="label">{$form.weight.label}</td>
                <td>{$form.weight.html}</td>
              </tr>
        {/if}
        {if !empty($form.icon.html)}
          <tr class="crm-admin-options-form-block-icon">
            <td class="label">{$form.icon.label}</td>
            <td>{$form.icon.html}</td>
          </tr>
        {/if}
        {if !empty($form.color.html)}
          <tr class="crm-admin-options-form-block-color">
            <td class="label">{$form.color.label}</td>
            <td>{$form.color.html}</td>
          </tr>
        {/if}
        {if !empty($form.component_id.html)} {* Component ID is exposed for activity types if CiviCase is enabled. *}
              <tr class="crm-admin-options-form-block-component_id">
                <td class="label">{$form.component_id.label}</td>
                <td>{$form.component_id.html}</td>
              </tr>
        {/if}
              <tr class="crm-admin-options-form-block-is_active">
                <td class="label">{$form.is_active.label}</td>
                <td>{$form.is_active.html}</td>
              </tr>
        {if !empty($showDefault)}
              <tr class="crm-admin-options-form-block-is_default">
                <td class="label">{$form.is_default.label}</td>
                <td>{$form.is_default.html}</td>
              </tr>
        {/if}
        {if !empty($showContactFilter)}{* contactOptions is exposed for email/postal greeting and addressee types to set filter for contact types *}
           <tr class="crm-admin-options-form-block-contactOptions">
             <td class="label">{$form.contact_type_id.label}</td>
             <td>{$form.contact_type_id.html}</td>
           </tr>
        {/if}
        <tr class="crm-admin-options-form-block-custom_data">
          <td colspan="2">
            {include file="CRM/common/customDataBlock.tpl" customDataType='OptionValue' cid=false}
          </td>
        </tr>
    </table>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
