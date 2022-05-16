<?php
use CRM_Shimmy_ExtensionUtil as E;

return [
  'shimmy_example' => [
    'group_name' => 'Shimmy Preferences',
    'group' => 'shimmy',
    'name' => 'shimmy_example',
    'type' => 'String',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => 'CRM_Shimmy_Utils::getExampleOptions',
    ],
    'default' => 'first',
    'add' => '4.7',
    'title' => E::ts('Shimmy editor layout'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('What is a shimmy example?'),
    'help_text' => NULL,
    'settings_pages' => ['shimmy' => ['weight' => 10]],
  ],
];
