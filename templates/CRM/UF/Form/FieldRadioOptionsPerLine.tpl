<div class="crm-section editrow_{$profileFieldName}-section form-item" id="editrow-{$rowIdentifier}">
  <div class="label option-label">{$formElement.label}</div>
  <div class="content">
    <div class="crm-multiple-checkbox-radio-options crm-options-per-line" style="--crm-opts-per-line:{$field.options_per_line};">
      {foreach name=outer key=key item=item from=$formElement}
        {if is_array($item) && array_key_exists('html', $item)}
          <div class="crm-option-label-pair" >{$formElement.$key.html}</div>
        {/if}
      {/foreach}
    </div>
    {* Include the edit options list for admins *}
    {if $formElement.html|strstr:"crm-option-edit-link"}
      {$formElement.html|regex_replace:"@^.*(<a href=.*? class=.crm-option-edit-link.*?</a>)$@":"$1"}
    {else}
      {$formElement.html}
    {/if}
  </div>
  <div class="clear"></div>
</div>
<div class="clear"></div>
