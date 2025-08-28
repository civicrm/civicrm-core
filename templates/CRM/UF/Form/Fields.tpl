{foreach from=$fields item=field key=fieldName}
  {if $field.skipDisplay}
    {continue}
  {/if}

  {assign var=profileFieldName value=$field.name}
  {if $prefix}
    {assign var="formElement" value=$form.$prefix.$profileFieldName}
    {assign var="rowIdentifier" value=$form.$prefix.$profileFieldName.id}
  {else}
    {assign var="formElement" value=$form.$profileFieldName}
    {assign var="rowIdentifier" value=$field.name}
  {/if}

  {if $field.field_type eq "Formatting"}
    {if $action neq 4}
      {$field.help_pre}
    {/if}
  {elseif $profileFieldName}
    {* Show explanatory text for field if not in 'view' mode *}
    {if $field.help_pre && $action neq 4}
      <div class="crm-section helprow-{$profileFieldName}-section helprow-pre" id="helprow-{$rowIdentifier}">
        <div class="content description">{$field.help_pre}</div>
      </div>
    {/if}
    {if array_key_exists('options_per_line', $field) && $field.options_per_line != 0}
      <div class="crm-section editrow_{$profileFieldName}-section form-item" id="editrow-{$rowIdentifier}" {if $field.html_type eq 'Radio'}role="radiogroup" aria-labelledby="{$profileFieldName}_group"{/if}>
        <div class="label option-label" {if $field.html_type eq 'Radio' or $field.html_type eq 'CheckBox'}id="{$profileFieldName}_group">{$formElement.label|regex_replace:"/\<(\/|)label\>/":""}{else}>{$formElement.label}{/if}</div>
        <div class="content" {if $field.html_type eq 'CheckBox'}role="group"  aria-labelledby="{$profileFieldName}_group"{/if}>
          {$formElement.html}
        </div>
        <div class="clear"></div>
      </div>
    {else}
      <div class="crm-section editrow_{$profileFieldName}-section form-item" id="editrow-{$rowIdentifier}"  {if $field.html_type eq 'Radio'}role="radiogroup" aria-labelledby="{$profileFieldName}_group"{/if}>
        <div class="label"{if $field.html_type eq 'Radio' or $field.html_type eq 'CheckBox'}id="{$profileFieldName}_group">{$formElement.label|regex_replace:"/\<(\/|)label\>/":""}{else}>{$formElement.label}{/if}</div>
        <div class="content" {if $field.html_type eq 'CheckBox'}role="group"  aria-labelledby="{$profileFieldName}_group"{/if}>
          {if $profileFieldName|str_starts_with:'im-'}
            {assign var="provider" value=profileFieldNamen|cat:"-provider_id"}
            {if array_key_exists($provider, $form)}{$form.$provider.html}{/if}&nbsp;
          {/if}

          {if $profileFieldName eq 'email_greeting' or  $profileFieldName eq 'postal_greeting' or $profileFieldName eq 'addressee'}
            {include file="CRM/Profile/Form/GreetingType.tpl"}
          {elseif $profileFieldName eq 'tag'}
          <table class="form-layout-compressed{if $context EQ 'profile'} crm-profile-tagsandgroups{/if}">
            <tr>
              <td>
            <div class="crm-section tag-section">
              {if !empty($title)}{$form.tag.label}<br>{/if}
              {$form.tag.html}
            </div>
              </td>
            </tr>
          </table>
          {elseif ($profileFieldName eq 'group' && $form.group) || ($profileFieldName eq 'tag' && $form.tag)}
            <table class="form-layout-compressed{if $context EQ 'profile'} crm-profile-tagsandgroups{/if}">
              <tr>
                <td>
            {if $groupElementType eq 'select'}
              <div class="crm-section group-section">
                {if $title}{$form.group.label}<br>{/if}
                {$form.group.html}
              </div>
            {else}
              {foreach key=key item=item from=$tagGroup.group}
                <div class="group-wrapper">
                  {$form.group.$key.html}
                  {if $item.description}
                    <div class="description">{$item.description}</div>
                  {/if}
                </div>
              {/foreach}
            {/if}
                </td>
              </tr>
            </table>
          {elseif array_key_exists('is_datetime_field', $field) && $field.is_datetime_field && $action & 4}
            <span class="crm-frozen-field">
              {$formElement.value|crmDate:$field.smarty_view_format}
              <input type="hidden"
               name="{$formElement.name}"
               value="{$formElement.value}" id="{$formElement.name}"
              >
            </span>
          {elseif ( $profileFieldName eq 'image_URL' )}
            {$formElement.html}
            {if !empty($imageURL)}
              <div class="crm-section contact_image-section">
                <div class="content">
                {include file="CRM/Contact/Page/ContactImage.tpl"}
                </div>
              </div>
            {/if}
          {elseif $profileFieldName|str_starts_with:'phone'}
            {assign var="phone_ext_field" value=$profileFieldName|replace:'phone':'phone_ext'}
            {$formElement.html}
            {if array_key_exists($phone_ext_field, $form)}
              &nbsp;{$form.$phone_ext_field.html}
            {/if}
          {else}
            {if $prefix}
              {if $profileFieldName eq 'organization_name' && !empty($form.onbehalfof_id)}
                {$form.onbehalfof_id.html}
              {/if}
              {if $field.html_type eq 'File' && $viewOnlyPrefixFileValues}
                {$viewOnlyPrefixFileValues.$prefix.$profileFieldName}
              {else}
                {$formElement.html}
              {/if}
            {elseif $field.html_type eq 'File' && $viewOnlyFileValues}
              {$viewOnlyFileValues.$profileFieldName}
            {elseif $field.html_type eq 'Radio' or $field.html_type eq 'CheckBox' && $field.data_type neq "Boolean"}
              <div class="crm-multiple-checkbox-radio-options">
                {foreach name=outer key=key item=item from=$formElement}
                  {if is_array($item) && array_key_exists('html', $item)}
                    {$formElement.$key.html}
                  {/if}
                {/foreach}
              </div>
              {* Include the edit options list for admins *}
              {if $formElement.html|strstr:"crm-option-edit-link"}
                {$formElement.html|regex_replace:"@^.*(<a href=.*? class=.crm-option-edit-link.*?</a>)$@":"$1"}
              {/if}
            {else}
              {$formElement.html}
            {/if}
          {/if}

          {*CRM-4564*}
          {if $field.html_type eq 'Autocomplete-Select'}
            {if $field.data_type eq 'ContactReference'}
              {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $profileFieldName}
            {/if}
          {/if}
        </div>
        <div class="clear"></div>
      </div>
    {/if}
    {* Show explanatory text for field if not in 'view' mode *}
    {if $field.help_post && $action neq 4}
      <div class="crm-section helprow-{$profileFieldName}-section helprow-post" id="helprow-{$rowIdentifier}">
        <div class="content description">{$field.help_post}</div>
      </div>
    {/if}
  {/if}
{/foreach}
