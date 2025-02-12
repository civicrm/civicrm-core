<div af-fieldset="">
  <crm-search-display-{$display_type}
    search-name="{$saved_search}"
    display-name="{$search_display}"
    filters="{ldelim}entity_id: options.{$entity_id_filter}{rdelim}"
    />
</div>
