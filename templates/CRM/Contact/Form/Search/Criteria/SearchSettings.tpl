<div id="display-settings" class="advanced-search-fields basic-fields form-layout">
  <div class="search-field">
    {if !empty($form.component_mode)}
      {$form.component_mode.label} {help id="id-display-results"}
      <br />
      {$form.component_mode.html}
      {if !empty($form.display_relationship_type)}
        <div id="crm-display_relationship_type">{$form.display_relationship_type.html}</div>
      {/if}
    {else}
      &nbsp;
    {/if}
  </div>
  <div class="search-field">
    {$form.uf_group_id.label} {help id="id-search-views"}<br />{$form.uf_group_id.html}
  </div>
  <div class="search-field">
    {$form.operator.label} {help id="id-search-operator"}<br />{$form.operator.html}
  </div>
</div>
