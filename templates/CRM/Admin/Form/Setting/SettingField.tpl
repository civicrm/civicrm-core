{* Display setting field from metadata - todo consolidate with CRM_Core_Form_Field.tpl *}
<tr class="crm-setting-form-block-{$setting_name}">
  <td class="label">
    {$form.$setting_name.label}
    {if array_key_exists('help_text', $fieldSpec) && $fieldSpec.help_text}
      {* @todo the appended -id here appears to be inconsistent in the hlp files *}
      {assign var='tplhelp_id' value = $setting_name|cat:'-id'|replace:'_':'-'}{help id="$tplhelp_id"}
    {/if}
  </td>
  <td>
    {if array_key_exists('wrapper_element', $fieldSpec) && !empty($fieldSpec.wrapper_element)}
      {$fieldSpec.wrapper_element.0|smarty:nodefaults}{$form.$setting_name.html}{$fieldSpec.wrapper_element.1|smarty:nodefaults}
    {else}
      {$form.$setting_name.html}
    {/if}
    <div class="description">
      {$fieldSpec.description}
    </div>
  </td>
</tr>
