<?php

return [
  'type' => 'search',
  'title' => ts('Comments'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/contact/view/note/comments',
  'permission' => [
    'access CiviCRM',
    'view all notes',
  ],
  'permission_operator' => 'OR',
];
