{foreach from=$fields item=field key=fieldName}
  {if $field.skipDisplay}
    {continue}
  {/if}

  {assign var=profileFieldName value=$field.name}
  {if $prefix}
    {assign var="formElement" value=$form.$prefix.$profileFieldName}
  {else}
    {assign var="formElement" value=$form.$profileFieldName}
  {/if}

  {if $field.groupTitle != $fieldset}
    {if $fieldset != $zeroField}
      {if $groupHelpPost && $action neq 4}
        <div class="messages help">{$groupHelpPost}</div>
      {/if}
      {if $mode ne 8}
        </fieldset>
      {/if}
    {/if}

    {if $mode ne 8 && $action ne 1028 && $action ne 4 && !$hideFieldset}
      <fieldset class="crm-profile crm-profile-id-{$field.group_id} crm-profile-name-{$field.groupName}"><legend>{$field.groupDisplayTitle}</legend>
    {/if}

    {if ($form.formName eq 'Confirm' OR $form.formName eq 'ThankYou') AND $prefix neq 'honor'}
      <div class="header-dark">{$field.groupTitle} </div>
    {/if}
    {assign var=fieldset  value=`$field.groupTitle`}
    {assign var=groupHelpPost  value=`$field.groupHelpPost`}
    {if $field.groupHelpPre && $action neq 4 && $action neq 1028}
      <div class="messages help">{$field.groupHelpPre}</div>
    {/if}
  {/if}

  {if $field.field_type eq "Formatting"}
    {if $action neq 4 && $action neq 1028}
      {$field.help_pre}
    {/if}
  {elseif $profileFieldName}
    {* Show explanatory text for field if not in 'view' or 'preview' modes *}
    {if $field.help_pre && $action neq 4 && $action neq 1028}
      <div class="crm-section helprow-{$profileFieldName}-section helprow-pre" id="helprow-{$profileFieldName}">
        <div class="content description">{$field.help_pre}</div>
      </div>
    {/if}
    {if $field.options_per_line != 0}
      <div class="crm-section editrow_{$profileFieldName}-section form-item" id="editrow-{$profileFieldName}">
        <div class="label option-label">{$formElement.label}</div>
        <div class="content 3">

          {assign var="count" value="1"}
          {strip}
            <table class="form-layout-compressed">
              <tr>
                {* sort by fails for option per line. Added a variable to iterate through the element array*}
                {assign var="index" value="1"}
                {foreach name=outer key=key item=item from=$formElement}
                {if $index < 10}
                {assign var="index" value=`$index+1`}
                {else}
                <td class="labels font-light">{$formElement.$key.html}</td>
                {if $count == $field.options_per_line}
              </tr>
              <tr>
                {assign var="count" value="1"}
                {else}
                {assign var="count" value=`$count+1`}
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
      <div class="crm-section editrow_{$profileFieldName}-section form-item" id="editrow-{$profileFieldName}">
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
          {elseif $field.is_datetime_field && $action & 4}
            <span class="crm-frozen-field">
              {$formElement.value|crmDate:$field.smarty_view_format}
              <input type="hidden"
               name="{$formElement.name}"
               value="{$formElement.value}" id="{$formElement.name}"
              >
            </span>
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
    {* Show explanatory text for field if not in 'view' or 'preview' modes *}
    {if $field.help_post && $action neq 4 && $action neq 1028}
      <div class="crm-section helprow-{$profileFieldName}-section helprow-post" id="helprow-{$profileFieldName}">
        <div class="content description">{$field.help_post}</div>
      </div>
    {/if}
  {/if}
{/foreach}
