<?php
use CRM_Search_ExtensionUtil as E;

return [
  'search_kit_entity_refresh' => [
    'group_name' => 'Search Kit',
    'group' => 'search_kit',
    'name' => 'search_kit_entity_refresh',
    'type' => 'String',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => 'Civi\Api4\Action\SKEntity\Refresh::getModeOptions',
    ],
    'default' => '',
    'add' => '5.83',
    'title' => E::ts('Default refresh mode'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('By default, how should the system perform refreshes on DB Entity?'),
    'help_text' => NULL,
  ],
];
