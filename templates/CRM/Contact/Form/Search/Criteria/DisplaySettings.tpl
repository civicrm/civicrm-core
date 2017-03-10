<div id="display-settings" class="form-item">
  <table>
    <tr>
      <td>
        {if $form.component_mode}
          {$form.component_mode.label} {help id="id-display-results"}
          <br />
          {$form.component_mode.html}
          {if $form.display_relationship_type}
            <div id="crm-display_relationship_type">{$form.display_relationship_type.html}</div>
          {/if}
        {else}
          &nbsp;
        {/if}
      </td>
      <td>
        {$form.uf_group_id.label} {help id="id-search-views"}<br />{$form.uf_group_id.html}
      </td>
    </tr>
  </table>
</div>
