<div af-fieldset="">
  {*
    TODO: @see https://github.com/civicrm/civicrm-core/pull/33801
    <af-search-param-sets></af-search-param-sets>
  *}
  <details class="af-container af-layout-inline" af-title="Filters">
    {foreach from=$fieldDefns key="name" item="defn"}
      <af-field name='{$name}' defn='{$defn|@json_encode|escape}' />
    {/foreach}
  </details>
  <crm-search-display-table search-name='{$savedSearch}' display-name='{$searchDisplay}'></crm-search-display-table>
</div>