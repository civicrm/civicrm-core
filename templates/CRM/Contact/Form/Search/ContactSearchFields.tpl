<tr>
  <td>
    {$form.sort_name.label}
    <br>
    {$form.sort_name.html|crmAddClass:'twenty'}
  </td>
  <td>{$form.buttons.html}</td>
</tr>
<tr>
  {if !empty($form.contact_tags)}
    <td>
      <label>{$form.contact_tags.label}</label>
      <br>
      {$form.contact_tags.html}
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}

  {if !empty($form.group)}
    <td>
      <label>{$form.group.label}</label>
      <br>
      {$form.group.html}
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}
</tr>
<tr class="crm-event-search-form-block-deleted_contacts">
  <td>
    {$form.contact_type.label}
    <br>
    {$form.contact_type.html}
  </td>
  <td>
    {if !empty($form.deleted_contacts)}
      {$form.deleted_contacts.html}&nbsp;&nbsp;{$form.deleted_contacts.label}
    {/if}
  </td>
</tr>
