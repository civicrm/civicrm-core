<?php

return [
  'type' => 'search',
  'title' => ts('Tagged Entities'),
  'description' => ts('Popup listing the entities tagged with a given tag'),
  'server_route' => 'civicrm/tag/usage',
  'permission' => ['administer CiviCRM', 'manage tags'],
];
