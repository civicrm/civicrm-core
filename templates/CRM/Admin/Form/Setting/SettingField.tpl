{* Display setting field from metadata *}
<tr class="crm-setting-form-block-{$setting_name}">
  <td class="label">
    {$form.$setting_name.label}
    {if !empty($fieldSpec.help_text) || !empty($fieldSpec.help_markup)}
      {help id="$setting_name" title=$fieldSpec.title file='CRM/Admin/Form/Setting/SettingField'}
    {/if}
  </td>
  <td>
    {if !empty($readOnlyFields) && in_array($setting_name, $readOnlyFields)}
      <i class="crm-i fa-lock disabled" role="img" aria-hidden="true"></i>
    {/if}
    {if !empty($fieldSpec.wrapper_element)}
      {$fieldSpec.wrapper_element.0 nofilter}{$form.$setting_name.html}{$fieldSpec.wrapper_element.1 nofilter}
    {else}
      {$form.$setting_name.html nofilter}
    {/if}
    {if !empty($fieldSpec.description)}
      <div class="description">
        {$fieldSpec.description|escape}
      </div>
    {/if}
  </td>
</tr>
