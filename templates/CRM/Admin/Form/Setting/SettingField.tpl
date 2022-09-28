{* Display setting field from metadata - todo consolidate with CRM_Core_Form_Field.tpl *}
<tr class="crm-setting-form-block-{$setting_name}">
  <td class="label">{$form.$setting_name.label}</td>
  <td>
    {if !empty($fieldSpec.wrapper_element)}
      {$fieldSpec.wrapper_element.0}{$form.$setting_name.html}{$fieldSpec.wrapper_element.1}
    {else}
      {$form.$setting_name.html}
    {/if}
    <div class="description">
      {$fieldSpec.description}
    </div>
    {if $fieldSpec.help_text}
      {* @todo the appended -id here appears to be inconsistent in the hlp files *}
      {assign var='tplhelp_id' value = $setting_name|cat:'-id'|replace:'_':'-'}{help id="$tplhelp_id"}
    {/if}
  </td>
</tr>
