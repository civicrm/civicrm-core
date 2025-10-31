<div af-fieldset="">
  {*
    TODO: @see https://github.com/civicrm/civicrm-core/pull/33801
    <af-search-param-sets></af-search-param-sets>
  *}
  <details class="af-container af-layout-inline" af-title="Filters">
    {foreach $fieldDefns as $name=>$defn}
      <af-field name='{$name}' defn='{$defn|@json_encode}' />
    {/foreach}
  </details>
  <crm-search-display-table search-name='{$savedSearch}' display-name='{$searchDisplay}'></crm-search-display-table>
</div>