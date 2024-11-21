<div af-fieldset="">
  <div class="af-container af-layout-inline">
    <a class="af-button btn btn-primary" crm-i="fa-plus" target="crm-popup" ng-href="/civicrm/af/custom/{$group.name}/create#?entity_id={ldelim}{ldelim} options.contact_id {rdelim}{rdelim}">{ts 1=$group.title}Add new %1{/ts}</a>
  </div>
  <crm-search-display-{$display_type}
    search-name="{$saved_search}"
    display-name="{$search_display}"
    filters="{ldelim}entity_id: options.contact_id{rdelim}"
    />
</div>