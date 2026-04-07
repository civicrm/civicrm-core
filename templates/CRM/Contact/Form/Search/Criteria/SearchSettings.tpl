<div id="display-settings" class="advanced-search-fields basic-fields form-layout">
  <div class="search-field">
    {if !empty($form.component_mode)}
      {$form.component_mode.label} {help id="component_mode"}
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
    {$form.uf_group_id.label} {help id="uf_group_id"}<br />{$form.uf_group_id.html}
    {crmPermission has='administer CiviCRM'}
      <a class="crm-hover-button" target="_blank" href="{crmURL p="civicrm/admin/uf/group" q="reset=1" fb=1}">
        {icon icon="fa-wrench"}{ts}Manage Profiles{/ts}{/icon}
      </a>
    {/crmPermission}
  </div>
  <div class="search-field">
    {$form.operator.label} {help id="operator"}<br />{$form.operator.html}
  </div>
</div>
