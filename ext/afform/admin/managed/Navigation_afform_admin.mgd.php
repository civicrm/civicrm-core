<?php
use CRM_AfformAdmin_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afform_admin',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_admin',
        'label' => E::ts('FormBuilder'),
        'permission' => [
          'administer afform',
        ],
        'parent_id.name' => 'Customize Data and Screens',
        'weight' => 1,
        'url' => 'civicrm/admin/afform',
        'is_active' => 1,
        'icon' => 'crm-i fa-list-alt',
      ],
      'match' => ['domain_id', 'name'],
    ],
  ],
];
