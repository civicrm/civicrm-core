<?php
return function (phpQueryObject $doc) {
  // Add displays for every SavedSearch tagged "UserDashboard"
  $searchDisplays = civicrm_api4('SearchDisplay', 'get', [
    'checkPermissions' => FALSE,
    'select' => ['name', 'label', 'type:name', 'saved_search_id.name'],
    'where' => [
      ['saved_search_id.is_current', '=', TRUE],
      ['saved_search_id.tags:name', 'IN', ['UserDashboard']],
    ],
    'orderBy' => ['name' => 'ASC'],
  ]);
  $layout = '';
  foreach ($searchDisplays as $display) {
    $layout .= <<<HTML
    <div af-fieldset="" class="af-container-style-pane" af-title="$display[label]">
      <{$display['type:name']} search-name="{$display['saved_search_id.name']}" display-name="$display[name]"></{$display['type:name']}>
    </div>
  HTML;
  }

  // This naively adds all displays, under the assumption that site-builders never edit `afsearchUserDashboard` layout.
  // However, if they do it that, then this could instead do some reconciliation (adding/removing blocks as needed).
  $doc->html($layout);
  // $doc->append($layout);
};
