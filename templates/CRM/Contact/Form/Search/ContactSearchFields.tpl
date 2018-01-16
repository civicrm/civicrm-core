<tr>
  <td class="font-size12pt">
    {$form.sort_name.label}&nbsp;&nbsp;{$form.sort_name.html|crmAddClass:'twenty'}
  </td>
  <td>{$form.buttons.html}</td>
</tr>
<tr>
  {if $form.contact_tags}
    <td><label>{$form.contact_tags.label}</label>
      {$form.contact_tags.html}
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}

  {if $form.group}
    <td><label>{$form.group.label}</label>
      {$form.group.html}
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}
</tr>
<tr class="crm-event-search-form-block-deleted_contacts">
  <td>{$form.contact_type.label}&nbsp;&nbsp;{$form.contact_type.html}<br></td>
  <td>
    {if $form.deleted_contacts}
      {$form.deleted_contacts.html}&nbsp;&nbsp;{$form.deleted_contacts.label}
    {/if}
  </td>
</tr>
