<tr>
  <td>
    {$form.sort_name.label nofilter}
    <br>
    {$form.sort_name.html|crmAddClass:'twenty' nofilter}
  </td>
  <td>{$form.buttons.html nofilter}</td>
</tr>
<tr>
  {if !empty($form.contact_tags)}
    <td>
      <label>{$form.contact_tags.label nofilter}</label>
      <br>
      {$form.contact_tags.html nofilter}
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}

  {if !empty($form.group)}
    <td>
      <label>{$form.group.label nofilter}</label>
      <br>
      {$form.group.html nofilter}
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}
</tr>
<tr class="crm-event-search-form-block-deleted_contacts">
  <td>
    {$form.contact_type.label nofilter}
    <br>
    {$form.contact_type.html nofilter}
  </td>
  <td>
    {if !empty($form.deleted_contacts)}
      {$form.deleted_contacts.html nofilter}&nbsp;&nbsp;{$form.deleted_contacts.label nofilter}
    {/if}
  </td>
</tr>
