<?php
use CRM_Temporary_ExtensionUtil as E;

return [
  'temporary_timestamps' => [
    'group_name' => 'Temporary Preferences',
    'group' => 'temporary',
    'name' => 'temporary_timestamps',
    'type' => 'String',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => 'CRM_Temporary_Meta::getTimestampModes',
    ],
    'default' => 'auto',
    'add' => '5.49',
    'title' => E::ts('Timestamp mode'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('How should timestamp fields be modeled?'),
    'help_text' => NULL,
    // 'settings_pages' => ['temporary' => ['weight' => 20]],
  ],
];
