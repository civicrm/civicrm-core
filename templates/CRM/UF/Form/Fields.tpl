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
      <div class="crm-section editrow_{$profileFieldName}-section form-item" id="editrow-{$rowIdentifier}">
        <div class="label option-label">{$formElement.label}</div>
        <div class="content 3">

          {assign var="count" value=1}
          {strip}
            <table class="form-layout-compressed">
              <tr>
                {* sort by fails for option per line. Added a variable to iterate through the element array*}
                {foreach name=outer key=key item=item from=$formElement}
                  {* There are both numeric and non-numeric keys mixed in here, where the non-numeric are metadata that aren't arrays with html members. *}
                  {if is_array($item) && array_key_exists('html', $item)}
                <td class="labels font-light">{$formElement.$key.html}</td>
                {if $count == $field.options_per_line}
              </tr>
              <tr>
                {assign var="count" value=1}
                {else}
                {assign var="count" value=$count+1}
                {/if}
                {/if}
                {/foreach}
              </tr>
            </table>
          {/strip}
        </div>
        <div class="clear"></div>
      </div>
    {else}
      <div class="crm-section editrow_{$profileFieldName}-section form-item" id="editrow-{$rowIdentifier}">
        <div class="label">
          {$formElement.label}
        </div>
        <div class="content">
          {if $profileFieldName|substr:0:3 eq 'im-'}
            {assign var="provider" value=profileFieldNamen|cat:"-provider_id"}
            {$form.$provider.html}&nbsp;
          {/if}

          {if $profileFieldName eq 'email_greeting' or  $profileFieldName eq 'postal_greeting' or $profileFieldName eq 'addressee'}
            {include file="CRM/Profile/Form/GreetingType.tpl"}
          {elseif ($profileFieldName eq 'group' && $form.group) || ($profileFieldName eq 'tag' && $form.tag)}
            {include file="CRM/Contact/Form/Edit/TagsAndGroups.tpl" type=$profileFieldName title=null context="profile"}
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
          {elseif $profileFieldName|substr:0:5 eq 'phone'}
            {assign var="phone_ext_field" value=$profileFieldName|replace:'phone':'phone_ext'}
            {$formElement.html}
            {if $form.$phone_ext_field.html}
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
