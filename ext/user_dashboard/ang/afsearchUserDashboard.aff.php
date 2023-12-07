<?php
use CRM_UserDashboard_ExtensionUtil as E;

$afform = [
  'type' => 'search',
  'title' => E::ts('User Dashboard'),
  'server_route' => 'civicrm/user',
  'permission' => ['access Contact Dashboard'],
  'layout' => '',
  // temporary, remove after merging https://github.com/civicrm/civicrm-core/pull/27783
  'requires' => ['af', 'afCore', 'crmSearchDisplayTable'],
];

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
foreach ($searchDisplays as $display) {
  $afform['layout'] .= <<<HTML
    <div af-fieldset="" class="af-container-style-pane" af-title="$display[label]">
      <{$display['type:name']} search-name="{$display['saved_search_id.name']}" display-name="$display[name]"></{$display['type:name']}>
    </div>
  HTML;
}

return $afform;
