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

  // The subsequent examples are all variations on setting or omitting the `is_domain` and `is_contact` properties.

  'shimmy_domain_default' => [
    'group_name' => 'Shimmy Preferences',
    'group' => 'shimmy',
    'name' => 'shimmy_domain_default',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => 'shimmy_domain_default value',
    'title' => E::ts('Shimmy domain default'),
    // absent is_contact and is_domain, assume that this is domain-setting
    'help_text' => NULL,
    'settings_pages' => ['shimmy' => ['weight' => 10]],
  ],
  'shimmy_backfill_domain0' => [
    'group_name' => 'Shimmy Preferences',
    'group' => 'shimmy',
    'name' => 'shimmy_backfill_domain0',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => 'shimmy_backfill_domain0 value',
    'title' => E::ts('Shimmy backfill domain0'),
    'is_contact' => 1,
    // is_domain will be backfilled as !is_contact
    'help_text' => NULL,
    'settings_pages' => ['shimmy' => ['weight' => 10]],
  ],
  'shimmy_backfill_contact0' => [
    'group_name' => 'Shimmy Preferences',
    'group' => 'shimmy',
    'name' => 'shimmy_backfill_contact0',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => 'shimmy_backfill_contact0 value',
    'title' => E::ts('Shimmy backfill contact0'),
    'is_domain' => 1,
    // is_contact will be backfilled as !is_domain
    'help_text' => NULL,
    'settings_pages' => ['shimmy' => ['weight' => 10]],
  ],
];
