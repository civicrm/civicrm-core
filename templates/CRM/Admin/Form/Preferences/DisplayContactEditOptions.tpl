<tr class="crm-preferences-display-form-block-contact_edit_options">
  <td class="label">
    {$form.contact_edit_options.label}
    {help id="$setting_name" title=$fieldSpec.title file='CRM/Admin/Form/Setting/SettingField'}
  </td>
  <td>
    <table style="width:90%">
      <tr>
        <td style="width:30%">
          <span class="label"><strong>{ts}Individual Name Fields{/ts}</strong></span>
          <ul id="contactEditNameFields" class="crm-checkbox-list">
            {foreach from=$nameFields item="title" key="opId"}
              <li id="preference-{$opId}-contactedit">
                {$form.contact_edit_options.$opId.html}
              </li>
            {/foreach}
          </ul>
        </td>
        <td style="width:30%">
          <span class="label"><strong>{ts}Contact Details{/ts}</strong></span>
          <ul id="contactEditBlocks" class="crm-checkbox-list crm-sortable-list">
            {foreach from=$contactBlocks item="title" key="opId"}
              <li id="preference-{$opId}-contactedit">
                {$form.contact_edit_options.$opId.html}
              </li>
            {/foreach}
          </ul>
        </td>
        <td style="width:30%">
          <span class="label"><strong>{ts}Other Panes{/ts}</strong></span>
          <ul id="contactEditOptions"  class="crm-checkbox-list crm-sortable-list">
            {foreach from=$editOptions item="title" key="opId"}
              <li id="preference-{$opId}-contactedit">
                {$form.contact_edit_options.$opId.html}
              </li>
            {/foreach}
          </ul>
        </td>
      </tr>
    </table>
  </td>
</tr>
