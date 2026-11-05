<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Find Participants'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/find-participants',
  'search_displays' => [
    'Find_Participants.Find_Participants_Table_1',
  ],
];
